<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReturnController extends Controller
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
    public function store(Request $request) {
        return DB::transaction(function () use ($request) {
            $return = ProductReturn::create([
                'sale_id' => $request->sale_id,
                'branch_id' => $request->branch_id,
                'reason' => $request->reason,
                'user_id' => auth()->id()
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
                } else {
                    // Record as waste - No stock increase
                    WasteLog::create([
                        'branch_id' => $request->branch_id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'reason' => 'Damaged Return: ' . $request->reason
                    ]);
                }
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
