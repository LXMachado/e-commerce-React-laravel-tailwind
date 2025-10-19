<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class PerformanceMonitorService
{
    /**
     * Performance thresholds
     */
    private const SLOW_QUERY_THRESHOLD = 250; // milliseconds
    private const VERY_SLOW_QUERY_THRESHOLD = 1000; // milliseconds
    private const CACHE_HIT_RATIO_TARGET = 0.8; // 80%

    /**
     * Monitor search performance
     */
    public function monitorSearchPerformance(string $operation, float $executionTime, array $context = []): void
    {
        $executionTimeMs = $executionTime * 1000;

        $performanceData = [
            'operation' => $operation,
            'execution_time_ms' => round($executionTimeMs, 2),
            'timestamp' => now()->toISOString(),
            'context' => $context,
            'performance_level' => $this->classifyPerformance($executionTimeMs),
        ];

        // Log based on performance level
        switch ($performanceData['performance_level']) {
            case 'slow':
                Log::warning('Slow search operation detected', $performanceData);
                break;
            case 'very_slow':
                Log::error('Very slow search operation detected', $performanceData);
                break;
            default:
                Log::info('Search operation performance', $performanceData);
                break;
        }

        // Store performance metrics for analysis
        $this->storePerformanceMetrics($performanceData);
    }

    /**
     * Get search performance statistics
     */
    public function getSearchPerformanceStats(string $timeRange = '1_hour'): array
    {
        $cacheKey = "performance_stats:{$timeRange}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function() use ($timeRange) {
            return [
                'time_range' => $timeRange,
                'total_operations' => $this->getTotalOperationsCount($timeRange),
                'average_execution_time' => $this->getAverageExecutionTime($timeRange),
                'slow_operations_count' => $this->getSlowOperationsCount($timeRange),
                'cache_hit_ratio' => $this->getCacheHitRatio($timeRange),
                'performance_distribution' => $this->getPerformanceDistribution($timeRange),
                'top_slow_operations' => $this->getTopSlowOperations($timeRange),
            ];
        });
    }

    /**
     * Optimize database queries based on performance data
     */
    public function optimizeQueries(): array
    {
        $optimizations = [];

        // Check for missing indexes
        $missingIndexes = $this->analyzeMissingIndexes();
        if (!empty($missingIndexes)) {
            $optimizations['missing_indexes'] = $missingIndexes;
        }

        // Check for slow queries that could benefit from caching
        $cacheCandidates = $this->identifyCacheCandidates();
        if (!empty($cacheCandidates)) {
            $optimizations['cache_candidates'] = $cacheCandidates;
        }

        // Check for query optimization opportunities
        $queryOptimizations = $this->analyzeQueryOptimizations();
        if (!empty($queryOptimizations)) {
            $optimizations['query_optimizations'] = $queryOptimizations;
        }

        return $optimizations;
    }

    /**
     * Classify performance level
     */
    private function classifyPerformance(float $executionTimeMs): string
    {
        if ($executionTimeMs > self::VERY_SLOW_QUERY_THRESHOLD) {
            return 'very_slow';
        } elseif ($executionTimeMs > self::SLOW_QUERY_THRESHOLD) {
            return 'slow';
        }

        return 'fast';
    }

    /**
     * Store performance metrics in database and cache
     */
    private function storePerformanceMetrics(array $performanceData): void
    {
        // Store in database
        \App\Models\PerformanceMetric::create([
            'metric_type' => $performanceData['metric_type'] ?? 'general',
            'metric_name' => $performanceData['metric_name'] ?? 'unknown',
            'operation' => $performanceData['operation'] ?? null,
            'endpoint' => $performanceData['endpoint'] ?? null,
            'method' => $performanceData['method'] ?? null,
            'value' => $performanceData['execution_time_ms'] ?? 0,
            'unit' => 'ms',
            'status' => $this->classifyPerformanceStatus($performanceData['execution_time_ms'] ?? 0),
            'context' => $performanceData['context'] ?? null,
            'metadata' => $performanceData['metadata'] ?? null,
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip(),
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'memory_usage_mb' => $this->getMemoryUsage(),
            'cpu_usage_percent' => $this->getCpuUsage(),
            'measured_at' => now(),
        ]);

        // Also store in cache for quick access
        $metricsKey = 'performance_metrics:' . date('Y-m-d-H');
        $currentMetrics = Cache::get($metricsKey, []);
        $currentMetrics[] = $performanceData;

        // Keep only last 1000 metrics per hour
        if (count($currentMetrics) > 1000) {
            $currentMetrics = array_slice($currentMetrics, -1000);
        }

        Cache::put($metricsKey, $currentMetrics, now()->addHour());
    }

    /**
     * Get total operations count
     */
    private function getTotalOperationsCount(string $timeRange): int
    {
        $hours = $this->parseTimeRange($timeRange);

        $total = 0;
        for ($i = 0; $i < $hours; $i++) {
            $metricsKey = 'performance_metrics:' . date('Y-m-d-H', strtotime("-{$i} hours"));
            $metrics = Cache::get($metricsKey, []);
            $total += count($metrics);
        }

        return $total;
    }

    /**
     * Get average execution time
     */
    private function getAverageExecutionTime(string $timeRange): float
    {
        $hours = $this->parseTimeRange($timeRange);

        $totalTime = 0;
        $totalCount = 0;

        for ($i = 0; $i < $hours; $i++) {
            $metricsKey = 'performance_metrics:' . date('Y-m-d-H', strtotime("-{$i} hours"));
            $metrics = Cache::get($metricsKey, []);

            foreach ($metrics as $metric) {
                $totalTime += $metric['execution_time_ms'];
                $totalCount++;
            }
        }

        return $totalCount > 0 ? round($totalTime / $totalCount, 2) : 0;
    }

    /**
     * Get slow operations count
     */
    private function getSlowOperationsCount(string $timeRange): int
    {
        $hours = $this->parseTimeRange($timeRange);

        $slowCount = 0;

        for ($i = 0; $i < $hours; $i++) {
            $metricsKey = 'performance_metrics:' . date('Y-m-d-H', strtotime("-{$i} hours"));
            $metrics = Cache::get($metricsKey, []);

            foreach ($metrics as $metric) {
                if (in_array($metric['performance_level'], ['slow', 'very_slow'])) {
                    $slowCount++;
                }
            }
        }

        return $slowCount;
    }

    /**
     * Get cache hit ratio
     */
    private function getCacheHitRatio(string $timeRange): float
    {
        // This would typically be calculated from actual cache hit/miss data
        // For now, return a placeholder based on configuration
        return config('cache.search_enabled', true) ? 0.85 : 0.0;
    }

    /**
     * Get performance distribution
     */
    private function getPerformanceDistribution(string $timeRange): array
    {
        $hours = $this->parseTimeRange($timeRange);

        $distribution = ['fast' => 0, 'slow' => 0, 'very_slow' => 0];

        for ($i = 0; $i < $hours; $i++) {
            $metricsKey = 'performance_metrics:' . date('Y-m-d-H', strtotime("-{$i} hours"));
            $metrics = Cache::get($metricsKey, []);

            foreach ($metrics as $metric) {
                $distribution[$metric['performance_level']]++;
            }
        }

        return $distribution;
    }

    /**
     * Get top slow operations
     */
    private function getTopSlowOperations(string $timeRange, int $limit = 10): array
    {
        $hours = $this->parseTimeRange($timeRange);

        $slowOperations = [];

        for ($i = 0; $i < $hours; $i++) {
            $metricsKey = 'performance_metrics:' . date('Y-m-d-H', strtotime("-{$i} hours"));
            $metrics = Cache::get($metricsKey, []);

            foreach ($metrics as $metric) {
                if (in_array($metric['performance_level'], ['slow', 'very_slow'])) {
                    $slowOperations[] = $metric;
                }
            }
        }

        // Sort by execution time and return top slowest
        usort($slowOperations, function($a, $b) {
            return $b['execution_time_ms'] <=> $a['execution_time_ms'];
        });

        return array_slice($slowOperations, 0, $limit);
    }

    /**
     * Analyze missing indexes
     */
    private function analyzeMissingIndexes(): array
    {
        $missingIndexes = [];

        // Check for common search patterns that might need indexes
        $searchPatterns = [
            'products.name' => 'Search by product name',
            'products.description' => 'Search by product description',
            'products.sku' => 'Search by SKU',
            'product_variants.sku' => 'Search by variant SKU',
            'categories.name' => 'Search by category name',
        ];

        foreach ($searchPatterns as $column => $description) {
            // This would typically query information_schema or use EXPLAIN ANALYZE
            // For now, return recommendations based on common patterns
            $missingIndexes[] = [
                'column' => $column,
                'description' => $description,
                'recommended_index' => "INDEX idx_{$column}_search ({$column})",
            ];
        }

        return $missingIndexes;
    }

    /**
     * Identify cache candidates
     */
    private function identifyCacheCandidates(): array
    {
        return [
            [
                'query_type' => 'category_products',
                'description' => 'Products by category queries',
                'recommended_ttl' => 300, // 5 minutes
            ],
            [
                'query_type' => 'popular_products',
                'description' => 'Frequently searched products',
                'recommended_ttl' => 600, // 10 minutes
            ],
        ];
    }

    /**
     * Analyze query optimizations
     */
    private function analyzeQueryOptimizations(): array
    {
        return [
            [
                'type' => 'query_refactoring',
                'description' => 'Use SELECT specific columns instead of SELECT *',
                'impact' => 'medium',
            ],
            [
                'type' => 'join_optimization',
                'description' => 'Optimize JOIN conditions for better performance',
                'impact' => 'high',
            ],
        ];
    }

    /**
     * Parse time range to hours
     */
    private function parseTimeRange(string $timeRange): int
    {
        return match ($timeRange) {
            '1_hour' => 1,
            '6_hours' => 6,
            '24_hours' => 24,
            '7_days' => 168,
            default => 1,
        };
    }

    /**
     * Clear performance metrics
     */
    public function clearMetrics(): void
    {
        $pattern = 'performance_metrics:*';
        $keys = Cache::getStore()->getRedis()->keys($pattern);

        if (!empty($keys)) {
            Cache::getStore()->getRedis()->del($keys);
        }

        Log::info('Performance metrics cleared');
    }

    /**
     * Get system health status
     */
    public function getSystemHealth(): array
    {
        $stats = $this->getSearchPerformanceStats('1_hour');

        $healthScore = $this->calculateHealthScore($stats);

        return [
            'health_score' => $healthScore,
            'status' => $this->getHealthStatus($healthScore),
            'performance_stats' => $stats,
            'recommendations' => $this->getHealthRecommendations($stats),
        ];
    }

    /**
     * Calculate health score (0-100)
     */
    private function calculateHealthScore(array $stats): float
    {
        $score = 100;

        // Deduct points for slow performance
        $slowPercentage = ($stats['slow_operations_count'] / max($stats['total_operations'], 1)) * 100;
        $score -= $slowPercentage * 2;

        // Deduct points for high average execution time
        if ($stats['average_execution_time'] > self::SLOW_QUERY_THRESHOLD) {
            $score -= 20;
        }

        // Bonus points for good cache hit ratio
        if ($stats['cache_hit_ratio'] > self::CACHE_HIT_RATIO_TARGET) {
            $score += 5;
        }

        return max(0, min(100, $score));
    }

    /**
     * Get health status based on score
     */
    private function getHealthStatus(float $score): string
    {
        if ($score >= 90) return 'excellent';
        if ($score >= 75) return 'good';
        if ($score >= 60) return 'fair';
        if ($score >= 40) return 'poor';

        return 'critical';
    }

    /**
     * Get health recommendations
     */
    private function getHealthRecommendations(array $stats): array
    {
        $recommendations = [];

        if ($stats['average_execution_time'] > self::SLOW_QUERY_THRESHOLD) {
            $recommendations[] = 'Consider adding database indexes for frequently searched columns';
        }

        if ($stats['slow_operations_count'] > 0) {
            $recommendations[] = 'Review and optimize slow queries';
        }

        if ($stats['cache_hit_ratio'] < self::CACHE_HIT_RATIO_TARGET) {
            $recommendations[] = 'Enable or optimize caching for better performance';
        }

        return $recommendations;
    }

    /**
     * Monitor API response time
     */
    public function monitorApiResponse(string $endpoint, string $method, float $responseTime, array $context = []): void
    {
        $responseTimeMs = $responseTime * 1000;

        $performanceData = [
            'metric_type' => 'api_response',
            'metric_name' => 'response_time',
            'operation' => 'api_request',
            'endpoint' => $endpoint,
            'method' => $method,
            'execution_time_ms' => round($responseTimeMs, 2),
            'context' => $context,
            'performance_level' => $this->classifyPerformance($responseTimeMs),
        ];

        // Log based on performance level
        switch ($performanceData['performance_level']) {
            case 'slow':
                Log::warning('Slow API response detected', $performanceData);
                break;
            case 'very_slow':
                Log::error('Very slow API response detected', $performanceData);
                break;
            default:
                Log::info('API response performance', $performanceData);
                break;
        }

        // Check if alerting is needed
        if ($responseTimeMs > self::SLOW_QUERY_THRESHOLD) {
            $this->triggerAlert('slow_api_response', $performanceData);
        }

        $this->storePerformanceMetrics($performanceData);
    }

    /**
     * Monitor database query performance
     */
    public function monitorDatabaseQuery(string $query, float $executionTime, array $context = []): void
    {
        $executionTimeMs = $executionTime * 1000;

        $performanceData = [
            'metric_type' => 'database_query',
            'metric_name' => 'query_execution_time',
            'operation' => $query,
            'execution_time_ms' => round($executionTimeMs, 2),
            'context' => $context,
            'performance_level' => $this->classifyPerformance($executionTimeMs),
        ];

        // Log slow queries
        if ($executionTimeMs > self::SLOW_QUERY_THRESHOLD) {
            Log::warning('Slow database query detected', $performanceData);
        }

        $this->storePerformanceMetrics($performanceData);
    }

    /**
     * Monitor cache operations
     */
    public function monitorCacheOperation(string $operation, float $executionTime, bool $hit, array $context = []): void
    {
        $executionTimeMs = $executionTime * 1000;

        $performanceData = [
            'metric_type' => 'cache_operation',
            'metric_name' => 'cache_' . $operation,
            'operation' => $operation,
            'execution_time_ms' => round($executionTimeMs, 2),
            'context' => array_merge($context, ['cache_hit' => $hit]),
            'performance_level' => $this->classifyPerformance($executionTimeMs),
        ];

        $this->storePerformanceMetrics($performanceData);
    }

    /**
     * Monitor system resources
     */
    public function monitorSystemResources(): void
    {
        $metrics = [
            'memory_usage' => $this->getMemoryUsage(),
            'cpu_usage' => $this->getCpuUsage(),
        ];

        foreach ($metrics as $metric => $value) {
            $performanceData = [
                'metric_type' => 'system_resource',
                'metric_name' => $metric,
                'value' => $value,
                'unit' => $metric === 'memory_usage' ? 'MB' : 'percent',
                'measured_at' => now(),
            ];

            $this->storePerformanceMetrics($performanceData);
        }
    }

    /**
     * Get comprehensive performance statistics
     */
    public function getComprehensiveStats(string $timeRange = '1_hour'): array
    {
        return Cache::remember("comprehensive_stats:{$timeRange}", now()->addMinutes(5), function() use ($timeRange) {
            return [
                'api_performance' => $this->getApiPerformanceStats($timeRange),
                'database_performance' => $this->getDatabasePerformanceStats($timeRange),
                'cache_performance' => $this->getCachePerformanceStats($timeRange),
                'system_performance' => $this->getSystemPerformanceStats($timeRange),
                'media_performance' => $this->getMediaPerformanceStats($timeRange),
                'overall_health' => $this->getOverallHealthScore($timeRange),
            ];
        });
    }

    /**
     * Get API performance statistics
     */
    private function getApiPerformanceStats(string $timeRange): array
    {
        $hours = $this->parseTimeRange($timeRange);

        return [
            'total_requests' => \App\Models\PerformanceMetric::where('metric_type', 'api_response')
                ->where('measured_at', '>=', now()->subHours($hours))
                ->count(),
            'average_response_time' => \App\Models\PerformanceMetric::where('metric_type', 'api_response')
                ->where('measured_at', '>=', now()->subHours($hours))
                ->avg('value') ?? 0,
            'slow_requests' => \App\Models\PerformanceMetric::where('metric_type', 'api_response')
                ->where('measured_at', '>=', now()->subHours($hours))
                ->where('value', '>', self::SLOW_QUERY_THRESHOLD)
                ->count(),
            'top_endpoints' => $this->getTopEndpoints($timeRange),
        ];
    }

    /**
     * Get database performance statistics
     */
    private function getDatabasePerformanceStats(string $timeRange): array
    {
        $hours = $this->parseTimeRange($timeRange);

        return [
            'total_queries' => \App\Models\PerformanceMetric::where('metric_type', 'database_query')
                ->where('measured_at', '>=', now()->subHours($hours))
                ->count(),
            'average_query_time' => \App\Models\PerformanceMetric::where('metric_type', 'database_query')
                ->where('measured_at', '>=', now()->subHours($hours))
                ->avg('value') ?? 0,
            'slow_queries' => \App\Models\PerformanceMetric::where('metric_type', 'database_query')
                ->where('measured_at', '>=', now()->subHours($hours))
                ->where('value', '>', self::SLOW_QUERY_THRESHOLD)
                ->count(),
        ];
    }

    /**
     * Get cache performance statistics
     */
    private function getCachePerformanceStats(string $timeRange): array
    {
        $hours = $this->parseTimeRange($timeRange);

        $cacheHits = \App\Models\PerformanceMetric::where('metric_type', 'cache_operation')
            ->where('measured_at', '>=', now()->subHours($hours))
            ->where('context->cache_hit', true)
            ->count();

        $cacheMisses = \App\Models\PerformanceMetric::where('metric_type', 'cache_operation')
            ->where('measured_at', '>=', now()->subHours($hours))
            ->where('context->cache_hit', false)
            ->count();

        $totalCacheOps = $cacheHits + $cacheMisses;
        $hitRatio = $totalCacheOps > 0 ? $cacheHits / $totalCacheOps : 0;

        return [
            'total_operations' => $totalCacheOps,
            'cache_hits' => $cacheHits,
            'cache_misses' => $cacheMisses,
            'hit_ratio' => round($hitRatio, 4),
        ];
    }

    /**
     * Get system performance statistics
     */
    private function getSystemPerformanceStats(string $timeRange): array
    {
        $hours = $this->parseTimeRange($timeRange);

        return [
            'average_memory_usage' => \App\Models\PerformanceMetric::where('metric_type', 'system_resource')
                ->where('metric_name', 'memory_usage')
                ->where('measured_at', '>=', now()->subHours($hours))
                ->avg('value') ?? 0,
            'average_cpu_usage' => \App\Models\PerformanceMetric::where('metric_type', 'system_resource')
                ->where('metric_name', 'cpu_usage')
                ->where('measured_at', '>=', now()->subHours($hours))
                ->avg('value') ?? 0,
        ];
    }

    /**
     * Get media performance statistics
     */
    private function getMediaPerformanceStats(string $timeRange): array
    {
        $hours = $this->parseTimeRange($timeRange);

        return [
            'total_uploads' => \App\Models\PerformanceMetric::where('operation', 'media_upload')
                ->where('measured_at', '>=', now()->subHours($hours))
                ->count(),
            'total_processing' => \App\Models\PerformanceMetric::where('operation', 'media_processing')
                ->where('measured_at', '>=', now()->subHours($hours))
                ->count(),
            'average_upload_time' => \App\Models\PerformanceMetric::where('operation', 'media_upload')
                ->where('measured_at', '>=', now()->subHours($hours))
                ->avg('value') ?? 0,
            'average_processing_time' => \App\Models\PerformanceMetric::where('operation', 'media_processing')
                ->where('measured_at', '>=', now()->subHours($hours))
                ->avg('value') ?? 0,
        ];
    }

    /**
     * Get top slow endpoints
     */
    private function getTopEndpoints(string $timeRange, int $limit = 10): array
    {
        $hours = $this->parseTimeRange($timeRange);

        return \App\Models\PerformanceMetric::select('endpoint', 'method', \DB::raw('AVG(value) as avg_time'), \DB::raw('COUNT(*) as request_count'))
            ->where('metric_type', 'api_response')
            ->where('measured_at', '>=', now()->subHours($hours))
            ->where('value', '>', self::SLOW_QUERY_THRESHOLD)
            ->groupBy('endpoint', 'method')
            ->orderBy('avg_time', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get overall health score
     */
    private function getOverallHealthScore(string $timeRange): array
    {
        $stats = $this->getComprehensiveStats($timeRange);

        $score = 100;

        // Deduct points for slow API responses
        $slowApiPercentage = ($stats['api_performance']['slow_requests'] / max($stats['api_performance']['total_requests'], 1)) * 100;
        $score -= $slowApiPercentage * 1.5;

        // Deduct points for slow database queries
        $slowQueryPercentage = ($stats['database_performance']['slow_queries'] / max($stats['database_performance']['total_queries'], 1)) * 100;
        $score -= $slowQueryPercentage * 2;

        // Deduct points for high memory usage
        if ($stats['system_performance']['average_memory_usage'] > 100) {
            $score -= 20;
        }

        // Bonus points for good cache hit ratio
        if ($stats['cache_performance']['hit_ratio'] > self::CACHE_HIT_RATIO_TARGET) {
            $score += 5;
        }

        return [
            'score' => max(0, min(100, $score)),
            'status' => $this->getHealthStatus($score),
            'last_updated' => now()->toISOString(),
        ];
    }

    /**
     * Classify performance status
     */
    private function classifyPerformanceStatus(float $value): string
    {
        if ($value > self::VERY_SLOW_QUERY_THRESHOLD) {
            return 'critical';
        } elseif ($value > self::SLOW_QUERY_THRESHOLD) {
            return 'warning';
        }

        return 'success';
    }

    /**
     * Get current memory usage
     */
    private function getMemoryUsage(): float
    {
        if (function_exists('memory_get_usage')) {
            return round(memory_get_usage(true) / 1024 / 1024, 2); // Convert to MB
        }

        return 0;
    }

    /**
     * Get current CPU usage (Linux/Unix systems)
     */
    private function getCpuUsage(): float
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return round($load[0] * 100, 2); // Convert load average to percentage
        }

        return 0;
    }

    /**
     * Trigger performance alert
     */
    private function triggerAlert(string $alertType, array $data): void
    {
        try {
            // Log alert
            Log::warning('Performance alert triggered', [
                'alert_type' => $alertType,
                'data' => $data,
                'threshold' => self::SLOW_QUERY_THRESHOLD,
            ]);

            // Here you could integrate with external alerting systems like:
            // - Slack notifications
            // - Email alerts
            // - PagerDuty
            // - DataDog/New Relic alerts

            // For now, we'll just log it
            // In production, you might want to dispatch an alert job

        } catch (\Exception $e) {
            Log::error('Failed to trigger performance alert', [
                'alert_type' => $alertType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clean up old performance metrics
     */
    public function cleanupOldMetrics(int $daysToKeep = 30): int
    {
        try {
            $deletedCount = \App\Models\PerformanceMetric::where('measured_at', '<', now()->subDays($daysToKeep))
                ->delete();

            Log::info('Cleaned up old performance metrics', [
                'deleted_count' => $deletedCount,
                'days_kept' => $daysToKeep,
            ]);

            return $deletedCount;

        } catch (\Exception $e) {
            Log::error('Failed to cleanup old performance metrics', [
                'error' => $e->getMessage(),
                'days_to_keep' => $daysToKeep,
            ]);

            return 0;
        }
    }

    /**
     * Get performance trends
     */
    public function getPerformanceTrends(string $timeRange = '24_hours'): array
    {
        $hours = $this->parseTimeRange($timeRange);

        return [
            'api_response_trend' => $this->getTrendData('api_response', $hours),
            'database_query_trend' => $this->getTrendData('database_query', $hours),
            'cache_hit_trend' => $this->getCacheHitTrend($hours),
            'system_resource_trend' => $this->getSystemResourceTrend($hours),
        ];
    }

    /**
     * Get trend data for a metric type
     */
    private function getTrendData(string $metricType, int $hours): array
    {
        return \App\Models\PerformanceMetric::select(
                \DB::raw('DATE_FORMAT(measured_at, "%Y-%m-%d %H:00:00") as hour'),
                \DB::raw('AVG(value) as avg_value'),
                \DB::raw('COUNT(*) as count')
            )
            ->where('metric_type', $metricType)
            ->where('measured_at', '>=', now()->subHours($hours))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->toArray();
    }

    /**
     * Get cache hit trend
     */
    private function getCacheHitTrend(int $hours): array
    {
        return \App\Models\PerformanceMetric::select(
                \DB::raw('DATE_FORMAT(measured_at, "%Y-%m-%d %H:00:00") as hour'),
                \DB::raw('AVG(CASE WHEN context->cache_hit = true THEN 1 ELSE 0 END) * 100 as hit_ratio')
            )
            ->where('metric_type', 'cache_operation')
            ->where('measured_at', '>=', now()->subHours($hours))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->toArray();
    }

    /**
     * Get system resource trend
     */
    private function getSystemResourceTrend(int $hours): array
    {
        return \App\Models\PerformanceMetric::select(
                \DB::raw('DATE_FORMAT(measured_at, "%Y-%m-%d %H:00:00") as hour'),
                \DB::raw('AVG(CASE WHEN metric_name = "memory_usage" THEN value END) as avg_memory'),
                \DB::raw('AVG(CASE WHEN metric_name = "cpu_usage" THEN value END) as avg_cpu')
            )
            ->where('metric_type', 'system_resource')
            ->where('measured_at', '>=', now()->subHours($hours))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->toArray();
    }
}