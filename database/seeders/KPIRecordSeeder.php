<?php

namespace Database\Seeders;

use App\Models\KPIRecord;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class KPIRecordSeeder extends Seeder
{
    public function run(): void
    {
        $days = 30;
        $now = Carbon::now();

        // Commercial KPIs
        for ($i = 0; $i < $days; $i++) {
            $date = $now->copy()->subDays($i);
            
            // Conversion uplift (0-15% range)
            KPIRecord::recordMetric(
                category: 'commercial',
                metric: 'conversion_uplift',
                value: rand(50, 150) / 10, // 5.0% to 15.0%
                context: ['date' => $date->toDateString()],
                source: 'web',
                recordedAt: $date
            );

            // Average order value
            KPIRecord::recordMetric(
                category: 'commercial',
                metric: 'aov',
                value: rand(80000, 250000), // Rp 80k - 250k
                context: ['currency' => 'IDR'],
                source: 'web',
                recordedAt: $date
            );

            // Visual search rate
            KPIRecord::recordMetric(
                category: 'commercial',
                metric: 'visual_search_rate',
                value: rand(10, 35) / 10, // 1.0% - 3.5%
                context: ['date' => $date->toDateString()],
                source: 'web',
                recordedAt: $date
            );
        }

        // Operational KPIs
        for ($i = 0; $i < $days; $i++) {
            $date = $now->copy()->subDays($i);
            
            // AI P95 latency (200-800ms range)
            KPIRecord::recordMetric(
                category: 'operational',
                metric: 'ai_p95_latency_ms',
                value: rand(200, 800),
                context: ['endpoint' => 'recommendations'],
                source: 'api',
                recordedAt: $date
            );

            // AI cost per order (Rp 500-2000)
            KPIRecord::recordMetric(
                category: 'operational',
                metric: 'ai_cost_per_order',
                value: rand(500, 2000),
                context: ['currency' => 'IDR'],
                source: 'api',
                recordedAt: $date
            );

            // Support deflection rate
            KPIRecord::recordMetric(
                category: 'operational',
                metric: 'support_deflection_rate',
                value: rand(150, 350) / 10, // 15% - 35%
                context: ['assistant_resolutions' => rand(10, 50)],
                source: 'web',
                recordedAt: $date
            );
        }

        // Governance KPIs (occasional)
        for ($i = 0; $i < 5; $i++) {
            $date = $now->copy()->subDays(rand(1, 30));
            
            // Prompt drift incidents (rare)
            KPIRecord::recordMetric(
                category: 'governance',
                metric: 'prompt_drift_incidents',
                value: rand(0, 2),
                context: ['severity' => 'low'],
                source: 'api',
                recordedAt: $date
            );
        }

        // Fallback success rate
        KPIRecord::recordMetric(
            category: 'governance',
            metric: 'fallback_success_rate',
            value: 98.5,
            context: ['offline_orders_synced' => rand(45, 55)],
            source: 'web',
            recordedAt: $now
        );
    }
}
