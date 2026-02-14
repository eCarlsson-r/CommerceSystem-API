<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockLog;
use App\Models\Customer;
use App\Services\StockService; // Import your new service
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\SaleResource;

class SaleController extends Controller
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
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $invoice_number = 'INV-' . time();
            $discount_amount = $request->manual_discount + ($request->applied_points * 100);
            // 1. Create the Sale
            $sale = Sale::create([
                'date' => date('Y-m-d'),
                'invoice_number' => $invoice_number,
                'branch_id' => $request->branch_id,
                'employee_id' => auth()->id(),
                'customer_id' => $request->customer_id ?? 1,
                'subtotal' => $request->subtotal,
                'tax_amount' => $request->tax_amount ?? 0,
                'manual_discount' => $request->manual_discount,
                'applied_points' => $request->applied_points,
                'discount_amount' => $discount_amount,
                'grand_total' => $request->grand_total,
                'status' => 'completed'
            ]);

            // 2. Save Items using the relationship
            foreach ($request->items as $item) {
                $stock = Stock::where('branch_id', $request->branch_id)
                 ->where('product_id', $item['product_id'])
                 ->first();

                $sale->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'sale_price' => $item['price'],
                    'purchase_price' => $stock->purchase_price,
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'total_price' => $item['total_price'] ?? $item['quantity'] * $item['price'],
                ]);
            }

            // 3. Save Payments using the relationship
            $sale->payments()->createMany($request->payments);
            $customer = Customer::find($request->customer_id);
            if ($customer) {
                $customer->points -= $request->applied_points;
                $customer->save();
            }

            // 4. Update Stocks & Record to StockLog (Kartu Stok)
            foreach ($request->items as $item) {
                $this->stockService->decrease(
                    $request->branch_id, 
                    $item['product_id'], 
                    $item['quantity'],
                    $invoice_number,
                    'SALE'
                );
            }

            return new SaleResource($sale->load(['branch', 'employee', 'customer', 'items', 'payments']));
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sale $sale)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sale $sale)
    {
        //
    }

    public function recent()
    {
        return Sale::with(['branch:id,name', 'customer:id,name'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'branch' => $sale->branch->name,
                    'customer' => $sale->customer->name ?? 'Guest',
                    'grand_total' => $sale->grand_total,
                    'time' => $sale->created_at->diffForHumans(),
                    'payments' => $sale->payments,
                ];
            });
    }
}
