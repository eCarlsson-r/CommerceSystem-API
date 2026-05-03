<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class LaravelAiKitService
{
    private function getGoogleEndpoint(string $model, string $action): string
    {
        $project = config('services.google.project_id');
        $location = config('services.google.location', 'us-central1');
        return "https://{$location}-aiplatform.googleapis.com/v1/projects/{$project}/locations/{$location}/publishers/google/models/{$model}:{$action}";
    }

    private function getEmbedding(string $text): string
    {
        $response = Http::withToken(config('services.google.token'))
            ->post($this->getGoogleEndpoint('text-embedding-004', 'predict'), [
                'instances' => [['content' => $text]]
            ]);
            
        $vector = $response->json('predictions.0.embeddings.values');
        return is_array($vector) ? '[' . implode(',', $vector) . ']' : '[]';
    }

    private function getMultimodalEmbedding(string $imageUrl): string
    {
        $imageData = base64_encode(file_get_contents($imageUrl));
        $response = Http::withToken(config('services.google.token'))
            ->post($this->getGoogleEndpoint('multimodalembedding@001', 'predict'), [
                'instances' => [
                    [
                        'image' => ['bytesBase64Encoded' => $imageData]
                    ]
                ]
            ]);

        $vector = $response->json('predictions.0.imageEmbedding');
        return is_array($vector) ? '[' . implode(',', $vector) . ']' : '[]';
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

        // pgvector search on products
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

        // pgvector search using multimodal embedding
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

        // RAG: Retrieve context
        $vectorStr = $this->getEmbedding($message);
        $contextDocs = "";
        if ($vectorStr !== '[]') {
            $products = DB::table('products')
                ->select('name', 'description')
                ->whereNotNull('embedding')
                ->orderByRaw("embedding <=> '{$vectorStr}'")
                ->limit(3)
                ->get();
            
            foreach ($products as $p) {
                $contextDocs .= "Product: {$p->name}. Description: {$p->description}\n";
            }
        }

        $prompt = "You are a helpful retail assistant. Use the following product context to answer the user.\nContext:\n{$contextDocs}\n\nUser: {$message}";

        $response = Http::withToken(config('services.google.token'))
            ->post($this->getGoogleEndpoint('gemini-1.5-flash', 'streamGenerateContent'), [
                'contents' => ['parts' => ['text' => $prompt]]
            ]);

        $reply = $response->json('0.candidates.0.content.parts.0.text') ?? $response->json('candidates.0.content.parts.0.text') ?? 'I could not process that request.';

        return [
            'reply' => $reply,
            'followUps' => [
                'Can you show me more options?',
                'What are the dimensions?',
            ],
        ];
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

        $response = Http::withToken(config('services.google.token'))
            ->post($this->getGoogleEndpoint('imagegeneration@006', 'predict'), [
                'instances' => [$instances],
                'parameters' => [
                    'sampleCount' => 1,
                    // Edit mode parameters like mode="edit" or "inpainting"
                    'mode' => 'edit',
                ]
            ]);

        return ['image_base64' => $response->json('predictions.0.bytesBase64Encoded')];
    }
}
