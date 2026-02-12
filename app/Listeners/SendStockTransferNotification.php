<?php

namespace App\Listeners;

use App\Events\StockTransferCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use App\Models\User;
use App\Models\Employee;
use App\Notifications\StockTransferRequest;
use Illuminate\Support\Facades\Notification;

class SendStockTransferNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(StockTransferCreated $event): void
    {
        $transfer = $event->transfer;

        // Find staff at the destination branch
        $staffAtDestination = Employee::where('branch_id', $transfer->to_branch_id)->get();
        $staffIds = $staffAtDestination->pluck('user_id');
        $staffs = User::whereIn('id', $staffIds)->get();

        // Send the Notification (WebPush, etc)
        Notification::send($staffs, new StockTransferRequest($transfer));
    }
}
