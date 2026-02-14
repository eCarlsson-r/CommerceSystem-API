<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function syncCart(Request $request) {
        // Validates stock at the 'Medan Main' branch before allowing add
        $stock = Stock::where('branch_id', 1)->where('product_id', $request->id)->first();
        if($stock->quantity < $request->qty) return response()->json(['error' => 'Stock Habis'], 422);
        
        // Update logic...
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    public function addToCart(Request $request) {
        $stock = Stock::where('branch_id', $request->preferred_branch)
                    ->where('product_id', $request->product_id)
                    ->first();

        if ($stock->quantity < $request->quantity) {
            return response()->json(['message' => 'Insufficient stock at this branch'], 422);
        }

        // Update or create cart record
        Cart::updateOrCreate([
            'customer_id' => $request->customer_id,
            'product_id' => $request->product_id,
            'quantity' => $request->quantity
        ]);

        return response()->json(['message' => 'Item added to cart']);
    }

    /**
     * Display the specified resource.
     */
    public function show(Cart $cart)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cart $cart)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cart $cart)
    {
        //
    }
}
