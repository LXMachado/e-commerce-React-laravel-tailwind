<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PerformanceMonitorService;
use App\Services\CdnService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceController extends Controller
{
    public function __construct(
        private PerformanceMonitorService $performanceMonitor,
        private CdnService $cdnService
    ) {}

    /**
     * Get comprehensive performance metrics
     * GET /api/performance/metrics
     */
    public function metrics(Request $request): JsonResponse
    {
        try {
            $timeRange = $request->get('time_range', '1_hour');

            $metrics = $this->performanceMonitor->getComprehensiveStats($timeRange);

            return response()->json([
                'success' => true,
                'data' => [
                    'time_range' => $timeRange,
                    'generated_at' => now()->toISOString(),
                    'metrics' => $metrics,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve performance metrics', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve performance metrics',
            ], 500);
        }
    }

    /**
     * Get system health status
     * GET /api/performance/health
     */
    public function health(Request $request): JsonResponse
    {
        try {
            $timeRange = $request->get('time_range', '1_hour');

            $health = $this->performanceMonitor->getSystemHealth();
            $cdnHealth = $this->cdnService->healthCheck();

            return response()->json([
                'success' => true,
                'data' => [
                    'system_health' => $health,
                    'cdn_health' => $cdnHealth,
                    'database_health' => $this->getDatabaseHealth(),
                    'cache_health' => $this->getCacheHealth(),
                    'generated_at' => now()->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve system health', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve system health',
            ], 500);
        }
    }

    /**
     * Get performance trends
     * GET /api/performance/trends
     */
    public function trends(Request $request): JsonResponse
    {
        try {
            $timeRange = $request->get('time_range', '24_hours');

            $trends = $this->performanceMonitor->getPerformanceTrends($timeRange);

            return response()->json([
                'success' => true,
                'data' => [
                    'time_range' => $timeRange,
                    'trends' => $trends,
                    'generated_at' => now()->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve performance trends', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve performance trends',
            ], 500);
        }
    }

    /**
     * Get real-time performance dashboard data
     * GET /api/performance/dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $timeRange = $request->get('time_range', '1_hour');

            // Get comprehensive stats
            $stats = $this->performanceMonitor->getComprehensiveStats($timeRange);

            // Get recent slow operations
            $recentSlowOperations = $this->getRecentSlowOperations($timeRange);

            // Get top endpoints by performance
            $topEndpoints = $this->getTopEndpoints($timeRange);

            // Get system resource usage
            $systemResources = $this->getCurrentSystemResources();

            return response()->json([
                'success' => true,
                'data' => [
                    'overview' => [
                        'health_score' => $stats['overall_health']['score'],
                        'health_status' => $stats['overall_health']['status'],
                        'total_requests' => $stats['api_performance']['total_requests'],
                        'average_response_time' => $stats['api_performance']['average_response_time'],
                        'slow_requests_percentage' => $stats['api_performance']['total_requests'] > 0
                            ? ($stats['api_performance']['slow_requests'] / $stats['api_performance']['total_requests']) * 100
                            : 0,
                    ],
                    'api_performance' => [
                        'total_requests' => $stats['api_performance']['total_requests'],
                        'average_response_time' => $stats['api_performance']['average_response_time'],
                        'slow_requests' => $stats['api_performance']['slow_requests'],
                        'top_endpoints' => $topEndpoints,
                    ],
                    'database_performance' => [
                        'total_queries' => $stats['database_performance']['total_queries'],
                        'average_query_time' => $stats['database_performance']['average_query_time'],
                        'slow_queries' => $stats['database_performance']['slow_queries'],
                    ],
                    'cache_performance' => [
                        'hit_ratio' => $stats['cache_performance']['hit_ratio'],
                        'total_operations' => $stats['cache_performance']['total_operations'],
                    ],
                    'system_performance' => [
                        'memory_usage' => $stats['system_performance']['average_memory_usage'],
                        'cpu_usage' => $stats['system_performance']['average_cpu_usage'],
                        'current_resources' => $systemResources,
                    ],
                    'media_performance' => $stats['media_performance'],
                    'recent_slow_operations' => $recentSlowOperations,
                    'alerts' => $this->getActiveAlerts(),
                    'generated_at' => now()->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve dashboard data', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard data',
            ], 500);
        }
    }

    /**
     * Clear performance metrics
     * DELETE /api/performance/metrics
     */
    public function clearMetrics(Request $request): JsonResponse
    {
        try {
            // Check if user has admin privileges
            $user = Auth::user();
            if (!$user || !$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin privileges required',
                ], 403);
            }

            $olderThanDays = $request->get('older_than_days', 7);

            // Clear cache metrics
            $this->performanceMonitor->clearMetrics();

            // Clear database metrics older than specified days
            $deletedCount = \App\Models\PerformanceMetric::where('measured_at', '<', now()->subDays($olderThanDays))->delete();

            Log::info('Performance metrics cleared', [
                'user_id' => $user->id,
                'older_than_days' => $olderThanDays,
                'deleted_count' => $deletedCount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Performance metrics cleared successfully',
                'data' => [
                    'deleted_count' => $deletedCount,
                    'older_than_days' => $olderThanDays,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to clear performance metrics', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear performance metrics',
            ], 500);
        }
    }

    /**
     * Get CDN statistics
     * GET /api/performance/cdn
     */
    public function cdnStats(): JsonResponse
    {
        try {
            $stats = $this->cdnService->getCdnStats();
            $health = $this->cdnService->healthCheck();

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $stats,
                    'health' => $health,
                    'generated_at' => now()->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve CDN statistics', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve CDN statistics',
            ], 500);
        }
    }

    /**
     * Get recent slow operations
     */
    private function getRecentSlowOperations(string $timeRange): array
    {
        $hours = $this->parseTimeRange($timeRange);

        return \App\Models\PerformanceMetric::where('measured_at', '>=', now()->subHours($hours))
            ->where('value', '>', 250) // More than 250ms
            ->orderBy('measured_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($metric) {
                return [
                    'id' => $metric->id,
                    'metric_type' => $metric->metric_type,
                    'operation' => $metric->operation,
                    'endpoint' => $metric->endpoint,
                    'value' => $metric->value,
                    'unit' => $metric->unit,
                    'status' => $metric->status,
                    'measured_at' => $metric->measured_at,
                ];
            })
            ->toArray();
    }

    /**
     * Get top endpoints by performance
     */
    private function getTopEndpoints(string $timeRange): array
    {
        $hours = $this->parseTimeRange($timeRange);

        return \App\Models\PerformanceMetric::select('endpoint', 'method', \DB::raw('AVG(value) as avg_time'), \DB::raw('COUNT(*) as request_count'))
            ->where('metric_type', 'api_response')
            ->where('measured_at', '>=', now()->subHours($hours))
            ->groupBy('endpoint', 'method')
            ->orderBy('avg_time', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($endpoint) {
                return [
                    'endpoint' => $endpoint->endpoint,
                    'method' => $endpoint->method,
                    'average_time' => round($endpoint->avg_time, 2),
                    'request_count' => $endpoint->request_count,
                ];
            })
            ->toArray();
    }

    /**
     * Get current system resources
     */
    private function getCurrentSystemResources(): array
    {
        return [
            'memory_usage_mb' => memory_get_usage(true) / 1024 / 1024,
            'memory_peak_mb' => memory_get_peak_usage(true) / 1024 / 1024,
            'cpu_load' => function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 0,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Get database health status
     */
    private function getDatabaseHealth(): array
    {
        try {
            $startTime = microtime(true);

            // Test database connection
            DB::connection()->getPdo();

            $responseTime = (microtime(true) - $startTime) * 1000;

            return [
                'status' => 'healthy',
                'response_time_ms' => round($responseTime, 2),
                'connection' => 'active',
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'connection' => 'failed',
            ];
        }
    }

    /**
     * Get cache health status
     */
    private function getCacheHealth(): array
    {
        try {
            $startTime = microtime(true);

            // Test cache operations
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'test_value', 10);
            $retrievedValue = Cache::get($testKey);
            Cache::forget($testKey);

            $responseTime = (microtime(true) - $startTime) * 1000;

            return [
                'status' => $retrievedValue === 'test_value' ? 'healthy' : 'unhealthy',
                'response_time_ms' => round($responseTime, 2),
                'operations' => 'working',
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'operations' => 'failed',
            ];
        }
    }

    /**
     * Get active alerts
     */
    private function getActiveAlerts(): array
    {
        $alerts = [];

        try {
            // Check for recent slow operations
            $recentSlowCount = \App\Models\PerformanceMetric::where('measured_at', '>=', now()->subMinutes(5))
                ->where('value', '>', 1000) // More than 1 second
                ->count();

            if ($recentSlowCount > 0) {
                $alerts[] = [
                    'type' => 'slow_operations',
                    'severity' => 'warning',
                    'message' => "{$recentSlowCount} slow operations detected in the last 5 minutes",
                    'timestamp' => now()->toISOString(),
                ];
            }

            // Check system resources
            $memoryUsage = memory_get_usage(true) / 1024 / 1024;
            if ($memoryUsage > 100) { // More than 100MB
                $alerts[] = [
                    'type' => 'high_memory_usage',
                    'severity' => 'warning',
                    'message' => 'High memory usage detected: ' . round($memoryUsage, 2) . 'MB',
                    'timestamp' => now()->toISOString(),
                ];
            }

        } catch (\Exception $e) {
            Log::error('Failed to check active alerts', [
                'error' => $e->getMessage(),
            ]);
        }

        return $alerts;
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
}
