<?php

namespace App\Events;

use App\Models\Stock;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Stock $stock) {}

    public function broadcastOn(): array
    {
        // We broadcast to a public 'inventory' channel
        return [new Channel('inventory')];
    }

    public function broadcastWith(): array
    {
        // Data sent to Next.js/Angular
        return [
            'product_code' => $this->stock->product_code,
            'branch_code'  => $this->stock->branch_code,
            'new_quantity' => $this->stock->quantity,
        ];
    }
}
