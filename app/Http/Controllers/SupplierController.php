<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Supplier::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'contact_person' => 'required|string',
            'tax_id' => 'required|string',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'phone' => 'required'
        ]);
        return Supplier::create($validated);
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        return $supplier;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'contact_person' => 'required|string',
            'tax_id' => 'required|string',
            'phone' => 'required',
            'email' => 'nullable|email',
            'address' => 'nullable|string'
        ]);
        return $supplier->update($validated);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        return $supplier->delete();
    }
}
