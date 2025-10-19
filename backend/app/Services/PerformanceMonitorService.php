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
     * Store performance metrics
     */
    private function storePerformanceMetrics(array $performanceData): void
    {
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
}