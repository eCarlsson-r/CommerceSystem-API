<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Stock;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductCardResource;
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

        if ($request->has('search')) {
            $search = $request->search;
            $products->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        return response()->json($products->get());
    }

    public function show($id)
    {
        $stock = Stock::with('product.category', 'product.media', 'logs')
            ->whereHas('product', function($q) use ($id) {
                $q->where('id', $id);
            })
            ->firstOrFail();
        return response()->json([
            'product' => new ProductCardResource($stock),
            'stocks' => $stock->product->stocks->map(fn($s) => [
                'branch' => $s->branch,
                'quantity' => $s->quantity
            ])
        ]);
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

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('products/gallery', 'public');
                    $product->media()->create([
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType(),
                        'extension' => $file->getClientOriginalExtension(),
                        'size' => $file->getSize(),
                        'disk' => 'public',
                        'path' => $path
                    ]);
                }
            }

            return new ProductResource($product->load('media'));
        });
    }

    public function update(Request $request, Product $product) {
        return DB::transaction(function () use ($request, $product) {
            $product->update($request->all());

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('products/gallery', 'public');
                    $product->media()->create([
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType(),
                        'extension' => $file->getClientOriginalExtension(),
                        'size' => $file->getSize(),
                        'disk' => 'public',
                        'path' => $path
                    ]);
                }
            }

            // Instant update for POS and eCommerce
            broadcast(new ProductUpdated($product))->toOthers();

            return new ProductResource($product->load('media'));
        });
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

    public function getProducts(Request $request)
    {
        $limit = $request->get('limit', 12);
        $filter = $request->get('filter'); // best-seller, new-arrival

        $query = Stock::with(['product.media', 'product.category', 'logs'])
            ->where('quantity', '>', 0);

        // 1. Contextual Filters (Best Seller / New Arrival)
        if ($filter === 'best-seller') {
            $query->withCount(['logs' => function($q) {
                $q->where('type', 'OUT')->where('created_at', '>=', now()->subDays(30));
            }])->orderBy('logs_count', 'desc');
        } elseif ($filter === 'new-arrival') {
            $query->latest();
        }

        // 2. User-Driven Filters (Sidebar & Search)
        if ($request->filled('category')) {
            $query->whereHas('product.category', function($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        $minBound = Stock::where('quantity', '>', 0)->min('sale_price') ?? 0;
        $maxBound = Stock::where('quantity', '>', 0)->max('sale_price') ?? 10000000;

        if ($request->filled('min_price')) {
            $query->where('sale_price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('sale_price', '<=', $request->max_price);
        }

        if ($request->filled('search')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        // 3. Execution & Uniqueness
        // We fetch a bit more than the limit to ensure unique product_id after grouping
        $stocks = $query->take($limit * 3)->get();

        $products = $stocks->unique('product_id')
            ->take($limit)
            ->map(function($stock) {
                return new ProductCardResource($stock);
            });

        return response()->json([
            'products' => $products->values(),
            'min_price' => $minBound,
            'max_price' => $maxBound,
        ]);
    }
}
