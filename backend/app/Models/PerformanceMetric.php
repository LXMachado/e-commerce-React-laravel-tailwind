<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class PerformanceMetric extends Model
{
    use HasFactory;

    protected $table = 'performance_metrics';

    protected $fillable = [
        'metric_type',
        'metric_name',
        'operation',
        'endpoint',
        'method',
        'value',
        'unit',
        'threshold',
        'status',
        'context',
        'metadata',
        'user_agent',
        'ip_address',
        'user_id',
        'session_id',
        'memory_usage_mb',
        'cpu_usage_percent',
        'cache_hits',
        'cache_misses',
        'cache_hit_ratio',
        'database_queries_count',
        'database_query_time_ms',
        'measured_at',
    ];

    protected $casts = [
        'value' => 'decimal:4',
        'threshold' => 'decimal:4',
        'context' => 'array',
        'metadata' => 'array',
        'memory_usage_mb' => 'decimal:2',
        'cpu_usage_percent' => 'decimal:2',
        'cache_hits' => 'integer',
        'cache_misses' => 'integer',
        'cache_hit_ratio' => 'decimal:4',
        'database_queries_count' => 'integer',
        'database_query_time_ms' => 'decimal:4',
        'measured_at' => 'datetime',
    ];

    protected $attributes = [
        'unit' => 'ms',
        'status' => 'success',
    ];

    /**
     * Get the user associated with this metric
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this metric indicates a performance issue
     */
    public function isPerformanceIssue(): bool
    {
        return in_array($this->status, ['warning', 'error', 'critical']);
    }

    /**
     * Check if this metric is slow
     */
    public function isSlow(): bool
    {
        return $this->value > 250; // More than 250ms
    }

    /**
     * Get formatted value with unit
     */
    public function getFormattedValueAttribute(): string
    {
        return round($this->value, 2) . ' ' . $this->unit;
    }

    /**
     * Get performance level based on value
     */
    public function getPerformanceLevelAttribute(): string
    {
        if ($this->value > 1000) {
            return 'very_slow';
        } elseif ($this->value > 250) {
            return 'slow';
        }

        return 'fast';
    }

    /**
     * Scope for API response metrics
     */
    public function scopeApiResponses(Builder $query): Builder
    {
        return $query->where('metric_type', 'api_response');
    }

    /**
     * Scope for database query metrics
     */
    public function scopeDatabaseQueries(Builder $query): Builder
    {
        return $query->where('metric_type', 'database_query');
    }

    /**
     * Scope for cache operation metrics
     */
    public function scopeCacheOperations(Builder $query): Builder
    {
        return $query->where('metric_type', 'cache_operation');
    }

    /**
     * Scope for system resource metrics
     */
    public function scopeSystemResources(Builder $query): Builder
    {
        return $query->where('metric_type', 'system_resource');
    }

    /**
     * Scope for slow metrics
     */
    public function scopeSlow(Builder $query): Builder
    {
        return $query->where('value', '>', 250);
    }

    /**
     * Scope for metrics by type
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('metric_type', $type);
    }

    /**
     * Scope for metrics by status
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for metrics in time range
     */
    public function scopeInTimeRange(Builder $query, string $timeRange): Builder
    {
        $hours = match ($timeRange) {
            '1_hour' => 1,
            '6_hours' => 6,
            '24_hours' => 24,
            '7_days' => 168,
            default => 1,
        };

        return $query->where('measured_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope for metrics by endpoint
     */
    public function scopeByEndpoint(Builder $query, string $endpoint): Builder
    {
        return $query->where('endpoint', $endpoint);
    }

    /**
     * Scope for metrics by user
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get average value for metric type in time range
     */
    public static function getAverageForType(string $type, string $timeRange = '1_hour'): float
    {
        return static::byType($type)
            ->inTimeRange($timeRange)
            ->avg('value') ?? 0;
    }

    /**
     * Get count of slow metrics in time range
     */
    public static function getSlowCount(string $timeRange = '1_hour'): int
    {
        return static::slow()
            ->inTimeRange($timeRange)
            ->count();
    }

    /**
     * Get performance summary for time range
     */
    public static function getPerformanceSummary(string $timeRange = '1_hour'): array
    {
        $baseQuery = static::inTimeRange($timeRange);

        return [
            'total_metrics' => (clone $baseQuery)->count(),
            'slow_metrics' => (clone $baseQuery)->slow()->count(),
            'error_metrics' => (clone $baseQuery)->byStatus('error')->count(),
            'critical_metrics' => (clone $baseQuery)->byStatus('critical')->count(),
            'average_response_time' => (clone $baseQuery)->apiResponses()->avg('value') ?? 0,
            'average_memory_usage' => (clone $baseQuery)->systemResources()->where('metric_name', 'memory_usage')->avg('value') ?? 0,
            'average_cpu_usage' => (clone $baseQuery)->systemResources()->where('metric_name', 'cpu_usage')->avg('value') ?? 0,
        ];
    }

    /**
     * Get top slow endpoints
     */
    public static function getTopSlowEndpoints(string $timeRange = '1_hour', int $limit = 10): array
    {
        return static::select('endpoint', 'method', \DB::raw('AVG(value) as avg_time'), \DB::raw('COUNT(*) as request_count'))
            ->apiResponses()
            ->inTimeRange($timeRange)
            ->slow()
            ->groupBy('endpoint', 'method')
            ->orderBy('avg_time', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get performance trends for time range
     */
    public static function getPerformanceTrends(string $timeRange = '24_hours'): array
    {
        $hours = match ($timeRange) {
            '1_hour' => 1,
            '6_hours' => 6,
            '24_hours' => 24,
            '7_days' => 168,
            default => 24,
        };

        return static::select(
                \DB::raw('DATE_FORMAT(measured_at, "%Y-%m-%d %H:00:00") as hour'),
                \DB::raw('AVG(value) as avg_value'),
                \DB::raw('COUNT(*) as count'),
                'metric_type'
            )
            ->where('measured_at', '>=', now()->subHours($hours))
            ->groupBy('hour', 'metric_type')
            ->orderBy('hour')
            ->get()
            ->groupBy('metric_type')
            ->toArray();
    }

    /**
     * Clean up old metrics
     */
    public static function cleanupOldMetrics(int $daysToKeep = 30): int
    {
        return static::where('measured_at', '<', now()->subDays($daysToKeep))
            ->delete();
    }

    /**
     * Get metrics by time period
     */
    public static function getMetricsByPeriod(string $period, string $metricType = null): array
    {
        $query = static::where('measured_at', '>=', now()->subDay());

        if ($metricType) {
            $query->byType($metricType);
        }

        return $query->orderBy('measured_at')
            ->get()
            ->groupBy(function ($metric) use ($period) {
                return match ($period) {
                    'hour' => $metric->measured_at->format('Y-m-d H:00'),
                    'day' => $metric->measured_at->format('Y-m-d'),
                    'week' => $metric->measured_at->format('Y-W'),
                    default => $metric->measured_at->format('Y-m-d H:00'),
                };
            })
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'average_value' => $group->avg('value'),
                    'min_value' => $group->min('value'),
                    'max_value' => $group->max('value'),
                    'slow_count' => $group->where('value', '>', 250)->count(),
                ];
            })
            ->toArray();
    }
}
