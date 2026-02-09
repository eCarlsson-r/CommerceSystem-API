<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::with('products')->get();
        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $category = Category::create($request->all());
        return response()->json($category);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return response()->json($category);
    }

    public function update(Request $request, Category $category) {
        $category->update($request->validate(['name' => 'required|string']));
        return $category;
    }

    public function destroy(Category $category) {
        // Check if category has products before deleting
        if ($category->products()->exists()) {
            return response()->json(['message' => 'Cannot delete category with products'], 422);
        }
        $category->delete();
        return response()->noContent();
    }
}
