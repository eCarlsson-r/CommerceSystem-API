<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LaravelAiKitService
{
    private array $costTracker = [];
    private int $maxRetries = 3;
    private float $retryDelay = 0.5;

    /**
     * Track API costs for monitoring
     */
    private function trackCost(string $operation, int $tokens = 0): void
    {
        $this->costTracker[] = [
            'operation' => $operation,
            'tokens' => $tokens,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Get total costs for current request
     */
    public function getCosts(): array
    {
        return [
            'operations' => count($this->costTracker),
            'details' => $this->costTracker,
        ];
    }

    /**
     * Retry wrapper for HTTP calls
     */
    private function retryHttp(callable $callback, string $operation)
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->maxRetries) {
            try {
                $result = $callback();
                $this->trackCost($operation);
                return $result;
            } catch (\Exception $e) {
                $lastException = $e;
                $attempts++;
                
                if ($attempts < $this->maxRetries) {
                    usleep($this->retryDelay * 1000000 * $attempts); // Exponential backoff
                    Log::warning("Vertex AI retry {$attempts}/{$this->maxRetries} for {$operation}: {$e->getMessage()}");
                }
            }
        }

        Log::error("Vertex AI failed after {$this->maxRetries} attempts for {$operation}: {$lastException->getMessage()}");
        throw $lastException;
    }
    private function getGoogleEndpoint(string $model, string $action): string
    {
        $project = config('services.google.project_id');
        $location = config('services.google.location', 'us-central1');
        return "https://{$location}-aiplatform.googleapis.com/v1/projects/{$project}/locations/{$location}/publishers/google/models/{$model}:{$action}";
    }

    /**
     * Get embedding for text (PostgreSQL pgvector format)
     * Returns pgvector string "[0.1,0.2,...]"
     */
    private function getEmbedding(string $text): string
    {
        // Cache embeddings to reduce API calls and cost
        $cacheKey = 'embedding:' . md5($text);
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($text) {
            return $this->retryHttp(function () use ($text) {
                $response = Http::withToken(config('services.google.token'))
                    ->timeout(30)
                    ->post($this->getGoogleEndpoint('text-embedding-004', 'predict'), [
                        'instances' => [['content' => substr($text, 0, 8000)]]
                    ]);
                
                if (!$response->successful()) {
                    throw new \Exception('Embedding API error: ' . $response->body());
                }
                
                $vector = $response->json('predictions.0.embeddings.values');
                return is_array($vector) ? '[' . implode(',', $vector) . ']' : '[]';
            }, 'text-embedding');
        });
    }

    /**
     * Batch embedding for multiple texts (more efficient)
     * Returns array of pgvector strings
     */
    public function getBatchEmbeddings(array $texts): array
    {
        $results = [];
        $batchSize = 5; // Vertex AI batch limit
        
        foreach (array_chunk($texts, $batchSize) as $chunk) {
            $response = $this->retryHttp(function () use ($chunk) {
                $instances = array_map(fn($text) => ['content' => substr($text, 0, 8000)], $chunk);
                
                return Http::withToken(config('services.google.token'))
                    ->timeout(60)
                    ->post($this->getGoogleEndpoint('text-embedding-004', 'predict'), [
                        'instances' => $instances
                    ]);
            }, 'batch-text-embedding');

            $predictions = $response->json('predictions') ?? [];
            foreach ($predictions as $pred) {
                $vector = $pred['embeddings']['values'] ?? [];
                $results[] = is_array($vector) ? '[' . implode(',', $vector) . ']' : '[]';
            }
        }

        return $results;
    }

    /**
     * Get multimodal embedding for image (PostgreSQL pgvector format)
     * Returns pgvector string "[0.1,0.2,...]"
     */
    private function getMultimodalEmbedding(string $imageUrl): string
    {
        return $this->retryHttp(function () use ($imageUrl) {
            $imageData = base64_encode(file_get_contents($imageUrl));
            $response = Http::withToken(config('services.google.token'))
                ->timeout(30)
                ->post($this->getGoogleEndpoint('multimodalembedding@001', 'predict'), [
                    'instances' => [
                        [
                            'image' => ['bytesBase64Encoded' => $imageData]
                        ]
                    ]
                ]);

            if (!$response->successful()) {
                throw new \Exception('Multimodal embedding API error: ' . $response->body());
            }

            $vector = $response->json('predictions.0.imageEmbedding');
            return is_array($vector) ? '[' . implode(',', $vector) . ']' : '[]';
        }, 'multimodal-embedding');
    }

    public function recommendations(array $payload): array
    {
        $maxResults = (int) ($payload['maxResults'] ?? 6);
        $context = implode(' ', $payload['contextTags'] ?? []);

        if (empty($context)) {
            // Fallback
            $items = Stock::query()
                ->where('quantity', '>', 0)
                ->inRandomOrder()
                ->take($maxResults)
                ->get()
                ->map(fn ($stock) => [
                    'productId' => (int) $stock->product_id,
                    'score' => 1.0,
                    'reason' => 'Random in-stock suggestion',
                ])
                ->all();
            return ['items' => $items];
        }

        $vectorStr = $this->getEmbedding($context);
        if ($vectorStr === '[]') {
            return ['items' => []];
        }

        // PostgreSQL pgvector search: Use <=> operator for cosine distance
        $products = DB::table('products')
            ->select('id', DB::raw("1 - (embedding <=> '{$vectorStr}') AS score"))
            ->whereNotNull('embedding')
            ->orderByRaw("embedding <=> '{$vectorStr}'")
            ->limit($maxResults)
            ->get();

        $items = $products->map(fn ($product) => [
            'productId' => (int) $product->id,
            'score' => round((float) $product->score, 2),
            'reason' => 'Semantic match via pgvector',
        ])->all();

        return ['items' => $items];
    }

    public function visualSearch(array $payload): array
    {
        $maxResults = (int) ($payload['maxResults'] ?? 6);
        $imageUrl = $payload['imageUrl'] ?? '';

        if (empty($imageUrl)) {
            return ['items' => []];
        }

        $vectorStr = $this->getMultimodalEmbedding($imageUrl);
        if ($vectorStr === '[]') {
            return ['items' => []];
        }

        // PostgreSQL pgvector search using multimodal embedding
        $products = DB::table('products')
            ->select('id', DB::raw("1 - (embedding <=> '{$vectorStr}') AS score"))
            ->whereNotNull('embedding')
            ->orderByRaw("embedding <=> '{$vectorStr}'")
            ->limit($maxResults)
            ->get();

        $items = $products->map(fn ($product) => [
            'productId' => (int) $product->id,
            'score' => round((float) $product->score, 2),
            'reason' => 'Visually similar via Vertex AI',
        ])->all();

        return ['items' => $items];
    }

    public function assistant(array $payload): array
    {
        $message = trim((string) ($payload['message'] ?? ''));
        if ($message === '') {
            return ['reply' => 'Please share your room style, colors, and wall size to get suggestions.'];
        }

        // RAG: Retrieve context with caching (PostgreSQL pgvector)
        $cacheKey = 'assistant_context:' . md5($message);
        $contextDocs = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($message) {
            $vectorStr = $this->getEmbedding($message);
            if ($vectorStr === '[]') {
                return '';
            }
            
            // PostgreSQL pgvector: Get top similar products using <=> operator
            $products = DB::table('products')
                ->select('name', 'description', 'base_price')
                ->whereNotNull('embedding')
                ->orderByRaw("embedding <=> '{$vectorStr}'")
                ->limit(5)
                ->get();
            
            $docs = '';
            foreach ($products as $p) {
                $docs .= "Product: {$p->name}. Price: {$p->base_price}. Description: {$p->description}\n";
            }
            return $docs;
        });

        $systemPrompt = "You are a helpful wallpaper and interior design assistant. Answer questions about wallpaper styles, room dimensions, pricing, and installation. Be concise and friendly. If you don't know something, say so.";
        $prompt = $contextDocs 
            ? "{$systemPrompt}\n\nRelevant products:\n{$contextDocs}\n\nUser question: {$message}"
            : "{$systemPrompt}\n\nUser question: {$message}";

        $response = $this->retryHttp(function () use ($prompt) {
            return Http::withToken(config('services.google.token'))
                ->timeout(30)
                ->post($this->getGoogleEndpoint('gemini-1.5-flash', 'generateContent'), [
                    'contents' => [
                        'role' => 'user',
                        'parts' => [['text' => $prompt]]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 500,
                        'topP' => 0.8,
                    ]
                ]);
        }, 'assistant-chat');

        $reply = $response->json('candidates.0.content.parts.0.text') 
            ?? 'I apologize, but I could not process your request. Please try again.';

        return [
            'reply' => trim($reply),
            'context_used' => !empty($contextDocs),
            'followUps' => $this->generateFollowUps($message, $reply),
        ];
    }

    /**
     * Generate contextual follow-up questions
     */
    private function generateFollowUps(string $message, string $reply): array
    {
        $followUps = [];
        
        if (stripos($message, 'price') !== false || stripos($message, 'cost') !== false) {
            $followUps[] = 'What is your budget range?';
        }
        if (stripos($message, 'room') !== false || stripos($message, 'wall') !== false) {
            $followUps[] = 'What are your wall dimensions?';
        }
        if (stripos($message, 'style') !== false || stripos($message, 'pattern') !== false) {
            $followUps[] = 'Do you prefer modern or classic designs?';
        }
        if (count($followUps) < 2) {
            $followUps[] = 'Can I see more wallpaper options?';
        }
        
        return array_slice($followUps, 0, 3);
    }

    public function translateDraft(array $payload): array
    {
        $text = (string) ($payload['text'] ?? '');
        $targetLocale = (string) ($payload['targetLocale'] ?? 'en');

        if ($text === '') {
            return ['translatedText' => '', 'qualityHint' => 'review_needed'];
        }

        $prompt = "Translate the following text to {$targetLocale}:\n\n{$text}";

        $response = Http::withToken(config('services.google.token'))
            ->post($this->getGoogleEndpoint('gemini-1.5-flash', 'streamGenerateContent'), [
                'contents' => ['parts' => ['text' => $prompt]]
            ]);

        $translatedText = $response->json('0.candidates.0.content.parts.0.text') ?? $response->json('candidates.0.content.parts.0.text') ?? '';

        return [
            'translatedText' => $translatedText,
            'qualityHint' => 'ai_draft',
        ];
    }

    public function editImage(array $payload): array
    {
        $prompt = $payload['prompt'];
        $baseImageBase64 = $payload['baseImageBase64'];
        $maskImageBase64 = $payload['maskImageBase64'] ?? null;

        $instances = [
            'prompt' => $prompt,
            'image' => [
                'bytesBase64Encoded' => $baseImageBase64
            ]
        ];

        if ($maskImageBase64) {
            $instances['mask'] = [
                'bytesBase64Encoded' => $maskImageBase64
            ];
        }

        return $this->retryHttp(function () use ($instances) {
            $response = Http::withToken(config('services.google.token'))
                ->timeout(60)
                ->post($this->getGoogleEndpoint('imagegeneration@006', 'predict'), [
                    'instances' => [$instances],
                    'parameters' => [
                        'sampleCount' => 1,
                        'mode' => 'edit',
                    ]
                ]);
            
            return ['image_base64' => $response->json('predictions.0.bytesBase64Encoded')];
        }, 'image-generation');
    }

    /**
     * Sync embeddings for all products (run as scheduled job)
     */
    public function syncAllProductEmbeddings(): array
    {
        $products = Product::whereNull('embedding')
            ->orWhereRaw('updated_at > created_at + INTERVAL 1 DAY')
            ->select('id', 'name', 'description', 'category_id')
            ->get();

        $texts = [];
        $productIds = [];
        
        foreach ($products as $product) {
            $text = "Product: {$product->name}. ";
            if ($product->description) {
                $text .= "Description: {$product->description}. ";
            }
            $texts[] = $text;
            $productIds[] = $product->id;
        }

        if (empty($texts)) {
            return ['processed' => 0, 'message' => 'No products need embedding update'];
        }

        $embeddings = $this->getBatchEmbeddings($texts);
        $updated = 0;
        
        foreach ($productIds as $i => $productId) {
            if (isset($embeddings[$i]) && $embeddings[$i] !== '[]') {
                // Store pgvector string directly for PostgreSQL
                DB::table('products')
                    ->where('id', $productId)
                    ->update(['embedding' => $embeddings[$i], 'updated_at' => now()]);
                $updated++;
            }
        }

        return [
            'processed' => count($texts),
            'updated' => $updated,
            'costs' => $this->getCosts(),
        ];
    }

    /**
     * Health check for Vertex AI connectivity
     */
    public function healthCheck(): array
    {
        try {
            $start = microtime(true);
            $this->getEmbedding('health check');
            $latency = round((microtime(true) - $start) * 1000);
            
            return [
                'status' => 'healthy',
                'latency_ms' => $latency,
                'timestamp' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ];
        }
    }
}
