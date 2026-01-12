<?php

namespace App\Http\Middleware;

use App\Services\PerformanceMonitorService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitorMiddleware
{
    public function __construct(
        private PerformanceMonitorService $performanceMonitor
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only monitor API routes
        if (!$this->shouldMonitor($request)) {
            return $next($request);
        }

        // Record start time and memory usage
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Add request ID for tracking
        $requestId = $this->generateRequestId();
        $request->headers->set('X-Request-ID', $requestId);

        try {
            // Process the request
            $response = $next($request);

            // Calculate performance metrics
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);

            $responseTime = $endTime - $startTime;
            $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB

            // Monitor API response
            $this->performanceMonitor->monitorApiResponse(
                $request->path(),
                $request->method(),
                $responseTime,
                [
                    'request_id' => $requestId,
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'status_code' => $response->getStatusCode(),
                    'memory_used_mb' => round($memoryUsed, 2),
                    'query_count' => $this->getQueryCount(),
                    'cache_operations' => $this->getCacheOperationsCount(),
                ]
            );

            // Add performance headers to response
            $response->headers->set('X-Response-Time', round($responseTime * 1000, 2) . 'ms');
            $response->headers->set('X-Request-ID', $requestId);
            $response->headers->set('X-Memory-Used', round($memoryUsed, 2) . 'MB');

            // Log slow responses
            if ($responseTime > 1.0) { // More than 1 second
                Log::warning('Slow API response detected', [
                    'request_id' => $requestId,
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'response_time' => round($responseTime, 3),
                    'memory_used' => round($memoryUsed, 2),
                    'status_code' => $response->getStatusCode(),
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                ]);
            }

            return $response;

        } catch (\Exception $e) {
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);

            $responseTime = $endTime - $startTime;
            $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;

            // Monitor failed request
            $this->performanceMonitor->monitorApiResponse(
                $request->path(),
                $request->method(),
                $responseTime,
                [
                    'request_id' => $requestId,
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e),
                    'memory_used_mb' => round($memoryUsed, 2),
                    'status_code' => 500,
                ]
            );

            Log::error('API request failed', [
                'request_id' => $requestId,
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'response_time' => round($responseTime, 3),
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            throw $e;
        }
    }

    /**
     * Determine if request should be monitored
     */
    private function shouldMonitor(Request $request): bool
    {
        // Only monitor API routes
        if (!$request->is('api/*')) {
            return false;
        }

        // Skip health check endpoints
        if ($request->is('api/health*')) {
            return false;
        }

        // Skip performance monitoring endpoints to avoid infinite loops
        if ($request->is('api/performance*') || $request->is('api/metrics*')) {
            return false;
        }

        return true;
    }

    /**
     * Generate unique request ID
     */
    private function generateRequestId(): string
    {
        return uniqid('req_', true);
    }

    /**
     * Get database query count
     */
    private function getQueryCount(): int
    {
        $queries = app('db')->getQueryLog();

        return count($queries);
    }

    /**
     * Get cache operations count
     */
    private function getCacheOperationsCount(): int
    {
        // This would require a custom cache event listener
        // For now, return 0 as placeholder
        return 0;
    }

    /**
     * Monitor database queries
     */
    private function monitorDatabaseQueries(): void
    {
        // Listen for database queries
        app('db')->listen(function ($query) {
            $executionTime = $query->time / 1000; // Convert to seconds

            $this->performanceMonitor->monitorDatabaseQuery(
                $query->sql,
                $executionTime,
                [
                    'connection' => $query->connectionName,
                    'bindings' => $query->bindings,
                ]
            );
        });
    }

    /**
     * Monitor cache operations
     */
    private function monitorCacheOperations(): void
    {
        // Listen for cache events
        $cache = app('cache');

        // This would require custom cache event listeners
        // Implementation depends on the cache driver being used
    }
}
