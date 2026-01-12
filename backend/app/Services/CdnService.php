<?php

namespace App\Services;

use App\Models\Media;
use App\Models\MediaConversion;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CdnService
{
    /**
     * CDN configuration
     */
    private const CACHE_TTL = 3600; // 1 hour
    private const PURGE_BATCH_SIZE = 100;

    /**
     * Upload media to CDN
     */
    public function uploadToCdn(Media $media): bool
    {
        try {
            $cdnDisk = Storage::disk('cdn');

            // Upload original file
            $originalContent = Storage::disk('public')->get($media->path);
            $cdnPath = 'media/' . $media->file_name;

            if (!$cdnDisk->put($cdnPath, $originalContent)) {
                throw new \Exception('Failed to upload original file to CDN');
            }

            // Update media with CDN URL
            $cdnUrl = $cdnDisk->url($cdnPath);
            $media->update(['cdn_url' => $cdnUrl]);

            // Upload conversions
            foreach ($media->conversions()->completed()->get() as $conversion) {
                $this->uploadConversionToCdn($conversion, $cdnDisk);
            }

            // Cache the CDN URLs
            $this->cacheCdnUrls($media);

            Log::info('Media uploaded to CDN successfully', [
                'media_id' => $media->id,
                'cdn_url' => $cdnUrl,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to upload media to CDN', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Upload conversion to CDN
     */
    private function uploadConversionToCdn(MediaConversion $conversion, $cdnDisk): void
    {
        try {
            $conversionContent = Storage::disk('public')->get($conversion->path);
            $conversionCdnPath = 'media/conversions/' . $conversion->file_name;

            if ($cdnDisk->put($conversionCdnPath, $conversionContent)) {
                $conversion->update([
                    'cdn_url' => $cdnDisk->url($conversionCdnPath),
                ]);

                Log::info('Conversion uploaded to CDN', [
                    'conversion_id' => $conversion->id,
                    'cdn_url' => $conversion->cdn_url,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to upload conversion to CDN', [
                'conversion_id' => $conversion->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Purge media from CDN cache
     */
    public function purgeFromCdn(Media $media): bool
    {
        try {
            $urlsToPurge = [];

            // Add original file URL
            if ($media->cdn_url) {
                $urlsToPurge[] = $media->cdn_url;
            }

            // Add conversion URLs
            foreach ($media->conversions as $conversion) {
                if ($conversion->cdn_url) {
                    $urlsToPurge[] = $conversion->cdn_url;
                }
            }

            if (empty($urlsToPurge)) {
                return true;
            }

            // Purge URLs in batches
            $success = true;
            foreach (array_chunk($urlsToPurge, self::PURGE_BATCH_SIZE) as $batch) {
                if (!$this->purgeBatch($batch)) {
                    $success = false;
                }
            }

            // Clear local cache
            $this->clearCdnCache($media);

            Log::info('Media purged from CDN', [
                'media_id' => $media->id,
                'urls_purged' => count($urlsToPurge),
            ]);

            return $success;

        } catch (\Exception $e) {
            Log::error('Failed to purge media from CDN', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Purge batch of URLs from CDN
     */
    private function purgeBatch(array $urls): bool
    {
        try {
            // This would implement CDN-specific purge logic
            // For Cloudflare, you would use their API
            // For AWS CloudFront, you would use invalidation API

            // Placeholder implementation
            Log::info('Purging CDN batch', [
                'urls' => $urls,
            ]);

            // Simulate API call delay
            sleep(1);

            return true;

        } catch (\Exception $e) {
            Log::error('CDN purge batch failed', [
                'urls' => $urls,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Cache CDN URLs for performance
     */
    private function cacheCdnUrls(Media $media): void
    {
        $cacheKey = "cdn_urls:{$media->id}";
        $urls = [
            'original' => $media->cdn_url,
            'conversions' => [],
        ];

        foreach ($media->conversions as $conversion) {
            $urls['conversions'][$conversion->conversion_name] = $conversion->cdn_url;
        }

        Cache::put($cacheKey, $urls, now()->addHours(self::CACHE_TTL));
    }

    /**
     * Clear CDN cache for media
     */
    private function clearCdnCache(Media $media): void
    {
        $cacheKey = "cdn_urls:{$media->id}";
        Cache::forget($cacheKey);
    }

    /**
     * Get CDN URLs from cache
     */
    public function getCachedCdnUrls(int $mediaId): ?array
    {
        $cacheKey = "cdn_urls:{$mediaId}";
        return Cache::get($cacheKey);
    }

    /**
     * Optimize image for CDN delivery
     */
    public function optimizeForCdn(Media $media): array
    {
        $optimizations = [];

        try {
            // WebP conversion if not exists
            if (!$media->getConversion('webp')) {
                $this->createWebpConversion($media);
                $optimizations[] = 'webp_conversion';
            }

            // AVIF conversion if not exists
            if (!$media->getConversion('avif')) {
                $this->createAvifConversion($media);
                $optimizations[] = 'avif_conversion';
            }

            // Responsive images
            $this->createResponsiveImages($media);
            $optimizations[] = 'responsive_images';

            Log::info('CDN optimization completed', [
                'media_id' => $media->id,
                'optimizations' => $optimizations,
            ]);

        } catch (\Exception $e) {
            Log::error('CDN optimization failed', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $optimizations;
    }

    /**
     * Create WebP conversion for CDN
     */
    private function createWebpConversion(Media $media): void
    {
        // This would use the MediaService to create WebP conversion
        // Implementation depends on the image processing library used
    }

    /**
     * Create AVIF conversion for CDN
     */
    private function createAvifConversion(Media $media): void
    {
        // This would use the MediaService to create AVIF conversion
        // Implementation depends on the image processing library used
    }

    /**
     * Create responsive image sizes
     */
    private function createResponsiveImages(Media $media): void
    {
        $responsiveSizes = [
            'small' => ['width' => 480, 'height' => 360],
            'medium' => ['width' => 768, 'height' => 576],
            'large' => ['width' => 1024, 'height' => 768],
            'xlarge' => ['width' => 1440, 'height' => 1080],
        ];

        foreach ($responsiveSizes as $size => $dimensions) {
            if (!$media->getConversion($size)) {
                $media->addConversionSize($size, $dimensions);
            }
        }

        $media->save();
    }

    /**
     * Get CDN statistics
     */
    public function getCdnStats(): array
    {
        return Cache::remember('cdn_stats', now()->addMinutes(5), function() {
            return [
                'total_files' => Media::whereNotNull('cdn_url')->count(),
                'total_size' => Media::whereNotNull('cdn_url')->sum('size'),
                'cache_hit_ratio' => $this->calculateCacheHitRatio(),
                'average_response_time' => $this->getAverageResponseTime(),
            ];
        });
    }

    /**
     * Calculate CDN cache hit ratio
     */
    private function calculateCacheHitRatio(): float
    {
        // This would integrate with CDN analytics API
        // Placeholder implementation
        return 0.85; // 85% hit ratio
    }

    /**
     * Get average CDN response time
     */
    private function getAverageResponseTime(): float
    {
        // This would integrate with CDN monitoring
        // Placeholder implementation
        return 150; // 150ms average
    }

    /**
     * Health check for CDN connectivity
     */
    public function healthCheck(): array
    {
        $startTime = microtime(true);

        try {
            $cdnDisk = Storage::disk('cdn');
            $testFile = 'health-check-' . time() . '.txt';
            $testContent = 'CDN Health Check - ' . now()->toISOString();

            // Try to write a test file
            $cdnDisk->put($testFile, $testContent);

            // Try to read it back
            $readContent = $cdnDisk->get($testFile);

            // Clean up
            $cdnDisk->delete($testFile);

            $responseTime = (microtime(true) - $startTime) * 1000;

            return [
                'status' => $readContent === $testContent ? 'healthy' : 'unhealthy',
                'response_time_ms' => round($responseTime, 2),
                'timestamp' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ];
        }
    }
}