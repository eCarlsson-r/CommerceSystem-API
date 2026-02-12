<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\WasteLog;
use App\Services\StockService;
use App\Http\Resources\PurchaseReturnItemResource;

class ReturnController extends Controller
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
        $returns = PurchaseReturnItem::with('product', 'purchaseReturn.branch', 'purchaseReturn.supplier')->get();
        return PurchaseReturnItemResource::collection($returns);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        return DB::transaction(function () use ($request) {
            $return = PurchaseReturn::create([
                'return_number' => 'RET-' . strtoupper(Str::random(8)),
                'supplier_id' => $request->supplier_id,
                'branch_id' => $request->branch_id,
                'reason' => $request->reason,
                'total_amount' => $request->total_amount,
                'user_id' => auth()->id(),
                'return_date' => date('Y-m-d')
            ]);

            foreach ($request->items as $item) {
                $return->items()->create($item);

                if ($item['condition'] === 'good') {
                    // Return to sellable stock
                    $this->stockService->increase(
                        $request->branch_id,
                        $item['product_id'],
                        $item['quantity'],
                        'RETURN_RESTOCK'
                    );
                }/* else {
                    // Record as waste - No stock increase
                    WasteLog::create([
                        'branch_id' => $request->branch_id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'reason' => 'Damaged Return: ' . $request->reason
                    ]);
                }*/
            }
            return response()->json(['message' => 'Return processed successfully']);
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
