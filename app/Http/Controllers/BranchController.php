<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Branch::all();
    }

    // app/Http/Controllers/Api/BranchController.php
    public function publicIndex()
    {
        // Fetch unique branch names from your Stock table
        return response()->json(
            Branch::whereHas('stocks', function($q) {
                $q->where('quantity', '>', 0);
            })->get(['id', 'name', 'address', 'phone', 'hours'])
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required'
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('branches');
        }

        return Branch::create($validated);
    }

    /**
     * Display the specified resource.
     */
    public function show(Branch $branch)
    {
        return $branch;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required'
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('branches');
        }

        $branch->update($validated);
        return $branch;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch)
    {
        return $branch->delete();
    }
}
