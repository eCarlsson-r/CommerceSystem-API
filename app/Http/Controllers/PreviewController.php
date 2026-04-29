<?php

namespace App\Http\Controllers;

use App\Models\Preview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreviewController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'preview_id' => 'nullable|string|max:100',
            'product_id' => 'required|exists:products,id',
            'room_dimensions' => 'required|array',
            'room_dimensions.width' => 'required|numeric|min:0.1|max:50',
            'room_dimensions.height' => 'required|numeric|min:0.1|max:20',
            'room_dimensions.depth' => 'required|numeric|min:0.1|max:50',
            'selected_wall' => 'required|in:front,back,left,right,all',
            'tile_scale' => 'nullable|numeric|min:0.1|max:10',
            'pattern_repeat' => 'nullable|numeric|min:0|max:200',
            'wall_coverage' => 'nullable|array',
            'wall_coverage.*.wall' => 'required|in:front,back,left,right,all',
            'wall_coverage.*.width' => 'required|numeric',
            'wall_coverage.*.height' => 'required|numeric',
            'wall_coverage.*.rolls_needed' => 'required|numeric|min:1',
        ]);

        $preview = Preview::create([
            'customer_id' => optional($request->user()?->customer)->id,
            'product_id' => $payload['product_id'],
            'room_dimensions' => $payload['room_dimensions'],
            'selected_wall' => $payload['selected_wall'],
            'tile_scale' => $payload['tile_scale'] ?? 1,
            'pattern_repeat' => $payload['pattern_repeat'] ?? 53,
            'wall_coverage' => $payload['wall_coverage'] ?? null,
            'metadata' => [
                'client_preview_id' => $payload['preview_id'] ?? null,
            ],
        ]);

        return response()->json([
            'preview_id' => $preview->id,
            'message' => 'Room preview saved',
        ], 201);
    }

    public function attachToCart(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'preview_id' => 'required|string|max:100',
            'product_id' => 'required|exists:products,id',
            'preview_url' => 'required|string|max:2048',
            'wall_coverage' => 'nullable|array',
        ]);

        // Find or create a cart item for this product and user
        $customerId = optional($request->user()?->customer)->id;

        if (!$customerId) {
            return response()->json([
                'message' => 'Preview attachment queued for guest user',
                'preview_id' => $payload['preview_id'],
            ]);
        }

        // Find existing cart item for this product
        $cartItem = \App\Models\Cart::where('customer_id', $customerId)
            ->where('product_id', $payload['product_id'])
            ->first();

        if ($cartItem) {
            $cartItem->update([
                'preview_id' => $payload['preview_id'],
                'preview_url' => $payload['preview_url'],
                'wall_coverage' => $payload['wall_coverage'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Room preview attached to cart',
            'preview_id' => $payload['preview_id'],
        ]);
    }
}
