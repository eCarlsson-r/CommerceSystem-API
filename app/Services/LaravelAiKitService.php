<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\Product;
use App\Models\Customer;
use App\Models\OrderItem;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Google\Auth\Credentials\ServiceAccountCredentials;

class LaravelAiKitService
{
    private int $maxRetries = 3;
    private float $retryDelay = 0.5;

    protected function getGoogleAccessToken()
    {
        $auth = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/cloud-platform',
            config('services.google.service_account')
        );
        return $auth->fetchAuthToken()['access_token'];
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
        
        // Gemini often uses 'global', but Imagen/embeddings usually prefer regional endpoints.
        // We'll try us-central1 as a fallback for everything except Gemini.
        $location = str_contains($model, 'gemini') ? 'global' : 'us-central1';
        
        // Try the base aiplatform endpoint which usually handles routing, 
        // but some regions might need the regional host.
        return "https://".(($location == 'global')? "": $location."-")."aiplatform.googleapis.com/v1/projects/{$project}/locations/{$location}/publishers/google/models/{$model}:{$action}";
    }

    public function postToVertex(string $model, string $action, array $payload, int $timeout = 30)
    {
        return Http::withToken($this->getGoogleAccessToken())
            ->timeout($timeout)
            ->post($this->getGoogleEndpoint($model, $action), $payload);
    }

    /**
     * ML-driven recommendation engine with multiple strategies:
     * - Collaborative filtering (co-purchases)
     * - Content-based filtering (category, price)
     * - Popularity-based (best sellers)
     * - Personalized (customer history)
     * - Contextual (current tags/context)
     */
    public function recommendations(array $payload): array
    {
        $maxResults = (int) ($payload['maxResults'] ?? 6);
        $productId = (int) ($payload['productId'] ?? 0);
        $customerId = (int) ($payload['customerId'] ?? 0);
        $contextTags = $payload['contextTags'] ?? [];
        $locale = $payload['locale'] ?? 'en';

        $scores = [];

        // Strategy 1: Collaborative Filtering (if product context given)
        if ($productId > 0) {
            $scores = $this->collaborativeScores($productId, $maxResults * 2);
        }

        // Strategy 2: Content-Based Filtering (category/price similarity)
        if ($productId > 0) {
            $contentScores = $this->contentBasedScores($productId, $maxResults * 2);
            $scores = $this->mergeScores($scores, $contentScores, 0.5);
        }

        // Strategy 3: Personalized (if customer given)
        if ($customerId > 0) {
            $personalScores = $this->personalizedScores($customerId, $maxResults * 2);
            $scores = $this->mergeScores($scores, $personalScores, 0.7);
        }

        // Strategy 4: Context-based (if tags given)
        if (!empty($contextTags)) {
            $contextScores = $this->contextBasedScores($contextTags, $maxResults * 2);
            $scores = $this->mergeScores($scores, $contextScores, 0.4);
        }

        // Strategy 5: Popularity-based (fallback/boost)
        $popularityScores = $this->popularityScores($maxResults * 3);
        $scores = $this->mergeScores($scores, $popularityScores, 0.3);

        // Filter to available stock only
        $availableScores = collect($scores)
            ->filter(fn ($item) => $this->isInStock((int) $item['productId']))
            ->values();

        // If no scores, use top sellers
        if ($availableScores->isEmpty()) {
            $availableScores = collect($this->topSellers($maxResults));
        }

        // Sort by score and take top results
        $items = $availableScores
            ->sortByDesc('score')
            ->take($maxResults)
            ->map(fn ($item) => [
                'productId' => (int) $item['productId'],
                'name' => Product::find($item['productId'])->name ?? 'Unknown Product',
                'imageUrl' => Product::find($item['productId'])->media[0]->path ?? null,
                'price' => Product::find($item['productId'])->base_price ?? null,
                'score' => round(min(1.0, max(0.0, (float) $item['score'])), 3),
                'reason' => $item['reason'] ?? 'Recommended based on your preferences',
            ])
            ->values()
            ->all();

        return ['items' => $items];
    }

    /**
     * Collaborative Filtering: Find products frequently bought with the current product
     */
    private function collaborativeScores(int $productId, int $limit): array
    {
        $coPurchases = DB::table('order_items as oi1')
            ->join('order_items as oi2', 'oi1.order_id', '=', 'oi2.order_id')
            ->where('oi1.product_id', $productId)
            ->where('oi2.product_id', '!=', $productId)
            ->select('oi2.product_id')
            ->selectRaw('COUNT(*) as co_purchase_count')
            ->groupBy('oi2.product_id')
            ->orderByDesc('co_purchase_count')
            ->limit($limit)
            ->get();

        $maxCount = $coPurchases->max('co_purchase_count') ?? 1;

        return $coPurchases->map(fn ($row) => [
            'productId' => (int) $row->product_id,
            'score' => (float) $row->co_purchase_count / $maxCount * 0.9,
            'reason' => 'Frequently bought together',
        ])->all();
    }

    /**
     * Content-Based Filtering: Find similar products by category and price range
     */
    private function contentBasedScores(int $productId, int $limit): array
    {
        $sourceProduct = Product::find($productId);
        if (!$sourceProduct) {
            return [];
        }

        $priceMin = $sourceProduct->base_price * 0.7;
        $priceMax = $sourceProduct->base_price * 1.3;

        $similar = Product::query()
            ->where('id', '!=', $productId)
            ->where('category_id', $sourceProduct->category_id)
            ->whereBetween('base_price', [$priceMin, $priceMax])
            ->limit($limit)
            ->get();

        return $similar->map(function ($product) use ($sourceProduct) {
            $priceDiff = abs($product->base_price - $sourceProduct->base_price) / max($sourceProduct->base_price, 1);
            $priceScore = max(0.5, 1 - ($priceDiff * 0.3));

            return [
                'productId' => (int) $product->id,
                'score' => $priceScore,
                'reason' => 'Similar category and price point',
            ];
        })->all();
    }

    /**
     * Personalized Recommendations: Based on customer's purchase history
     */
    private function personalizedScores(int $customerId, int $limit): array
    {
        $customer = Customer::find($customerId);
        if (!$customer) {
            return [];
        }

        // Find categories the customer has purchased from
        $purchasedCategories = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.customer_id', $customerId)
            ->distinct()
            ->pluck('products.category_id')
            ->toArray();

        if (empty($purchasedCategories)) {
            return [];
        }

        // Recommend popular items from those categories
        $recommended = Product::query()
            ->whereIn('category_id', $purchasedCategories)
            ->withCount(['stocks' => fn ($q) => $q->where('quantity', '>', 0)])
            ->orderByDesc('stocks_count')
            ->limit($limit)
            ->get();

        $maxStock = $recommended->max('stocks_count') ?? 1;

        return $recommended->map(fn ($product) => [
            'productId' => (int) $product->id,
            'score' => min(0.85, ($product->stocks_count ?? 0) / max($maxStock, 1) * 0.8),
            'reason' => 'Popular in your favorite categories',
        ])->all();
    }

    /**
     * Context-Based Filtering: Use contextual tags (room type, style, etc.)
     */
    private function contextBasedScores(array $contextTags, int $limit): array
    {
        if (empty($contextTags)) {
            return [];
        }

        // Find products with descriptions matching context tags
        $query = Product::query();

        foreach ($contextTags as $tag) {
            $query->orWhere('name', 'ilike', "%{$tag}%")
                ->orWhere('description', 'ilike', "%{$tag}%");
        }

        $matches = $query->limit($limit)->get();

        return $matches->map(fn ($product) => [
            'productId' => (int) $product->id,
            'score' => 0.7,
            'reason' => 'Matches your search context',
        ])->all();
    }

    /**
     * Popularity-Based Scoring: Top sellers and most viewed products
     */
    private function popularityScores(int $limit): array
    {
        $topSellers = DB::table('order_items')
            ->select('product_id')
            ->selectRaw('SUM(quantity) as total_sold')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();

        $maxSold = $topSellers->max('total_sold') ?? 1;

        return $topSellers->map(fn ($row) => [
            'productId' => (int) $row->product_id,
            'score' => (float) $row->total_sold / $maxSold * 0.75,
            'reason' => 'Best seller in your market',
        ])->all();
    }

    /**
     * Top Sellers: Simple fallback list
     */
    private function topSellers(int $limit): array
    {
        $sellers = DB::table('order_items')
            ->select('product_id')
            ->selectRaw('COUNT(*) as sales_count')
            ->groupBy('product_id')
            ->orderByDesc('sales_count')
            ->limit($limit)
            ->get();

        return $sellers->map(fn ($row) => [
            'productId' => (int) $row->product_id,
            'score' => 0.8,
            'reason' => 'Top seller',
        ])->all();
    }

    /**
     * Check if product is in stock
     */
    private function isInStock(int $productId): bool
    {
        return Stock::where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->exists();
    }

    /**
     * Merge two score arrays, combining scores with a weight factor
     */
    private function mergeScores(array $existing, array $new, float $weight): array
    {
        $merged = collect($existing);

        foreach ($new as $item) {
            $productId = $item['productId'];
            $existing_item = $merged->firstWhere('productId', $productId);

            if ($existing_item) {
                // Weighted average
                $existing_item['score'] = ($existing_item['score'] * 0.6) + ($item['score'] * $weight);
                $merged = $merged->map(fn ($i) => $i['productId'] === $productId ? $existing_item : $i);
            } else {
                $merged->push($item);
            }
        }

        return $merged->values()->all();
    }

    public function visualSearch(array $payload): array
    {
        // Use recommendations as foundation with image context
        $imageUrl = $payload['imageUrl'] ?? '';
        $maxResults = (int) ($payload['maxResults'] ?? 6);

        // For now, treat visual search as recommendations with style inference
        // In production, you'd use a vision model to extract image features
        $payload['contextTags'] = ['wallpaper', 'interior', 'design'];

        $recommendations = $this->recommendations($payload);

        return [
            ...$recommendations,
            'visualSearchMetadata' => [
                'imageProcessed' => !empty($imageUrl),
                'inferredTags' => ['wallpaper', 'interior', 'design'],
                'confidence' => 0.78,
            ],
        ];
    }

    public function assistant(array $payload): array
    {
        $message = trim((string) ($payload['message'] ?? ''));
        $customerId = (int) ($payload['customerId'] ?? 0);
        $locale = (string) ($payload['locale'] ?? 'en');
        
        $context = $payload['context'] ?? [];
        $history = $payload['history'] ?? $context['history'] ?? [];

        if ($message === '') {
            return [
                'reply' => 'Please share your room style, colors, and wall size to get suggestions.',
                'conversationId' => 'new',
            ];
        }

        // Extract keywords for contextual recommendations
        $keywords = $this->extractKeywords($message);

        // Call Gemini to generate both the styling advice and contextual follow-up questions
        $aiResult = $this->getAiResponse($message, $locale, $history, $keywords);

        return [
            'reply' => $aiResult['reply'],
            'followUps' => $aiResult['followUps'],
            'suggestedProducts' => count($keywords) > 0 ? $this->recommendations([
                'contextTags' => $keywords,
                'customerId' => $customerId,
                'maxResults' => 3,
            ])['items'] : [],
            'conversationId' => 'session_' . uniqid(),
        ];
    }

    /**
     * Extract keywords from user message for contextual recommendations
     */
    private function extractKeywords(string $message): array
    {
        $keywords = [];

        // Room types
        if (preg_match('/(bedroom|living room|kitchen|bathroom|office|hallway|dining)/i', $message, $m)) {
            $keywords[] = strtolower($m[1]);
        }

        // Styles
        if (preg_match('/(modern|vintage|bohemian|minimalist|industrial|classic|contemporary)/i', $message, $m)) {
            $keywords[] = strtolower($m[1]);
        }

        // Colors
        if (preg_match('/(blue|green|red|yellow|neutral|white|black|grey|gray)/i', $message, $m)) {
            $keywords[] = strtolower($m[1]);
        }

        return array_unique($keywords);
    }

    /**
     * Generate dynamic conversational replies and contextual follow-up questions from Gemini 3.1 Flash with static fallback
     */
    private function getAiResponse(string $message, string $locale, array $history, array $keywords): array
    {
        try {
            $systemInstruction = "You are an expert, friendly AI wallpaper and room styling advisor for our high-end home decor boutique. "
                . "Provide warm, inspiring, luxury-oriented design recommendations. "
                . "Always write the response in the matching customer locale: " . ($locale === 'id' ? 'Bahasa Indonesia' : 'English') . ". "
                . "Ensure you maintain consistency and remember details from previous turns. "
                . "Format your output strictly as a JSON object with two keys:\n"
                . "1. 'reply': A string containing your design recommendation. Keep it helpful, engaging, and under 3-4 sentences max. Do not include any greeting or conversational fluff, start directly with the helpful advice.\n"
                . "2. 'followUps': An array of exactly 2-3 short, contextual, and highly relevant follow-up questions for the user to ask next (e.g., 'Suggest modern designs', 'Calculate my roll quantity', or 'I prefer pastel colors').";

            $contents = [];
            $lastRole = null;

            // Map and format history ensuring role alternation for Gemini REST schema compliance
            foreach ($history as $msg) {
                $text = trim($msg['text'] ?? '');
                if ($text === '') continue;

                $role = ($msg['role'] === 'user' || ($msg['sender'] ?? '') === 'user') ? 'user' : 'model';

                // Skip welcome message to avoid beginning history with model
                if ($role === 'model' && empty($contents)) {
                    continue;
                }

                if ($role === $lastRole) {
                    $lastIdx = count($contents) - 1;
                    $contents[$lastIdx]['parts'][0]['text'] .= "\n\n" . $text;
                } else {
                    $contents[] = [
                        'role' => $role,
                        'parts' => [['text' => $text]]
                    ];
                    $lastRole = $role;
                }
            }

            // Append the latest user query
            if ($lastRole === 'user') {
                $lastIdx = count($contents) - 1;
                $contents[$lastIdx]['parts'][0]['text'] .= "\n\n" . $message;
            } else {
                $contents[] = [
                    'role' => 'user',
                    'parts' => [['text' => $message]]
                ];
            }

            $response = $this->postToVertex("gemini-3.1-flash-lite", "generateContent", [
                'contents' => $contents,
                'systemInstruction' => [
                    'parts' => [['text' => $systemInstruction]]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json'
                ]
            ]);

            if ($response->successful()) {
                $rawText = trim($response->json('candidates.0.content.parts.0.text'));
                $data = json_decode($rawText, true);
                if (isset($data['reply']) && isset($data['followUps'])) {
                    return [
                        'reply' => $data['reply'],
                        'followUps' => is_array($data['followUps']) ? $data['followUps'] : [],
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning("Vertex AI dynamic assistant call failed, using rule-based fallback. Error: " . $e->getMessage());
        }

        // Fallback response if LLM call fails
        $replies = [];
        if (in_array('bedroom', $keywords)) {
            $replies[] = 'For bedroom wallpaper, consider calming colors and patterns that promote relaxation.';
        }
        if (in_array('living room', $keywords)) {
            $replies[] = 'Living rooms can handle bolder patterns. Choose durable, washable options for high-traffic areas.';
        }
        if (in_array('modern', $keywords)) {
            $replies[] = 'Modern styles pair well with geometric patterns and muted color palettes.';
        }
        if (in_array('vintage', $keywords)) {
            $replies[] = 'Vintage designs work best with rich textures and classic patterns.';
        }
        if (empty($replies)) {
            $replies[] = 'Based on your request, I recommend starting with a sample roll to test the pattern and color in your space.';
        }

        $baseReply = implode(' ', $replies);
        $baseReply .= ' Would you like me to calculate quantity needed, or suggest specific patterns matching your style?';

        $localFollowUps = [];
        if ($locale === 'id') {
            $localFollowUps = [
                'Hitung jumlah roll?',
                'Rekomendasi warna?',
                'Sampel gratis?',
            ];
        } else {
            $localFollowUps = [
                'Calculate roll quantity?',
                'Recommend specific colors?',
                'Get free samples?',
            ];
        }

        return [
            'reply' => $baseReply,
            'followUps' => $localFollowUps,
        ];
    }

    public function translateDraft(array $payload): array
    {
        $text = (string) ($payload['text'] ?? '');
        $targetLocale = (string) ($payload['targetLocale'] ?? 'en');
        $sourceLocale = (string) ($payload['sourceLocale'] ?? 'en');

        if ($text === '') {
            return [
                'translatedText' => '',
                'qualityHint' => 'review_needed',
                'metadata' => ['isEmpty' => true],
            ];
        }

        // In production, use a real translation API (Google Translate, AWS Translate, OpenAI, etc.)
        // For now, provide a reasonable draft with locale markers
        $translatedText = $this->simpleDraft($text, $sourceLocale, $targetLocale);

        return [
            'translatedText' => $translatedText,
            'qualityHint' => 'draft',
            'locales' => [
                'source' => $sourceLocale,
                'target' => $targetLocale,
            ],
            'metadata' => [
                'characterCount' => strlen($text),
                'wordCount' => str_word_count($text),
                'requiresReview' => true,
            ],
        ];
    }

    /**
     * Simple draft translation (placeholder for real translation API)
     */
    private function simpleDraft(string $text, string $sourceLocale, string $targetLocale): string
    {
        // Placeholder: In production, call OpenAI, Google Translate, or AWS Translate
        return "[{$targetLocale}] {$text}";
    }

    public function editImage(array $payload): array
    {
        $prompt = $payload['prompt'];
        $baseImageBase64 = $payload['baseImageBase64'];
        $maskImageBase64 = $payload['maskImageBase64'] ?? null;
        $productImageBase64 = $payload['productImageBase64'] ?? null;

        $instances = [
            'prompt' => $prompt,
            'image' => [
                'bytesBase64Encoded' => $baseImageBase64
            ]
        ];

        $parameters = [
            'sampleCount' => 1,
            'mode' => 'edit',
            ];

        if ($maskImageBase64) {
            $instances['mask'] = [
                'image' => [
                    'bytesBase64Encoded' => $maskImageBase64
                ]
            ];
        } else {
            // Mask-free / automatic masking mode.
            // REFERENCE_TYPE_RAW (id:1) = context image — tells the model what to preserve.
            // REFERENCE_TYPE_STYLE (id:2) = style image — the actual wallpaper pattern to apply.
            //
            // The Imagen 3 API requires at least one referenceImage for mask-free editing.
            $referenceImages = [
                [
                    'referenceType'  => 'REFERENCE_TYPE_RAW',
                    'referenceId'    => 1,
                    'referenceImage' => [
                        'bytesBase64Encoded' => $baseImageBase64
                    ]
                ]
            ];

            // When the caller provides the actual product texture, add it as a style
            // reference so the model paints that specific pattern onto the walls.
            if ($productImageBase64) {
                $referenceImages[] = [
                    'referenceType'  => 'REFERENCE_TYPE_STYLE',
                    'referenceId'    => 2,
                    'referenceImage' => [
                        'bytesBase64Encoded' => $productImageBase64
                    ]
                ];
            }

            $instances['referenceImages'] = $referenceImages;

            $parameters['maskConfig'] = [
                'maskMode' => 'MASK_MODE_BACKGROUND'
            ];
            }

        return $this->retryHttp(function () use ($instances, $parameters) {
            // Trying imagen-3 as it's more likely to be available than the specific @006 version
            $response = $this->postToVertex('imagen-3.0-capability-001', 'predict', [
                'instances' => [$instances],
                'parameters' => $parameters
            ], 60);

            if ($response->failed()) {
                Log::error("Vertex AI Image Edit failed (Status: {$response->status()}): " . $response->body());
                throw new \Exception("Vertex AI prediction failed: " . $response->body());
            }
            
            $imageBase64 = $response->json('predictions.0.bytesBase64Encoded');
            
            if (!$imageBase64) {
                Log::warning("Vertex AI Image Edit returned no prediction. Full Response: " . $response->body());
            }

            return ['image_base64' => $imageBase64];
        }, 'image-generation');
    }
}
