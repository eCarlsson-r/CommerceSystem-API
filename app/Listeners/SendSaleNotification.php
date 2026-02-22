<?php

namespace App\Listeners;

use App\Events\SaleCreated;
use App\Models\User;
use App\Notifications\SaleNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendSaleNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SaleCreated $event): void
    {
        // Get all admin and manager users
        $admins = User::whereIn('role', ['admin', 'manager'])->get();

        // Send notification to all admins and managers
        Notification::send($admins, new SaleNotification($event->sale));
    }
}
