<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // List for both Next.js and Angular
    public function index(Request $request)
    {
        $products = Product::with('category')
            ->when($request->category, fn($q) => $q->where('category_id', $request->category))
            ->paginate(12);
            
        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string|max:2048'
        ]);

        $product = Product::create($validated);
        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product) {
        $product->update($request->all());

        // Instant update for POS and eCommerce
        broadcast(new ProductUpdated($product))->toOthers();

        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json($product);
    }
}
