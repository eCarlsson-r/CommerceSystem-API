<?php
namespace App\Services;

use App\Models\Stock;
use App\Models\StockLog;
use Illuminate\Support\Facades\DB;
use Exception;

class StockService
{
    /**
     * Decrease stock for a specific branch and record the movement.
     */
    public function decrease(int $branchId, int $productId, int $quantity, string $reference = 'SALE')
    {
        // 1. Locate the specific branch's stock record
        $stock = Stock::where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->first();

        if (!$stock) {
            throw new Exception("Product ID {$productId} is not registered in Branch ID {$branchId}.");
        }

        if ($stock->quantity < $quantity) {
            throw new Exception("Insufficient stock in this branch. Available: {$stock->quantity}");
        }

        // 2. Atomic Decrement (Safe for high-concurrency)
        $stock->decrement('quantity', $quantity);
        $newBalance = $stock->fresh()->quantity;

        // 3. Record to the Stock Ledger (Kartu Stok)
        // This is essential for your 11-branch audit trail
        StockLog::create([
            'stock_id'        => $stock->id,
            'reference_id'    => $referenceId,
            'type'            => $type,
            'description'     => "Sold {$quantity} units via {$referenceId}",
            'quantity_change' => -$quantity, // Negative for 'GET' logic
            'balance_after'   => $newBalance,
            'user_id'         => auth()->id()
        ]);
    }

    public function increase(int $branchId, int $productId, int $quantity, string $reference = 'PURCHASE')
    {
        // 1. Find or Create Stock Record
        $stock = Stock::firstOrCreate(
            ['branch_id' => $branchId, 'product_id' => $productId],
            ['quantity' => 0]
        );

        // 2. Atomic Increment
        $stock->increment('quantity', $quantity);

        // 3. Record to Stock Ledger
        StockLog::create([
            'stock_id'        => $stock->id,
            'reference_id'    => $referenceId,
            'type'            => $type,
            'description'     => "Received {$quantity} units via {$referenceId}",
            'quantity_change' => $quantity, // Positive for 'GET' logic
            'balance_after'   => $newBalance,
            'user_id'         => auth()->id()
        ]);
    }
}