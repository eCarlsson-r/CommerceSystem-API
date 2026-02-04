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

    public function store(Request $request) 
    {
        return DB::transaction(function () use ($request) {
            // 1. Validate (handling the 'images' array from Angular)
            $validated = $request->validate([
                'name' => 'required|string',
                'sku' => 'required|unique:products,sku',
                'category_id' => 'required|exists:categories,id',
                'base_price' => 'required|numeric',
                'min_stock_alert' => 'required|integer',
                'images.*' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
            ]);

            // 2. Create the Universal Product
            $product = Product::create($validated);

            // 3. Handle Multiple Images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('products/gallery', 'public');
                    $product->images()->create(['path' => $path]);
                }
            }

            // 4. THE 11-BRANCH AUTO-SETUP
            // Get all branch IDs (Medan, Binjai, etc.)
            $branchIds = Branch::pluck('id');

            foreach ($branchIds as $branchId) {
                $product->stocks()->create([
                    'branch_id' => $branchId,
                    'quantity' => 0, // Starts at zero
                    'min_stock_level' => $validated['min_stock_alert'] // Uses your form value
                ]);
            }

            return new ProductResource($product->load('images'));
        });
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
