<?php

namespace App\Listeners;

use App\Events\KPIEvent;
use App\Models\KPIRecord;

class LogKPIEvent
{
    public function handle(KPIEvent $event): void
    {
        KPIRecord::recordMetric(
            category: $event->category,
            metric: $event->metric,
            value: $event->value,
            context: $event->context,
            source: $event->source,
            userId: auth()->id(),
            sessionId: request()->get('session_id') ?? request()->header('X-Session-ID'),
        );

        // If you have analytics integration (e.g., Segment, Mixpanel), send here
        $this->sendToAnalytics($event);
    }

    private function sendToAnalytics(KPIEvent $event): void
    {
        // TODO: Implement analytics service integration
        // Example: $this->analyticsService->track(...)
    }
}
