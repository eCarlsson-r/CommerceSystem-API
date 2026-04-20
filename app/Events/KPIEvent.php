<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KPIEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $category,           // 'commercial', 'operational', 'governance'
        public string $metric,             // e.g., 'conversion_uplift', 'ai_p95_latency'
        public mixed $value,               // numeric or string value
        public array $context = [],        // additional context (user_id, product_id, etc.)
        public string $source = 'api',     // 'api', 'web', 'pos'
        public ?\DateTime $timestamp = null,
    ) {
        $this->timestamp ??= now();
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
