<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Stock;
use App\Models\StockLog;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;

class StockLogSeeder extends Seeder
{
    public function run(): void
    {
        $adminUsers = User::whereIn('role', ['admin', 'manager'])->pluck('id');
        if ($adminUsers->isEmpty()) {
            $adminUsers = collect([User::first()->id]);
        }

        // 1. Process Sales
        $sales = Sale::with('items')->get();
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                $stock = Stock::where('branch_id', $sale->branch_id)
                    ->where('product_id', $item->product_id)
                    ->first();

                if ($stock) {
                    $newBalance = $stock->quantity; // Current quantity is already set by StockSeeder, but we should probably recalculate it.
                    // Actually, let's just create the log and we'll fix the Stock quantity at the end based on logs.

                    StockLog::create([
                        'stock_id' => $stock->id,
                        'reference_id' => $sale->invoice_number,
                        'type' => 'sale',
                        'description' => "Item sold via {$sale->invoice_number}",
                        'quantity_change' => -$item->quantity,
                        'balance_after' => 0, // Will update later
                        'user_id' => $adminUsers->random(),
                        'created_at' => $sale->date,
                        'updated_at' => $sale->date,
                    ]);
                }
            }
        }

        // 2. Process Completed Purchase Orders
        $purchases = PurchaseOrder::with('items')->where('status', 'completed')->get();
        foreach ($purchases as $po) {
            // Purchases go to branch 1 by default for this mock data
            foreach ($po->items as $item) {
                $stock = Stock::where('branch_id', '1')
                    ->where('product_id', $item->product_id)
                    ->first();

                if ($stock) {
                    StockLog::create([
                        'stock_id' => $stock->id,
                        'reference_id' => $po->order_number,
                        'type' => 'purchase',
                        'description' => "Stock received from PO {$po->order_number}",
                        'quantity_change' => $item->quantity,
                        'balance_after' => 0,
                        'user_id' => $adminUsers->random(),
                        'created_at' => $po->order_date,
                        'updated_at' => $po->order_date,
                    ]);
                }
            }
        }

        // 3. Process Stock Transfers
        $transfers = StockTransfer::with('items')->get();
        foreach ($transfers as $transfer) {
            foreach ($transfer->items as $item) {
                // Source Branch (Deduction)
                $fromStock = Stock::where('branch_id', $transfer->from_branch_id)
                    ->where('product_id', $item->product_id)
                    ->first();

                if ($fromStock) {
                    StockLog::create([
                        'stock_id' => $fromStock->id,
                        'reference_id' => 'TRF-'.$transfer->id,
                        'type' => 'transfer',
                        'description' => "Stock transferred to branch {$transfer->to_branch_id}",
                        'quantity_change' => -$item->quantity,
                        'balance_after' => 0,
                        'user_id' => $transfer->created_by,
                        'created_at' => $transfer->date,
                        'updated_at' => $transfer->date,
                    ]);
                }

                // Destination Branch (Addition - only if Received)
                if ($transfer->status === 'R') {
                    $toStock = Stock::where('branch_id', $transfer->to_branch_id)
                        ->where('product_id', $item->product_id)
                        ->first();

                    if ($toStock) {
                        StockLog::create([
                            'stock_id' => $toStock->id,
                            'reference_id' => 'TRF-'.$transfer->id,
                            'type' => 'transfer',
                            'description' => "Stock received from branch {$transfer->from_branch_id}",
                            'quantity_change' => $item->quantity,
                            'balance_after' => 0,
                            'user_id' => $transfer->created_by,
                            'created_at' => $transfer->date,
                            'updated_at' => $transfer->date,
                        ]);
                    }
                }
            }
        }

        // 4. Final Balance Reconciliation
        // In a real system, we'd calculate balance_after chronologically.
        // For simple seeding, we'll just set the final balance on the Stock model
        // and leave balance_after as a simple running total if we were more motivated.
        // Let's just update the Stock quantities based on the sum of logs + initial seed?
        // Actually, StockSeeder already set an initial quantity. Let's treat that as 'initial' log.

        $stocks = Stock::all();
        foreach ($stocks as $stock) {
            $logs = StockLog::where('stock_id', $stock->id)->orderBy('created_at')->get();
            $balance = 50; // Starting baseline for all products

            // Prepend an 'adjustment' log for initial stock
            StockLog::create([
                'stock_id' => $stock->id,
                'reference_id' => 'INIT',
                'type' => 'adjustment',
                'description' => 'Initial stock seeding',
                'quantity_change' => 50,
                'balance_after' => 50,
                'user_id' => $adminUsers->random(),
                'created_at' => now()->subMonths(2),
                'updated_at' => now()->subMonths(2),
            ]);

            foreach ($logs as $log) {
                $balance += $log->quantity_change;
                $log->update(['balance_after' => $balance]);
            }

            $stock->update(['quantity' => $balance]);
        }
    }
}
