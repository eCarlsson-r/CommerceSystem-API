<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
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
                'grand_total' => $request->grand_total,
                // ... totals
            ]);

            // 2. Save Items using the relationship
            $sale->items()->createMany($request->items);

            // 3. Save Payments using the relationship
            $sale->payments()->createMany($request->payments);

            // 4. Update Stocks & Record to StockLog (Kartu Stok)
            foreach ($request->items as $item) {
                $this->stockService->decrease($item['product_id'], $item['quantity']);
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
}
