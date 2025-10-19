<?php

namespace App\Services;

use App\Models\Media;
use App\Models\MediaConversion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class MediaService
{
    /**
     * Default conversion sizes for images
     */
    private const DEFAULT_CONVERSIONS = [
        'thumb' => ['width' => 300, 'height' => 300, 'fit' => 'crop'],
        'medium' => ['width' => 800, 'height' => 600, 'fit' => 'resize'],
        'large' => ['width' => 1200, 'height' => 900, 'fit' => 'resize'],
        'webp' => ['format' => 'webp', 'quality' => 85],
        'avif' => ['format' => 'avif', 'quality' => 80],
    ];

    /**
     * Supported image formats
     */
    private const SUPPORTED_FORMATS = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/avif',
        'image/bmp',
        'image/tiff',
    ];

    /**
     * Maximum file size in bytes (10MB)
     */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Upload and process a media file
     */
    public function uploadMedia(UploadedFile $file, array $options = [], ?int $userId = null): Media
    {
        // Validate file
        $this->validateFile($file);

        DB::beginTransaction();
        try {
            // Generate unique filename
            $fileName = $this->generateUniqueFileName($file);

            // Store file
            $path = $file->storeAs('media', $fileName, 'public');

            // Get file info
            $fileHash = hash_file('sha256', $file->getPathname());
            $imageInfo = $this->getImageInfo($file);

            // Create media record
            $media = Media::create([
                'name' => $options['name'] ?? $file->getClientOriginalName(),
                'file_name' => $fileName,
                'mime_type' => $file->getMimeType(),
                'path' => $path,
                'disk' => 'public',
                'file_hash' => $fileHash,
                'size' => $file->getSize(),
                'metadata' => [
                    'original_name' => $file->getClientOriginalName(),
                    'uploaded_at' => now()->toISOString(),
                ],
                'alt' => $options['alt'] ?? null,
                'title' => $options['title'] ?? null,
                'description' => $options['description'] ?? null,
                'caption' => $options['caption'] ?? null,
                'width' => $imageInfo['width'] ?? null,
                'height' => $imageInfo['height'] ?? null,
                'uploaded_by' => $userId,
                'conversion_sizes' => self::DEFAULT_CONVERSIONS,
            ]);

            // Mark as processing
            $media->markAsProcessing();

            // Dispatch background job for processing
            ProcessMediaJob::dispatch($media, $options);

            DB::commit();

            Log::info('Media uploaded successfully', [
                'media_id' => $media->id,
                'file_name' => $fileName,
                'user_id' => $userId,
            ]);

            return $media;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Media upload failed', [
                'error' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName(),
                'user_id' => $userId,
            ]);

            throw $e;
        }
    }

    /**
     * Process media file (conversions, optimization, etc.)
     */
    public function processMedia(Media $media, array $options = []): void
    {
        try {
            $filePath = $media->getFullPath();

            if (!file_exists($filePath)) {
                throw new \Exception("Media file not found: {$filePath}");
            }

            // Load image for processing
            $image = Image::make($filePath);

            // Generate conversions
            $this->generateConversions($media, $image, $options);

            // Optimize original image
            $this->optimizeOriginal($media, $image);

            // Upload to R2 if configured
            if (config('filesystems.disks.r2')) {
                $this->uploadToR2($media);
            }

            // Mark as completed
            $media->markAsCompleted();

            Log::info('Media processed successfully', [
                'media_id' => $media->id,
                'conversions_generated' => $media->conversions()->count(),
            ]);

        } catch (\Exception $e) {
            $media->markAsFailed($e->getMessage());

            Log::error('Media processing failed', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate media conversions (thumbnails, different sizes, formats)
     */
    private function generateConversions(Media $media, $image, array $options = []): void
    {
        $conversions = $media->conversion_sizes ?? self::DEFAULT_CONVERSIONS;

        foreach ($conversions as $conversionName => $conversionOptions) {
            try {
                $conversion = $this->createConversion($media, $image, $conversionName, $conversionOptions);
                $media->conversions()->save($conversion);

                Log::info('Media conversion generated', [
                    'media_id' => $media->id,
                    'conversion_name' => $conversionName,
                    'conversion_id' => $conversion->id,
                ]);

            } catch (\Exception $e) {
                Log::warning('Failed to generate conversion', [
                    'media_id' => $media->id,
                    'conversion_name' => $conversionName,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Create a single conversion
     */
    private function createConversion(Media $media, $image, string $conversionName, array $options): MediaConversion
    {
        // Clone image for manipulation
        $conversionImage = clone $image;

        // Apply conversion options
        if (isset($options['width']) && isset($options['height'])) {
            if (($options['fit'] ?? 'resize') === 'crop') {
                $conversionImage->fit($options['width'], $options['height']);
            } else {
                $conversionImage->resize($options['width'], $options['height'], function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
        }

        // Apply format conversion
        if (isset($options['format'])) {
            $conversionImage->encode($options['format'], $options['quality'] ?? 90);
        }

        // Generate filename for conversion
        $extension = isset($options['format']) ? $options['format'] : $image->extension;
        $conversionFileName = pathinfo($media->file_name, PATHINFO_FILENAME) . "_{$conversionName}." . $extension;

        // Store conversion
        $conversionPath = 'media/conversions/' . $conversionFileName;
        Storage::disk('public')->put($conversionPath, $conversionImage->stream());

        // Calculate compression ratio
        $originalSize = $media->size;
        $conversionSize = Storage::disk('public')->size($conversionPath);
        $compressionRatio = $originalSize > 0 ? $conversionSize / $originalSize : 1;

        return new MediaConversion([
            'conversion_name' => $conversionName,
            'conversion_type' => isset($options['format']) ? 'format' : 'resize',
            'file_name' => $conversionFileName,
            'mime_type' => $this->getMimeTypeForExtension($extension),
            'path' => $conversionPath,
            'disk' => 'public',
            'size' => $conversionSize,
            'width' => $conversionImage->width(),
            'height' => $conversionImage->height(),
            'conversion_options' => $options,
            'status' => 'completed',
            'compression_ratio' => $compressionRatio,
            'quality_score' => $this->calculateQualityScore($conversionImage),
            'generated_at' => now(),
        ]);
    }

    /**
     * Optimize the original image
     */
    private function optimizeOriginal(Media $media, $image): void
    {
        try {
            $originalSize = $media->size;
            $optimizedPath = 'media/optimized/' . $media->file_name;

            // Optimize based on format
            switch ($image->mime()) {
                case 'image/jpeg':
                    $image->encode('jpg', 85);
                    break;
                case 'image/png':
                    $image->encode('png', 8); // Reduce color palette
                    break;
                default:
                    $image->encode($image->extension, 90);
            }

            Storage::disk('public')->put($optimizedPath, $image->stream());

            $optimizedSize = Storage::disk('public')->size($optimizedPath);
            $optimizationRatio = $originalSize > 0 ? $optimizedSize / $originalSize : 1;

            // Update media record
            $media->updateOptimizationInfo(true, $optimizationRatio);

            Log::info('Original image optimized', [
                'media_id' => $media->id,
                'original_size' => $originalSize,
                'optimized_size' => $optimizedSize,
                'compression_ratio' => $optimizationRatio,
            ]);

        } catch (\Exception $e) {
            Log::warning('Failed to optimize original image', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Upload media to R2 bucket
     */
    private function uploadToR2(Media $media): void
    {
        try {
            $r2Disk = Storage::disk('r2');

            // Upload original file
            $originalContent = Storage::disk('public')->get($media->path);
            $r2Path = 'media/' . $media->file_name;
            $r2Disk->put($r2Path, $originalContent);

            // Update media with R2 URL
            $media->update([
                'cloud_url' => $r2Disk->url($r2Path),
            ]);

            // Upload conversions
            foreach ($media->conversions as $conversion) {
                $conversionContent = Storage::disk('public')->get($conversion->path);
                $conversionR2Path = 'media/conversions/' . $conversion->file_name;
                $r2Disk->put($conversionR2Path, $conversionContent);

                $conversion->update([
                    'cloud_url' => $r2Disk->url($conversionR2Path),
                    'uploaded_at' => now(),
                ]);
            }

            Log::info('Media uploaded to R2', [
                'media_id' => $media->id,
                'r2_path' => $r2Path,
            ]);

        } catch (\Exception $e) {
            Log::warning('Failed to upload to R2', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \Exception("File size exceeds maximum allowed size of " . (self::MAX_FILE_SIZE / 1024 / 1024) . "MB");
        }

        // Check MIME type
        if (!in_array($file->getMimeType(), self::SUPPORTED_FORMATS)) {
            throw new \Exception("Unsupported file format: " . $file->getMimeType());
        }

        // Check if file is actually an image
        $imageInfo = $this->getImageInfo($file);
        if (!$imageInfo) {
            throw new \Exception("Invalid image file");
        }
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFileName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y_m_d_H_i_s');
        $random = Str::random(8);

        return "{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Get image information
     */
    private function getImageInfo(UploadedFile $file): ?array
    {
        try {
            $image = Image::make($file->getPathname());

            return [
                'width' => $image->width(),
                'height' => $image->height(),
                'mime' => $image->mime(),
                'extension' => $image->extension,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get MIME type for file extension
     */
    private function getMimeTypeForExtension(string $extension): string
    {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'bmp' => 'image/bmp',
            'tiff' => 'image/tiff',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Calculate image quality score
     */
    private function calculateQualityScore($image): float
    {
        // Simple quality score based on file size and dimensions
        // In a real implementation, you might use more sophisticated algorithms
        $width = $image->width();
        $height = $image->height();
        $pixelCount = $width * $height;

        // Base score on pixel density and format efficiency
        $baseScore = min(1.0, $pixelCount / 2000000); // Normalize to 2MP

        return round($baseScore, 2);
    }

    /**
     * Delete media and all conversions
     */
    public function deleteMedia(Media $media): bool
    {
        try {
            return $media->deleteWithConversions();
        } catch (\Exception $e) {
            Log::error('Failed to delete media', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get media with specific conversion
     */
    public function getMediaWithConversion(int $mediaId, string $conversionName): ?Media
    {
        return Media::with(['conversions' => function ($query) use ($conversionName) {
            $query->where('conversion_name', $conversionName);
        }])->find($mediaId);
    }

    /**
     * Get optimized media URL (CDN if available, otherwise local)
     */
    public function getOptimizedUrl(Media $media, string $conversionName = null): string
    {
        if ($conversionName) {
            $conversion = $media->getConversion($conversionName);
            if ($conversion && $conversion->cdn_url) {
                return $conversion->cdn_url;
            }
            if ($conversion && $conversion->cloud_url) {
                return $conversion->cloud_url;
            }
        }

        return $media->getUrl();
    }
}