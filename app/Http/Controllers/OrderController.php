<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Stock;
use App\Models\StockLog;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->status === "processing" || $request->status === "shipped") {
            $branchId = $request->branch_id;
            return Order::with('items.product', 'branch', 'customer')->where('branch_id', $branchId)->where('status', $request->status)->get();
        }
        return Order::with('items.product', 'customer')->where('status', $request->status)->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    public function processOrder(Request $request, $orderId) {
        return DB::transaction(function () use ($request, $orderId) {
            $order = Order::lockForUpdate()->findOrFail($orderId);

            if ($order->status !== 'pending') {
                return response()->json(['message' => 'Order already taken by another branch'], 422);
            }

            // Assign to current branch and update status
            $order->update([
                'status' => 'processing',
                'branch_id' => $request->branch_id, // The branch that clicked "Process"
                'processed_at' => now()
            ]);

            // Deduct Stock from THIS branch
            foreach ($order->items as $item) {
                $stock = Stock::where('branch_id', $request->branch_id)
                            ->where('product_id', $item->product_id)
                            ->first();
                
                if (!$stock || $stock->quantity < $item->quantity) {
                    throw new \Exception("Insufficient stock at this branch.");
                }

                $stock->decrement('quantity', $item->quantity);
                
                // Log for the Stock History we built
                StockLog::create([
                    'stock_id' => $stock->id,
                    'type' => 'OUT',
                    'change' => -$item->quantity,
                    'description' => "E-comm Fulfillment #{$order->order_number}"
                ]);
            }

            return response()->json(['message' => 'Order successfully assigned to your branch']);
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }

    public function finalizeShipment(Request $request, $orderId) {
        $order = Order::findOrFail($orderId);
        
        return DB::transaction(function() use ($order, $request) {
            // 1. Update Order status
            $order->update([
                'status' => 'shipped',
                'tracking_number' => $request->tracking_number,
                'courier_service' => $request->courier_service
            ]);

            // 2. Generate Final Sale record for the Ledger
            $sale = Sale::create([
                'branch_id' => $order->branch_id,
                'employee_id' => $request->user()->id,
                'customer_id' => $order->customer_id,
                'date' => now(),
                'grand_total' => $order->total_amount,
                'payment_method' => 'ONLINE_GATEWAY',
            ]);

            // 3. Link back
            $order->update(['sale_id' => $sale->id]);

            return response()->json(['message' => 'Shipment finalized and sale recorded']);
        });
    }
}
