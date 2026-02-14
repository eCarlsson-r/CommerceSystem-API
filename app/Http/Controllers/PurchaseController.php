<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\PurchaseOrder;
use App\Services\StockService;

class PurchaseController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService) {
        $this->stockService = $stockService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return PurchaseOrder::with('items.product', 'supplier', 'branch')->where('status', 'pending')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        return DB::transaction(function () use ($request) {
            // 1. Record the Purchase
            $purchase = PurchaseOrder::create([
                'supplier_id' => $request->supplier_id,
                'branch_id' => $request->branch_id,
                'total_amount' => $request->total_amount,
                'user_id' => auth()->id(),
                'order_date' => $request->order_date,
                'expected_date' => $request->expected_date,
                'order_number' => 'PO-' . strtoupper(Str::random(8))
            ]);

            $purchase->items()->createMany($request->items);

            return response()->json(['message' => 'Stock updated successfully']);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $purchase = PurchaseOrder::findOrFail($id);
        $purchase->update([
            'status' => $request->status,
        ]);

        foreach ($purchase->items as $item) {
            // 3. Use your StockService to increase inventory
            // This will automatically create the 'PURCHASE' StockLog entry
            $this->stockService->increase(
                $purchase->branch_id, 
                $item->product_id, 
                $item->quantity,
                $purchase->order_number
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
