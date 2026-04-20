<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KPIRecord extends Model
{
    protected $table = 'kpi_records';

    protected $fillable = [
        'category',        // 'commercial', 'operational', 'governance'
        'metric',          // metric name
        'value',           // metric value
        'context',         // JSON context data
        'source',          // 'api', 'web', 'pos'
        'user_id',         // nullable
        'customer_id',     // nullable
        'session_id',      // nullable
        'recorded_at',
    ];

    protected $casts = [
        'context' => 'array',
        'value' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    public static function recordMetric(
        string $category,
        string $metric,
        mixed $value,
        array $context = [],
        string $source = 'api',
        ?int $userId = null,
        ?int $customerId = null,
        ?string $sessionId = null,
    ) {
        return static::create([
            'category' => $category,
            'metric' => $metric,
            'value' => $value,
            'context' => $context,
            'source' => $source,
            'user_id' => $userId,
            'customer_id' => $customerId,
            'session_id' => $sessionId,
            'recorded_at' => now(),
        ]);
    }
}
