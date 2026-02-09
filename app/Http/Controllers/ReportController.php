<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Stock;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\StockLog;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function dailyClosing(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'date' => 'required|date',
        ]);

        $date = $request->date;
        $branchId = $request->branch_id;

        // Get totals by payment method
        $summary = Sale::where('branch_id', $branchId)
            ->whereDate('created_at', $date)
            ->select('payment_method', DB::raw('SUM(total_amount) as total'))
            ->groupBy('payment_method')
            ->get();

        // Get specific transaction count and total volume
        $stats = [
            'total_revenue' => $summary->sum('total'),
            'transaction_count' => Sale::where('branch_id', $branchId)->whereDate('created_at', $date)->count(),
            'breakdown' => $summary,
            'generated_at' => now()->toDateTimeString()
        ];

        return response()->json($stats);
    }

    public function stockAudit(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id',
        ]);

        $logs = StockLog::where('product_id', $request->product_id)
            ->where('branch_id', $request->branch_id)
            ->with('user:id,name') // Who performed the action?
            ->latest()
            ->paginate(20);

        return response()->json($logs);
    }

    public function financialOverview() {
        return [
            'total' => Sale::sum('grand_total'),
            'branches' => Branch::withSum('sales', 'grand_total')->get()
        ];
    }

    public function inventoryMatrix()
    {
        $products = Product::with(['stocks' => function($query) {
            $query->select('product_id', 'branch_id', 'quantity');
        }, 'stocks.branch:id,name'])->get();

        return response()->json($products);
    }

    public function supplierPerformance()
    {
        return Supplier::withCount('purchaseOrders')
            ->get()
            ->map(function ($supplier) {
                // Calculate total items bought from this supplier
                $totalBought = DB::table('purchase_order_items')
                    ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
                    ->where('purchase_orders.supplier_id', $supplier->id)
                    ->sum('quantity');

                // Calculate items from this supplier that ended up in Waste
                // Note: This requires a supplier_id or PO reference in your WasteLog
                $totalWaste = WasteLog::where('supplier_id', $supplier->id)->sum('quantity');

                return [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'po_count' => $supplier->purchase_orders_count,
                    'total_volume' => $totalBought,
                    'waste_volume' => $totalWaste,
                    'defect_rate' => $totalBought > 0 ? ($totalWaste / $totalBought) * 100 : 0,
                ];
            });
    }
}