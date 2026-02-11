<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Stock;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\ProductResource;
use App\Events\ProductUpdated;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('category', 'stocks');

        if ($request->has('category')) {
            $products->where('category_id', $request->category);
        }

        if ($request->has('branch_id')) {
            $products->whereHas('stocks', function ($query) use ($request) {
                $query->where('branch_id', $request->branch_id);
            });
        }

        return response()->json($products->get());
    }

    public function store(Request $request) 
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validate([
                'name' => 'required|string',
                'sku' => 'required|unique:products,sku',
                'category_id' => 'required|exists:categories,id',
                'base_price' => 'required|numeric',
                'min_stock_alert' => 'required|integer',
                'images.*' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
            ]);

            $product = Product::create($validated);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('products/gallery', 'public');
                    $product->media()->create(['path' => $path]);
                }
            }

            $branchIds = Branch::pluck('id');

            foreach ($branchIds as $branchId) {
                $product->stocks()->create([
                    'branch_id' => $branchId,
                    'quantity' => 0,
                    'purchase_price' => 0,
                    'sale_price' => $validated['base_price'],
                    'min_stock_level' => $validated['min_stock_alert']
                ]);
            }

            return new ProductResource($product->load('media'));
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

    public function lowStock(Request $request)
    {
        $query = Stock::with(['product', 'branch'])
            ->whereColumn('quantity', '<=', 'min_stock_level');

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        } else if ($request->has('scoped_branch_id')) {
            $query->where('branch_id', $request->scoped_branch_id);
        }

        return response()->json($query->get());
    }
}