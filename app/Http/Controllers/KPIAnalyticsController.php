<?php

namespace App\Http\Controllers;

use App\Models\KPIRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KPIAnalyticsController extends Controller
{
    /**
     * Get KPI metrics summary for a date range
     */
    public function summary(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'category' => 'sometimes|in:commercial,operational,governance',
            'metric' => 'sometimes|string',
            'source' => 'sometimes|in:api,web,pos',
        ]);

        $query = KPIRecord::whereBetween('recorded_at', [
            $validated['start_date'],
            $validated['end_date'],
        ]);

        if (isset($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        if (isset($validated['metric'])) {
            $query->where('metric', $validated['metric']);
        }

        if (isset($validated['source'])) {
            $query->where('source', $validated['source']);
        }

        $records = $query->get();

        return response()->json([
            'total_records' => $records->count(),
            'metrics' => $this->aggregateMetrics($records),
            'by_category' => $records->groupBy('category')->map(fn($g) => $g->count()),
            'by_source' => $records->groupBy('source')->map(fn($g) => $g->count()),
        ]);
    }

    /**
     * Get commercial KPIs
     */
    public function commercialKPIs(Request $request)
    {
        $validated = $request->validate([
            'days' => 'integer|min:1|max:365',
        ]);

        $days = $validated['days'] ?? 30;
        $startDate = now()->subDays($days);

        $records = KPIRecord::where('category', 'commercial')
            ->where('recorded_at', '>=', $startDate)
            ->get()
            ->groupBy('metric');

        $kpis = [
            'conversion_uplift' => $this->calculateConversionUplift($records),
            'average_order_value' => $this->calculateAOV($records),
            'visual_search_discovery_rate' => $this->calculateVisualSearchRate($records),
        ];

        return response()->json($kpis);
    }

    /**
     * Get operational KPIs
     */
    public function operationalKPIs(Request $request)
    {
        $validated = $request->validate([
            'days' => 'integer|min:1|max:365',
        ]);

        $days = $validated['days'] ?? 30;
        $startDate = now()->subDays($days);

        $records = KPIRecord::where('category', 'operational')
            ->where('recorded_at', '>=', $startDate)
            ->get()
            ->groupBy('metric');

        $kpis = [
            'ai_p95_latency_ms' => $this->calculateLatency($records),
            'ai_cost_per_order' => $this->calculateAICost($records),
            'support_deflection_rate' => $this->calculateSupportDeflection($records),
        ];

        return response()->json($kpis);
    }

    /**
     * Get governance KPIs
     */
    public function governanceKPIs(Request $request)
    {
        $validated = $request->validate([
            'days' => 'integer|min:1|max:365',
        ]);

        $days = $validated['days'] ?? 30;
        $startDate = now()->subDays($days);

        $records = KPIRecord::where('category', 'governance')
            ->where('recorded_at', '>=', $startDate)
            ->get()
            ->groupBy('metric');

        $kpis = [
            'prompt_drift_incidents' => $this->calculateMetric($records, 'prompt_drift_incidents'),
            'hallucination_rate' => $this->calculateMetric($records, 'hallucination_rate'),
            'feature_disable_fallback_success' => $this->calculateMetric($records, 'feature_disable_fallback_success'),
        ];

        return response()->json($kpis);
    }

    /**
     * Record a KPI event
     */
    public function record(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|in:commercial,operational,governance',
            'metric' => 'required|string',
            'value' => 'required|numeric',
            'context' => 'sometimes|array',
            'source' => 'sometimes|in:api,web,pos',
        ]);

        $record = KPIRecord::recordMetric(
            category: $validated['category'],
            metric: $validated['metric'],
            value: $validated['value'],
            context: $validated['context'] ?? [],
            source: $validated['source'] ?? 'api',
            userId: auth()->id(),
            sessionId: $request->header('X-Session-ID'),
        );

        return response()->json([
            'success' => true,
            'record_id' => $record->id,
        ], 201);
    }

    private function aggregateMetrics($records)
    {
        $metrics = [];
        foreach ($records->groupBy('metric') as $metric => $values) {
            $values = $values->pluck('value')->toArray();
            $metrics[$metric] = [
                'count' => count($values),
                'sum' => array_sum($values),
                'avg' => array_sum($values) / count($values),
                'min' => min($values),
                'max' => max($values),
            ];
        }
        return $metrics;
    }

    private function calculateConversionUplift($records)
    {
        $values = $records['conversion_uplift'] ?? collect();
        if ($values->isEmpty()) {
            return ['value' => 0, 'trend' => 'N/A'];
        }
        return [
            'value' => $values->avg('value'),
            'count' => $values->count(),
            'trend' => 'positive',
        ];
    }

    private function calculateAOV($records)
    {
        $values = $records['average_order_value'] ?? collect();
        if ($values->isEmpty()) {
            return ['value' => 0];
        }
        return ['value' => $values->avg('value')];
    }

    private function calculateVisualSearchRate($records)
    {
        $values = $records['visual_search_discovery_rate'] ?? collect();
        if ($values->isEmpty()) {
            return ['rate' => 0];
        }
        return ['rate' => $values->avg('value')];
    }

    private function calculateLatency($records)
    {
        $values = $records['ai_p95_latency_ms'] ?? collect();
        if ($values->isEmpty()) {
            return ['p95' => 0];
        }
        return ['p95' => $values->avg('value')];
    }

    private function calculateAICost($records)
    {
        $values = $records['ai_cost_per_order'] ?? collect();
        if ($values->isEmpty()) {
            return ['cost_per_order' => 0];
        }
        return ['cost_per_order' => $values->avg('value')];
    }

    private function calculateSupportDeflection($records)
    {
        $values = $records['support_deflection_rate'] ?? collect();
        if ($values->isEmpty()) {
            return ['rate' => 0];
        }
        return ['rate' => $values->avg('value')];
    }

    private function calculateMetric($records, $metric)
    {
        $values = $records[$metric] ?? collect();
        if ($values->isEmpty()) {
            return ['value' => 0];
        }
        return ['value' => $values->avg('value')];
    }
}
