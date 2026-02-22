<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Stock;
use App\Models\StockLog;
use App\Models\Sale;
use App\Services\StockService; // Import your new service
use App\Http\Resources\OrderResource;
use PDF;
use App\Events\OrderCreated;

class OrderController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService) {
        $this->stockService = $stockService;
    }
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

    public function myOrders(Request $request) {
        return OrderResource::collection(Order::with('items.product', 'branch')->where('customer_id', $request->user()->customer->id)->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->type === 'pickup') {
            foreach ($request->items as $item) {
                $stock = Stock::where('product_id', $item['id'])->where('branch_id', $item['branch']['id'])->first();

                // Check if the stock actually belongs to the requested branch
                if ($stock->branch_id !== $request->delivery_details['pickup_branch']) {
                    return response()->json([
                        'message' => "Item {$item['name']} is not available."
                    ], 422);
                }
            }
        }

        $invoice_number = 'INV-' . time();

        DB::beginTransaction();
        try {
            $order = Order::create([
                'customer_id' => auth()->user()->customer->id,
                'order_number' => $invoice_number,
                'total_amount' => $request->total,
                'status' => 'pending',
                'type' => $request->type, // shipping or pickup
                'shipping_address' => $request->type === 'shipping' ? $request->delivery_details['address'] : null,
                'courier_service' => $request->type === 'shipping' ? $request->delivery_details['courier_service'] : null,
                'branch_id' => $request->delivery_details['pickup_branch'] ?? null,
            ]);

            $order->items()->createMany(array_map(function ($item) {
                return [
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'total_price' => $item['price'] * $item['quantity']
                ];
            }, $request->items));

            foreach ($request->items as $item) {
                $this->stockService->decrease(
                    $item['branch']['id'],
                    $item['id'],
                    $item['quantity'],
                    $invoice_number,
                    'SALE'
                );
            }

            DB::commit();
            event(new OrderCreated($order));
            return response()->json(['order_id' => $order->id], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Ensure the user can only see their own order
        $order = Order::where('customer_id', auth()->user()->customer->id)
                    ->with(['branch', 'items.product.media'])
                    ->findOrFail($id);

        return response()->json($order);
    }

    // app/Http/Controllers/Api/OrderController.php

    public function invoice($id)
    {
        $order = Order::with(['items.product', 'customer'])->findOrFail($id);

        // Safety check: Only owner or admin can view
        if ($order->customer_id !== auth()->user()->customer->id) abort(403);

        $data = [
            'order' => new OrderResource($order),
            'logo' => public_path('images/logo-text.png'),
            'date' => $order->created_at->format('d M Y'),
        ];

        // You can return a simple HTML view or a PDF
        $pdf = PDF::loadView('invoices.order', $data);
        return $pdf->stream("Invoice-{$order->id}.pdf");
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

    public function cancel($id)
    {
        DB::transaction(function () use ($id) {
            $order = Order::findOrFail($id);

            if ($order->status === 'cancelled') return;

            foreach ($order->items as $item) {
                // Find the stock for the SPECIFIC branch used in the order
                $stock = Stock::where('product_id', $item->product_id)
                            ->where('branch_id', $item->branch_id) // Important!
                            ->first();

                if ($stock) {
                    // 1. Return the items to inventory
                    $stock->increment('quantity', $item->quantity);

                    // 2. Log it as a 'return' type (per your Enum)
                    StockLog::create([
                        'stock_id' => $stock->id,
                        'quantity' => $item->quantity,
                        'type' => 'return',
                        'note' => "Restocked from Cancelled Order #{$order->id}",
                        'user_id' => auth()->id()
                    ]);
                }
            }

            if ($request->has('cancel_reason')) {
                $order->update(['status' => 'cancelled', 'note' => $request->cancel_reason]);
            } else {
                $order->update(['status' => 'cancelled']);
            }
        });

        return response()->json(['message' => 'Order cancelled and stock returned.']);
    }
}
