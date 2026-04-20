<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Events\KPIEvent;
use Illuminate\Support\Facades\Event;

class RecordOrderConversion
{
    public function handle(OrderCreated $event): void
    {
        // Record conversion event
        Event::dispatch(new KPIEvent(
            category: 'commercial',
            metric: 'conversion_uplift',
            value: 1,
            context: [
                'order_id' => $event->order->id,
                'total' => $event->order->total,
                'items_count' => $event->order->items()->count(),
            ],
            source: 'api',
        ));

        // Record AOV
        Event::dispatch(new KPIEvent(
            category: 'commercial',
            metric: 'average_order_value',
            value: $event->order->total,
            context: [
                'order_id' => $event->order->id,
            ],
            source: 'api',
        ));
    }
}
