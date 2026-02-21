<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Stock;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user()->load('customer');
        $localItems = $request->input('items', []); // Items from LocalStorage

        foreach ($localItems as $item) {
            // Update or Create: If item from that branch exists, update quantity, else create
            Cart::updateOrCreate(
                [
                    'customer_id' => $user->customer->id,
                    'product_id' => $item['id'],
                    'branch_id' => $item['branch_id'],
                ],
                ['quantity' => $item['quantity']]
            );
        }

        // Return the full merged cart from DB
        return response()->json($user->cartItems()->with('product')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user()->load('customer');
        $stock = Stock::where('branch_id', $request->branch_id)
                    ->where('product_id', $request->product_id)
                    ->first();

        if ($stock->quantity < $request->quantity) {
            return response()->json(['message' => 'Insufficient stock at this branch'], 422);
        }

        // Update or create cart record
        Cart::updateOrCreate(
            [
                'customer_id' => $user->customer->id,
                'product_id' => $request->product_id,
                'branch_id' => $request->branch_id
            ],
            ['quantity' => $request->quantity]
        );

        return response()->json(['message' => 'Item added to cart', 'cart' => $user->cartItems()->with('product')->get()]);
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
