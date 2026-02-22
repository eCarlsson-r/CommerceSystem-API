<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Notifications\OrderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendOrderNotification implements ShouldQueue
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
    public function handle(OrderCreated $event): void
    {
        $branch = $event->order->branch;
        $staffs = $branch->employees; // Get staff for the branch

        Notification::send($staffs, new OrderNotification($event->order));
    }
}
