<?php

namespace App\Listeners;

use App\Events\AIOperationCompleted;
use App\Events\KPIEvent;
use Illuminate\Support\Facades\Event;

class RecordAIOperationMetrics
{
    public function handle(AIOperationCompleted $event): void
    {
        if (!$event->success) {
            return; // Don't record failed operations as successful metrics
        }

        // Record latency
        Event::dispatch(new KPIEvent(
            category: 'operational',
            metric: 'ai_p95_latency_ms',
            value: $event->latencyMs,
            context: [
                'operation' => $event->operation,
                'success' => $event->success,
            ],
            source: 'api',
        ));

        // Record cost
        Event::dispatch(new KPIEvent(
            category: 'operational',
            metric: 'ai_cost_per_order',
            value: $event->cost,
            context: [
                'operation' => $event->operation,
            ],
            source: 'api',
        ));

        // Record operation-specific metrics
        match ($event->operation) {
            'recommendations' => Event::dispatch(new KPIEvent(
                category: 'commercial',
                metric: 'ai_recommendations_served',
                value: 1,
                context: $event->context,
                source: 'api',
            )),
            'visual-search' => Event::dispatch(new KPIEvent(
                category: 'commercial',
                metric: 'visual_search_discovery_rate',
                value: 1,
                context: $event->context,
                source: 'api',
            )),
            'assistant' => Event::dispatch(new KPIEvent(
                category: 'operational',
                metric: 'support_deflection_rate',
                value: 1,
                context: $event->context,
                source: 'api',
            )),
            default => null,
        };
    }
}
