<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\StockTransferResource;
use App\Models\StockTransferItem;
use App\Models\StockTransfer;
use App\Events\StockReceived;
use App\Events\InventoryUpdated;
use App\Models\Stock;
use App\Models\User;
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
        $transfer = StockTransfer::create([
            'from_branch_id' => $request->from_branch_id,
            'to_branch_id' => $request->to_branch_id,
            'status' => 'M',
            'items' => $request->items
        ]);

        // Find staff at the destination branch
        $staffAtDestination = User::where('branch_code', $request->to_branch_code)->get();

        // Send the WebPush!
        \Notification::send($staffAtDestination, new StockTransferRequest($transfer));

        return response()->json($transfer);
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
                    'description' => "Received from Branch: " . $transfer->from_branch_code,
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
