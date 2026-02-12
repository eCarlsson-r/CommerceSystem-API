<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\StockTransferResource;
use App\Models\StockTransferItem;
use App\Models\StockTransfer;
use App\Events\StockReceived;
use App\Events\StockTransferCreated;
use App\Events\InventoryUpdated;
use App\Models\Stock;
use App\Models\User;
use App\Models\Employee;
use App\Notifications\StockTransferRequest;

class StockTransferController extends Controller
{
    public function index()
    {
        $transfers = StockTransfer::with('items')->latest()->paginate(10);
        return StockTransferResource::collection($transfers);
    }

    // Inside StockTransferController@store
    public function store(Request $request) 
    {
        return DB::transaction(function () use ($request) {
            $transfer = StockTransfer::create([
                'date' => date('Y-m-d'),
                'created_by' => $request->user_id,
                'from_branch_id' => $request->from_branch_id,
                'to_branch_id' => $request->to_branch_id,
                'status' => 'M',
                'items' => $request->items
            ]);

            foreach ($request->items as $item) {
                $transfer->items()->create($item);
            }

            // Fire the creation event (handles notifications and broadcasting)
            event(new StockTransferCreated($transfer));

            return response()->json($transfer);
        });
    }

    // Inside StockTransferController@receive
    public function receive(Request $request)
    {
        $transfer = StockTransfer::findOrFail($request->transfer_id);
        DB::transaction(function () use ($transfer) {
            $transfer->update(['status' => 'R']);

            foreach ($transfer->items as $item) {
                $stock = Stock::where('branch_id', $transfer->to_branch_id)
                            ->where('product_id', $item->product_id)
                            ->first();
                            
                // Example logic when receiving a transfer
                $newBalance = $stock->quantity + $item->quantity;

                $stock->logs()->create([
                    'reference_id' => $transfer->id,
                    'type' => 'transfer',
                    'description' => "Received from Branch: " . $transfer->fromBranch->name,
                    'quantity_change' => $item->quantity,
                    'balance_after' => $newBalance,
                    'user_id' => auth()->id()
                ]);

                $stock->increment('quantity', $item->quantity);

                broadcast(new InventoryUpdated($stock))->toOthers();
            }
        });
        event(new StockReceived($transfer));
    }
}
