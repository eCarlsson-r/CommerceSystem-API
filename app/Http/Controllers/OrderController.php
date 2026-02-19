<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Stock;
use App\Models\StockLog;
use App\Models\Sale;
use App\Http\Resources\OrderResource;

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
        DB::beginTransaction();
        try {
            $order = Order::create([
                'user_id' => auth()->id(),
                'total_price' => $request->total,
                'status' => 'PENDING',
                'type' => $request->type, // shipping or pickup
                'branch_name' => $request->details['branch_name'] ?? 'Main Warehouse',
            ]);

            foreach ($request->items as $item) {
                // Find the stock for this specific product
                $stock = Stock::where('product_id', $item['id'])->first();

                if ($stock->quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for {$item['name']}");
                }

                // Record the Stock Log
                $stock->logs()->create([
                    'quantity_change' => $item['quantity'],
                    'type' => 'OUT',
                    'description' => "E-commerce Order #{$order->id}"
                ]);

                $stock->decrement('quantity', $item['quantity']);
            }

            DB::commit();
            return response()->json(['order_id' => $order->id], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        //
    }

    public function release(Request $request, $orderId) {
        $order = Order::findOrFail($orderId);
        // Only allow releasing if it's currently in 'processing'
        if ($order->status !== 'processing') {
            return response()->json(['message' => 'Order cannot be released'], 422);
        }

        $order->update([
            'status' => 'paid',
            'branch_id' => null, // Remove branch assignment
            'processed_at' => null
        ]);

        return response()->json(['message' => 'Order released to global pool']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $order = Order::with('items.product', 'customer', 'branch')->lockForUpdate()->findOrFail($id);
            $oldStatus = $order->status;
            $newStatus = $request->status;

            // 1. Processing (New -> Processing)
            if ($newStatus === 'processing' && $oldStatus === 'new') {
                if ($order->status !== 'new' && $order->status !== 'pending' && $order->status !== 'paid') {
                     return response()->json(['message' => 'Order already taken or invalid status'], 422);
                }

                $sale = Sale::create([
                    'invoice_number' => $order->order_number,
                    'branch_id' => $request->branch_id,
                    'employee_id' => auth()->id(),
                    'customer_id' => $order->customer_id,
                    'date' => now(),
                    'subtotal' => $order->items->sum('total_amount'),
                    'grand_total' => $order->total_amount,
                    'payment_method' => 'E-PAYMENT',
                    'note' => 'E-comm Order #' . $order->order_number
                ]);

                $order->update([
                    'status' => 'processing',
                    'branch_id' => $request->branch_id,
                    'processed_at' => now()
                ]);

                foreach ($order->items as $item) {
                     $stock = Stock::where('branch_id', $request->branch_id)
                                 ->where('product_id', $item->product_id)
                                 ->first();

                     if (!$stock || $stock->quantity < $item->quantity) {
                         throw new \Exception("Insufficient stock at this branch.");
                     }

                     $sale->items()->create([
                         'product_id' => $item->product_id,
                         'quantity' => $item->quantity,
                         'sale_price' => $item->unit_price,
                         'purchase_price' => $stock->purchase_price,
                         'total_price' => $item->total_price
                     ]);

                     $stock->decrement('quantity', $item->quantity);

                     StockLog::create([
                         'stock_id' => $stock->id,
                         'type' => 'sale',
                         'reference_id' => $order->order_number,
                         'quantity_change' => -$item->quantity,
                         'balance_after' => $stock->fresh()->quantity,
                         'description' => "E-comm Fulfillment #{$order->order_number}",
                         'user_id' => auth()->id()
                     ]);
                }
            }
            // 2. Shipping (Processing -> Shipped)
            elseif ($newStatus === 'shipped' && $oldStatus === 'processing') {
                $request->validate([
                    'tracking_number' => 'required|string',
                    'courier_service' => 'required|string',
                ]);

                $order->update([
                    'status' => 'shipped',
                    'tracking_number' => $request->tracking_number,
                    'courier_service' => $request->courier_service,
                    'shipped_at' => now()
                ]);
            }
            // 3. Releasing (Processing -> New)
            elseif ($newStatus === 'new' && $oldStatus === 'processing') {
                 foreach ($order->items as $item) {
                    $stock = Stock::where('branch_id', $order->branch_id)
                                ->where('product_id', $item->product_id)
                                ->first();
                    if ($stock) {
                        $stock->increment('quantity', $item->quantity);
                    }
                }
                $order->update([
                    'status' => 'new', // or 'paid' depending on your flow, keeping 'new' based on context
                    'branch_id' => null,
                    'processed_at' => null
                ]);
            }
            // 4. Other updates (Notes, etc.)
            else {
                if ($request->has('cancel_reason')) {
                    $order->note = $request->cancel_reason;
                }
                // Allow generic status updates if needed, or restrict strict transitions
                if ($newStatus && $newStatus !== $oldStatus) {
                     $order->status = $newStatus;
                }
                $order->save();
            }

            return new OrderResource($order->fresh(['items.product', 'customer', 'branch']));
        });
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }
}
