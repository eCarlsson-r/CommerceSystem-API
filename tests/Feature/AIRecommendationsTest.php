<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Stock;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Branch;
use App\Services\LaravelAiKitService;
use Tests\TestCase;

class AIRecommendationsTest extends TestCase
{
    private LaravelAiKitService $aiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aiService = new LaravelAiKitService();
    }

    public function test_recommendations_returns_in_stock_items(): void
    {
        // Create test data
        $category = Category::factory()->create();
        $branch = Branch::factory()->create();
        
        for ($i = 0; $i < 5; $i++) {
            $product = Product::factory()->create(['category_id' => $category->id]);
            Stock::factory()->create([
                'product_id' => $product->id,
                'branch_id' => $branch->id,
                'quantity' => $i > 2 ? 0 : 10, // Some in stock, some out
            ]);
        }

        $result = $this->aiService->recommendations(['maxResults' => 3]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertLessThanOrEqual(3, count($result['items']));
        
        // Verify structure
        foreach ($result['items'] as $item) {
            $this->assertArrayHasKey('productId', $item);
            $this->assertArrayHasKey('score', $item);
            $this->assertArrayHasKey('reason', $item);
            $this->assertGreaterThanOrEqual(0, $item['score']);
            $this->assertLessThanOrEqual(1, $item['score']);
        }
    }

    public function test_personalized_recommendations_use_customer_history(): void
    {
        $category = Category::factory()->create();
        $branch = Branch::factory()->create();
        $customer = Customer::factory()->create();

        // Create customer purchase history
        $purchasedProduct = Product::factory()->create(['category_id' => $category->id]);
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $purchasedProduct->id,
        ]);

        // Create similar products
        for ($i = 0; $i < 3; $i++) {
            $product = Product::factory()->create(['category_id' => $category->id]);
            Stock::factory()->create([
                'product_id' => $product->id,
                'branch_id' => $branch->id,
                'quantity' => 10,
            ]);
        }

        $result = $this->aiService->recommendations([
            'customerId' => $customer->id,
            'maxResults' => 3,
        ]);

        $this->assertCount(3, $result['items']);
        $this->assertStringContainsString('favorite categories', $result['items'][0]['reason']);
    }

    public function test_collaborative_filtering_finds_co_purchases(): void
    {
        $category = Category::factory()->create();
        $branch = Branch::factory()->create();

        // Create products
        $product1 = Product::factory()->create(['category_id' => $category->id]);
        $product2 = Product::factory()->create(['category_id' => $category->id]);
        $product3 = Product::factory()->create(['category_id' => $category->id]);

        // Create co-purchase relationship
        $order = Order::factory()->create();
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product1->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product2->id]);

        // Stock products
        foreach ([$product1, $product2, $product3] as $product) {
            Stock::factory()->create([
                'product_id' => $product->id,
                'branch_id' => $branch->id,
                'quantity' => 10,
            ]);
        }

        $result = $this->aiService->recommendations([
            'productId' => $product1->id,
            'maxResults' => 3,
        ]);

        $this->assertGreaterThan(0, count($result['items']));
        // Verify product2 appears (co-purchased with product1)
        $recommendedIds = array_column($result['items'], 'productId');
        $this->assertContains($product2->id, $recommendedIds);
    }

    public function test_visual_search_includes_metadata(): void
    {
        $result = $this->aiService->visualSearch([
            'imageUrl' => 'https://example.com/image.jpg',
            'maxResults' => 3,
        ]);

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('visualSearchMetadata', $result);
        $this->assertTrue($result['visualSearchMetadata']['imageProcessed']);
    }

    public function test_assistant_generates_contextual_replies(): void
    {
        $result = $this->aiService->assistant([
            'message' => 'I need a modern bedroom wallpaper in blue',
        ]);

        $this->assertArrayHasKey('reply', $result);
        $this->assertArrayHasKey('followUps', $result);
        $this->assertArrayHasKey('conversationId', $result);
        $this->assertStringContainsString('bedroom', strtolower($result['reply']));
    }

    public function test_assistant_handles_empty_message(): void
    {
        $result = $this->aiService->assistant(['message' => '']);

        $this->assertStringContainsString('room style', $result['reply']);
    }

    public function test_translate_draft_includes_metadata(): void
    {
        $result = $this->aiService->translateDraft([
            'text' => 'Hello world',
            'sourceLocale' => 'en',
            'targetLocale' => 'es',
        ]);

        $this->assertArrayHasKey('translatedText', $result);
        $this->assertArrayHasKey('qualityHint', $result);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertContains('es', $result['translatedText']);
    }
}
