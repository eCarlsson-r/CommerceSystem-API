<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AIOperationCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $operation,           // 'recommendations', 'visual-search', 'assistant', 'translate'
        public int $latencyMs,              // operation latency in milliseconds
        public float $cost,                 // estimated cost
        public bool $success,               // whether operation succeeded
        public array $context = [],         // additional context
        public ?\Exception $error = null,
    ) {
    }
}
