<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index()
    {
        return Stock::with('product')->get();
    }

    public function store(Request $request)
    {
        $stock = Stock::create([
            'product_id' => $request->product_id,
            'branch_id' => $request->branch_id,
            'quantity' => $request->quantity,
            'purchase_price' => $request->purchase_price,
            'sale_price' => $request->sale_price,
            'discount_percent' => $request->discount_percent
        ]);

        return response()->json($stock);
    }

    public function update(Request $request, $id)
    {
        $stock = Stock::findOrFail($id);
        $stock->update($request->all());

        return response()->json($stock);
    }

    public function destroy($id)
    {
        $stock = Stock::findOrFail($id);
        $stock->delete();

        return response()->json($stock);
    }
}
