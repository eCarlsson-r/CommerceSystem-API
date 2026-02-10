<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request) {
        $query = Stock::query();

        if ($request->has('scoped_branch_id')) {
            $query->where('branch_id', $request->scoped_branch_id);
        }

        return $query->get();
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

    public function receiveTransfer($id) {
        return DB::transaction(function () use ($id) {
            $transfer = StockTransfer::findOrFail($id);
            
            foreach ($transfer->items as $item) {
                $this->stockService->increase(
                    $transfer->to_branch_id, 
                    $item->product_id, 
                    $item->quantity,
                    'TRANSFER_IN'
                );
            }
            
            $transfer->update(['status' => 'R']);
        });
    }
}
