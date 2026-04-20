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
            'image_url' => 'required|string|max:2048',
            'tile_scale' => 'nullable|numeric|min:0.1|max:10',
            'blend_intensity' => 'nullable|numeric|min:0|max:1',
            'wall_polygon' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        $preview = Preview::create([
            'customer_id' => optional($request->user()?->customer)->id,
            'product_id' => $payload['product_id'],
            'image_url' => $payload['image_url'],
            'tile_scale' => $payload['tile_scale'] ?? 1,
            'blend_intensity' => $payload['blend_intensity'] ?? 0.7,
            'wall_polygon' => $payload['wall_polygon'] ?? null,
            'metadata' => array_merge($payload['metadata'] ?? [], [
                'client_preview_id' => $payload['preview_id'] ?? null,
            ]),
        ]);

        return response()->json([
            'preview_id' => $preview->id,
            'message' => 'Preview saved',
        ], 201);
    }

    public function attachToCart(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'preview_id' => 'required|exists:previews,id',
            'cart_id' => 'required|exists:carts,id',
        ]);

        $preview = Preview::findOrFail($payload['preview_id']);
        $preview->update(['cart_id' => $payload['cart_id']]);

        return response()->json([
            'message' => 'Preview attached to cart',
            'preview' => $preview,
        ]);
    }
}
