<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Purchase;
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
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        return DB::transaction(function () use ($request) {
            // 1. Record the Purchase
            $purchase = Purchase::create([
                'branch_id' => $request->branch_id,
                'total_amount' => $request->total_amount,
                'user_id' => auth()->id(),
                'reference_number' => 'PO-' . strtoupper(Str::random(8))
            ]);

            foreach ($request->items as $item) {
                // 2. Link items to the PO
                $purchase->items()->create($item);

                // 3. Use your StockService to increase inventory
                // This will automatically create the 'PURCHASE' StockLog entry
                $this->stockService->increase(
                    $request->branch_id, 
                    $item['product_id'], 
                    $item['quantity']
                );
            }

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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
