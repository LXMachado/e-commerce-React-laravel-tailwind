<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class CacheOptimizationService
{
    /**
     * Cache TTL configurations
     */
    private const CACHE_TTL = [
        'short' => 300,        // 5 minutes
        'medium' => 1800,      // 30 minutes
        'long' => 7200,        // 2 hours
        'extended' => 21600,   // 6 hours
        'permanent' => 86400,  // 24 hours
    ];

    /**
     * Cache keys for different data types
     */
    private const CACHE_KEYS = [
        'categories' => 'categories:all',
        'products' => 'products:all',
        'featured_products' => 'products:featured',
        'popular_products' => 'products:popular',
        'search_suggestions' => 'search:suggestions',
        'media_metadata' => 'media:metadata',
        'cdn_urls' => 'cdn:urls',
        'performance_stats' => 'performance:stats',
    ];

    /**
     * Warm up essential caches
     */
    public function warmUpCaches(): array
    {
        $warmedCaches = [];
        $startTime = microtime(true);

        try {
            Log::info('Starting cache warm-up process');

            // Warm up category cache
            $warmedCaches[] = $this->warmUpCategoryCache();

            // Warm up product cache
            $warmedCaches[] = $this->warmUpProductCache();

            // Warm up search cache
            $warmedCaches[] = $this->warmUpSearchCache();

            // Warm up media cache
            $warmedCaches[] = $this->warmUpMediaCache();

            $executionTime = microtime(true) - $startTime;

            Log::info('Cache warm-up completed', [
                'warmed_caches' => count($warmedCaches),
                'execution_time' => round($executionTime, 2),
            ]);

            return [
                'success' => true,
                'warmed_caches' => $warmedCaches,
                'execution_time' => round($executionTime, 2),
            ];

        } catch (\Exception $e) {
            Log::error('Cache warm-up failed', [
                'error' => $e->getMessage(),
                'warmed_caches' => $warmedCaches,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'warmed_caches' => $warmedCaches,
            ];
        }
    }

    /**
     * Warm up category cache
     */
    private function warmUpCategoryCache(): string
    {
        $categories = \App\Models\Category::with('products')->get();

        Cache::put(self::CACHE_KEYS['categories'], $categories, self::CACHE_TTL['extended']);

        return 'categories';
    }

    /**
     * Warm up product cache
     */
    private function warmUpProductCache(): string
    {
        // Cache all products
        $products = \App\Models\Product::with(['variants', 'categories'])->get();
        Cache::put(self::CACHE_KEYS['products'], $products, self::CACHE_TTL['medium']);

        // Cache featured products
        $featuredProducts = \App\Models\Product::where('is_featured', true)
            ->with(['variants', 'categories'])
            ->get();
        Cache::put(self::CACHE_KEYS['featured_products'], $featuredProducts, self::CACHE_TTL['long']);

        return 'products';
    }

    /**
     * Warm up search cache
     */
    private function warmUpSearchCache(): string
    {
        // Cache popular search terms
        $popularSearches = \App\Models\Product::select('name')
            ->distinct()
            ->pluck('name')
            ->take(100)
            ->toArray();

        Cache::put(self::CACHE_KEYS['search_suggestions'], $popularSearches, self::CACHE_TTL['permanent']);

        return 'search_suggestions';
    }

    /**
     * Warm up media cache
     */
    private function warmUpMediaCache(): string
    {
        $mediaMetadata = \App\Models\Media::select('id', 'file_name', 'mime_type', 'cdn_url', 'cloud_url')
            ->processed()
            ->get()
            ->keyBy('id');

        Cache::put(self::CACHE_KEYS['media_metadata'], $mediaMetadata, self::CACHE_TTL['extended']);

        return 'media_metadata';
    }

    /**
     * Optimize cache configuration
     */
    public function optimizeCacheConfiguration(): array
    {
        $optimizations = [];

        try {
            // Optimize Redis configuration if using Redis
            if (config('cache.default') === 'redis') {
                $optimizations[] = $this->optimizeRedisConfiguration();
            }

            // Set up cache tags for better organization
            $optimizations[] = $this->setupCacheTags();

            // Configure cache prefixes
            $optimizations[] = $this->configureCachePrefixes();

            Log::info('Cache configuration optimized', [
                'optimizations' => $optimizations,
            ]);

            return $optimizations;

        } catch (\Exception $e) {
            Log::error('Cache optimization failed', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Optimize Redis configuration
     */
    private function optimizeRedisConfiguration(): string
    {
        // Set Redis optimization parameters
        config([
            'database.redis.options.prefix' => 'weekender_cache:',
            'database.redis.options.serializer' => 'php', // Use PHP serializer for better performance
        ]);

        return 'redis_configuration';
    }

    /**
     * Set up cache tags
     */
    private function setupCacheTags(): string
    {
        // Configure cache tags for different data types
        $tagConfigurations = [
            'categories' => ['category', 'navigation', 'menu'],
            'products' => ['product', 'catalog', 'inventory'],
            'media' => ['media', 'images', 'assets'],
            'search' => ['search', 'query', 'suggestions'],
            'performance' => ['performance', 'metrics', 'monitoring'],
        ];

        Cache::put('cache_tags_config', $tagConfigurations, self::CACHE_TTL['permanent']);

        return 'cache_tags';
    }

    /**
     * Configure cache prefixes
     */
    private function configureCachePrefixes(): string
    {
        $prefixes = [
            'api' => 'api:',
            'media' => 'media:',
            'search' => 'search:',
            'performance' => 'perf:',
            'cdn' => 'cdn:',
        ];

        Cache::put('cache_prefixes_config', $prefixes, self::CACHE_TTL['permanent']);

        return 'cache_prefixes';
    }

    /**
     * Invalidate related caches
     */
    public function invalidateRelatedCaches(string $cacheType, array $identifiers = []): int
    {
        $invalidatedCount = 0;

        try {
            switch ($cacheType) {
                case 'category':
                    $invalidatedCount += $this->invalidateCategoryCaches($identifiers);
                    break;

                case 'product':
                    $invalidatedCount += $this->invalidateProductCaches($identifiers);
                    break;

                case 'media':
                    $invalidatedCount += $this->invalidateMediaCaches($identifiers);
                    break;

                case 'search':
                    $invalidatedCount += $this->invalidateSearchCaches($identifiers);
                    break;

                default:
                    Log::warning('Unknown cache type for invalidation', [
                        'cache_type' => $cacheType,
                    ]);
            }

            Log::info('Related caches invalidated', [
                'cache_type' => $cacheType,
                'identifiers' => $identifiers,
                'invalidated_count' => $invalidatedCount,
            ]);

            return $invalidatedCount;

        } catch (\Exception $e) {
            Log::error('Cache invalidation failed', [
                'cache_type' => $cacheType,
                'identifiers' => $identifiers,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Invalidate category caches
     */
    private function invalidateCategoryCaches(array $identifiers): int
    {
        $count = 0;

        // Invalidate main category cache
        Cache::forget(self::CACHE_KEYS['categories']);
        $count++;

        // Invalidate related product caches
        if (!empty($identifiers)) {
            foreach ($identifiers as $categoryId) {
                Cache::forget("category:{$categoryId}:products");
                $count++;
            }
        }

        return $count;
    }

    /**
     * Invalidate product caches
     */
    private function invalidateProductCaches(array $identifiers): int
    {
        $count = 0;

        // Invalidate main product caches
        Cache::forget(self::CACHE_KEYS['products']);
        Cache::forget(self::CACHE_KEYS['featured_products']);
        $count += 2;

        // Invalidate specific product caches
        if (!empty($identifiers)) {
            foreach ($identifiers as $productId) {
                Cache::forget("product:{$productId}");
                Cache::forget("product:{$productId}:variants");
                $count += 2;
            }
        }

        return $count;
    }

    /**
     * Invalidate media caches
     */
    private function invalidateMediaCaches(array $identifiers): int
    {
        $count = 0;

        // Invalidate main media cache
        Cache::forget(self::CACHE_KEYS['media_metadata']);
        $count++;

        // Invalidate CDN URL cache
        Cache::forget(self::CACHE_KEYS['cdn_urls']);
        $count++;

        // Invalidate specific media caches
        if (!empty($identifiers)) {
            foreach ($identifiers as $mediaId) {
                Cache::forget("media:{$mediaId}");
                Cache::forget("media:{$mediaId}:conversions");
                $count += 2;
            }
        }

        return $count;
    }

    /**
     * Invalidate search caches
     */
    private function invalidateSearchCaches(array $identifiers): int
    {
        $count = 0;

        // Invalidate search suggestions
        Cache::forget(self::CACHE_KEYS['search_suggestions']);
        $count++;

        // Invalidate search results if query provided
        if (!empty($identifiers) && isset($identifiers['query'])) {
            Cache::forget("search:results:" . md5($identifiers['query']));
            $count++;
        }

        return $count;
    }

    /**
     * Get cache statistics
     */
    public function getCacheStatistics(): array
    {
        try {
            $cacheDriver = config('cache.default');

            switch ($cacheDriver) {
                case 'redis':
                    return $this->getRedisStatistics();

                case 'file':
                    return $this->getFileCacheStatistics();

                default:
                    return $this->getGenericCacheStatistics();
            }

        } catch (\Exception $e) {
            Log::error('Failed to get cache statistics', [
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => $e->getMessage(),
                'driver' => $cacheDriver,
            ];
        }
    }

    /**
     * Get Redis cache statistics
     */
    private function getRedisStatistics(): array
    {
        try {
            $redis = Cache::getStore()->getRedis();

            // Get Redis info
            $info = $redis->info();

            return [
                'driver' => 'redis',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'used_memory_mb' => round(($info['used_memory'] ?? 0) / 1024 / 1024, 2),
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'evicted_keys' => $info['evicted_keys'] ?? 0,
                'expired_keys' => $info['expired_keys'] ?? 0,
            ];

        } catch (\Exception $e) {
            return [
                'driver' => 'redis',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get file cache statistics
     */
    private function getFileCacheStatistics(): array
    {
        $cachePath = storage_path('framework/cache/data');

        if (!is_dir($cachePath)) {
            return [
                'driver' => 'file',
                'cache_directory' => 'not_found',
                'file_count' => 0,
            ];
        }

        $files = glob($cachePath . '/*/*');
        $fileCount = is_array($files) ? count($files) : 0;

        return [
            'driver' => 'file',
            'cache_directory' => $cachePath,
            'file_count' => $fileCount,
            'total_size_mb' => $this->getDirectorySize($cachePath),
        ];
    }

    /**
     * Get generic cache statistics
     */
    private function getGenericCacheStatistics(): array
    {
        return [
            'driver' => config('cache.default'),
            'available' => true,
        ];
    }

    /**
     * Get directory size in MB
     */
    private function getDirectorySize(string $path): float
    {
        try {
            $size = 0;

            foreach (glob($path . '/*') as $file) {
                $size += is_file($file) ? filesize($file) : $this->getDirectorySize($file);
            }

            return round($size / 1024 / 1024, 2);

        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Clean up expired cache entries
     */
    public function cleanupExpiredCache(): array
    {
        $startTime = microtime(true);
        $cleanedCount = 0;

        try {
            // Laravel automatically cleans up expired cache entries
            // But we can force cleanup for file-based cache
            if (config('cache.default') === 'file') {
                $cleanedCount = $this->cleanupFileCache();
            }

            // Clean up our custom cache keys if they're corrupted
            $this->cleanupCorruptedCache();

            $executionTime = microtime(true) - $startTime;

            Log::info('Cache cleanup completed', [
                'cleaned_count' => $cleanedCount,
                'execution_time' => round($executionTime, 2),
            ]);

            return [
                'success' => true,
                'cleaned_count' => $cleanedCount,
                'execution_time' => round($executionTime, 2),
            ];

        } catch (\Exception $e) {
            Log::error('Cache cleanup failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clean up file-based cache
     */
    private function cleanupFileCache(): int
    {
        $cachePath = storage_path('framework/cache/data');
        $cleanedCount = 0;

        if (!is_dir($cachePath)) {
            return $cleanedCount;
        }

        foreach (glob($cachePath . '/*/*') as $file) {
            if (is_file($file)) {
                $modified = filemtime($file);
                $age = time() - $modified;

                // Delete files older than 24 hours
                if ($age > 86400) {
                    if (unlink($file)) {
                        $cleanedCount++;
                    }
                }
            }
        }

        return $cleanedCount;
    }

    /**
     * Clean up corrupted cache entries
     */
    private function cleanupCorruptedCache(): void
    {
        $corruptedKeys = [];

        // Check for corrupted cache keys
        foreach (self::CACHE_KEYS as $key) {
            try {
                $value = Cache::get($key);

                // If we can't retrieve the value, it might be corrupted
                if ($value === null && !Cache::has($key)) {
                    $corruptedKeys[] = $key;
                }

            } catch (\Exception $e) {
                $corruptedKeys[] = $key;
            }
        }

        // Remove corrupted keys
        foreach ($corruptedKeys as $key) {
            Cache::forget($key);
        }

        if (!empty($corruptedKeys)) {
            Log::warning('Corrupted cache keys cleaned up', [
                'corrupted_keys' => $corruptedKeys,
            ]);
        }
    }

    /**
     * Preload critical resources
     */
    public function preloadCriticalResources(): array
    {
        $preloaded = [];

        try {
            // Preload critical CSS/JS if using CDN
            if (config('filesystems.disks.cdn')) {
                $preloaded[] = $this->preloadCdnAssets();
            }

            // Preload critical API data
            $preloaded[] = $this->preloadCriticalApiData();

            Log::info('Critical resources preloaded', [
                'preloaded_resources' => $preloaded,
            ]);

            return $preloaded;

        } catch (\Exception $e) {
            Log::error('Critical resource preload failed', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Preload CDN assets
     */
    private function preloadCdnAssets(): string
    {
        // This would preload critical CSS/JS files from CDN
        // Implementation depends on your asset pipeline

        return 'cdn_assets';
    }

    /**
     * Preload critical API data
     */
    private function preloadCriticalApiData(): string
    {
        // Preload essential data for faster page loads
        $this->warmUpCategoryCache();
        $this->warmUpProductCache();

        return 'critical_api_data';
    }

    /**
     * Monitor cache performance
     */
    public function monitorCachePerformance(): array
    {
        try {
            $stats = $this->getCacheStatistics();

            // Calculate cache hit ratio if available
            if (isset($stats['keyspace_hits'], $stats['keyspace_misses'])) {
                $total = $stats['keyspace_hits'] + $stats['keyspace_misses'];
                $hitRatio = $total > 0 ? $stats['keyspace_hits'] / $total : 0;
            } else {
                $hitRatio = 0;
            }

            return [
                'statistics' => $stats,
                'hit_ratio' => round($hitRatio, 4),
                'recommendations' => $this->getCacheRecommendations($stats),
                'timestamp' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            Log::error('Cache performance monitoring failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ];
        }
    }

    /**
     * Get cache recommendations
     */
    private function getCacheRecommendations(array $stats): array
    {
        $recommendations = [];

        // Check memory usage for Redis
        if (isset($stats['used_memory_mb']) && $stats['used_memory_mb'] > 100) {
            $recommendations[] = 'Consider increasing Redis memory limit or implementing cache eviction policy';
        }

        // Check eviction rate
        if (isset($stats['evicted_keys']) && $stats['evicted_keys'] > 1000) {
            $recommendations[] = 'High cache eviction rate detected. Consider increasing cache size or adjusting TTL values';
        }

        // Check hit ratio
        if (isset($stats['hit_ratio']) && $stats['hit_ratio'] < 0.8) {
            $recommendations[] = 'Low cache hit ratio. Consider optimizing cache keys or increasing cache size';
        }

        return $recommendations;
    }
}