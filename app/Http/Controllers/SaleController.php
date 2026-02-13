<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\StockService; // Import your new service
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            // 1. Create the Sale
            $sale = Sale::create([
                'invoice_number' => 'INV-' . time(),
                'branch_id' => $request->branch_id,
                'employee_id' => auth()->id(),
                'subtotal' => $request->subtotal,
                'tax_amount' => $request->tax_amount,
                'discount_amount' => $request->discount_amount,
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
                    'discount_amount' => $item['discount_amount'],
                    'total_price' => $item['total_price'],
                ]);
            }

            // 3. Save Payments using the relationship
            $sale->payments()->createMany($request->payments);

            // 4. Update Stocks & Record to StockLog (Kartu Stok)
            foreach ($request->items as $item) {
                $this->stockService->decrease($request->branch_id, $item['product_id'], $item['quantity']);
            }

            return new SaleResource($sale->load(['items', 'payments']));
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
