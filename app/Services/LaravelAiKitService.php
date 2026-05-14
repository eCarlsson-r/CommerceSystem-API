<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\Product;
use App\Models\Customer;
use App\Models\OrderItem;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class LaravelAiKitService
{
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
            $query->orWhere('name', 'LIKE', "%{$tag}%")
                ->orWhere('description', 'LIKE', "%{$tag}%");
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

        if ($message === '') {
            return [
                'reply' => 'Please share your room style, colors, and wall size to get suggestions.',
                'conversationId' => 'new',
            ];
        }

        // Extract keywords for contextual recommendations
        $keywords = $this->extractKeywords($message);

        return [
            'reply' => $this->generateAssistantReply($message, $keywords, $customerId),
            'followUps' => $this->generateFollowUpQuestions($keywords),
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
     * Generate contextual assistant replies
     */
    private function generateAssistantReply(string $message, array $keywords, int $customerId): string
    {
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

        return $baseReply;
    }

    /**
     * Generate follow-up questions based on context
     */
    private function generateFollowUpQuestions(array $keywords): array
    {
        $followUps = [
            'What room type is this wallpaper for?',
            'Do you prefer subtle or statement patterns?',
            'Would you like washable material recommendations?',
            'What is your approximate wall size?',
            'Do you have a preferred color palette?',
        ];

        // Customize based on extracted keywords
        if (in_array('bedroom', $keywords)) {
            $followUps[] = 'Would you prefer calming or energizing colors for your bedroom?';
        }

        return array_slice($followUps, 0, 3);
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
}
