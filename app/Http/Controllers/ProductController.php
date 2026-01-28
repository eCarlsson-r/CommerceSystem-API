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

        if ($request->has('branch_id')) {
            $products->where('branch_id', $request->branch_id);
        }

        return response()->json($products);
    }

    public function store(Request $request) {
        $product = Product::create($request->except('images'));

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $product->images()->create(['path' => $path]);
            }
        }
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
