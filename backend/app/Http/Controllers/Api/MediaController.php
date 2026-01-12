<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\MediaService;
use App\Services\PerformanceMonitorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    public function __construct(
        private MediaService $mediaService,
        private PerformanceMonitorService $performanceMonitor
    ) {}

    /**
     * Upload and process media file
     * POST /api/admin/media/upload
     */
    public function upload(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:jpeg,jpg,png,gif,webp,avif,bmp,tiff|max:10240', // 10MB max
                'name' => 'sometimes|string|max:255',
                'alt' => 'sometimes|string|max:255',
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string|max:1000',
                'caption' => 'sometimes|string|max:500',
                'conversions' => 'sometimes|array',
                'conversions.*.name' => 'string|max:50',
                'conversions.*.width' => 'integer|min:1|max:5000',
                'conversions.*.height' => 'integer|min:1|max:5000',
                'conversions.*.format' => 'string|in:jpg,jpeg,png,gif,webp,avif',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Get authenticated user
            $userId = Auth::id();
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            // Upload and process media
            $media = $this->mediaService->uploadMedia(
                $request->file('file'),
                $request->only(['name', 'alt', 'title', 'description', 'caption']),
                $userId
            );

            // Record performance metrics
            $executionTime = microtime(true) - $startTime;
            $this->performanceMonitor->monitorSearchPerformance(
                'media_upload',
                $executionTime,
                [
                    'media_id' => $media->id,
                    'file_size' => $media->size,
                    'mime_type' => $media->mime_type,
                    'user_id' => $userId,
                ]
            );

            Log::info('Media uploaded successfully', [
                'media_id' => $media->id,
                'user_id' => $userId,
                'file_name' => $media->file_name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Media uploaded successfully',
                'data' => [
                    'id' => $media->id,
                    'name' => $media->name,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'width' => $media->width,
                    'height' => $media->height,
                    'status' => $media->status,
                    'processing_status' => $media->processing_status,
                    'url' => $media->getUrl(),
                    'created_at' => $media->created_at,
                ],
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            $executionTime = microtime(true) - $startTime;

            // Record failed performance metrics
            $this->performanceMonitor->monitorSearchPerformance(
                'media_upload_failed',
                $executionTime,
                [
                    'error' => $e->getMessage(),
                    'user_id' => Auth::id(),
                ]
            );

            Log::error('Media upload failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'file_name' => $request->file('file')?->getClientOriginalName(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload media',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Serve media file with optimization
     * GET /api/media/{id}
     */
    public function show(Request $request, int $id): BinaryFileResponse|JsonResponse
    {
        $startTime = microtime(true);

        try {
            // Find media
            $media = Media::findOrFail($id);

            // Check if media is processed
            if (!$media->isProcessed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media is not yet processed',
                    'data' => [
                        'id' => $media->id,
                        'status' => $media->status,
                        'processing_status' => $media->processing_status,
                    ],
                ], 202);
            }

            // Get conversion if specified
            $conversionName = $request->query('conversion');
            if ($conversionName) {
                $media = $this->mediaService->getMediaWithConversion($id, $conversionName);

                if (!$media || !$media->getConversion($conversionName)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Conversion not found',
                    ], 404);
                }
            }

            // Get optimized URL
            $url = $this->mediaService->getOptimizedUrl($media, $conversionName);

            // Record performance metrics
            $executionTime = microtime(true) - $startTime;
            $this->performanceMonitor->monitorSearchPerformance(
                'media_serve',
                $executionTime,
                [
                    'media_id' => $media->id,
                    'conversion' => $conversionName,
                    'file_size' => $media->size,
                ]
            );

            // Return file response with proper headers
            $response = response()->file($media->getFullPath(), [
                'Content-Type' => $media->mime_type,
                'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
                'Cache-Control' => 'public, max-age=' . (config('cache.media_ttl', 3600)),
                'X-Media-ID' => $media->id,
                'X-Media-Conversion' => $conversionName ?? 'original',
            ]);

            return $response;

        } catch (\Exception $e) {
            $executionTime = microtime(true) - $startTime;

            // Record failed performance metrics
            $this->performanceMonitor->monitorSearchPerformance(
                'media_serve_failed',
                $executionTime,
                [
                    'media_id' => $id,
                    'error' => $e->getMessage(),
                ]
            );

            Log::error('Media serve failed', [
                'media_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to serve media',
            ], 500);
        }
    }

    /**
     * Delete media and all conversions
     * DELETE /api/admin/media/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $startTime = microtime(true);

        try {
            // Get authenticated user
            $userId = Auth::id();
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            // Find media
            $media = Media::where('id', $id)
                         ->where('uploaded_by', $userId)
                         ->first();

            if (!$media) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media not found or access denied',
                ], 404);
            }

            // Delete media
            $deleted = $this->mediaService->deleteMedia($media);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete media',
                ], 500);
            }

            // Record performance metrics
            $executionTime = microtime(true) - $startTime;
            $this->performanceMonitor->monitorSearchPerformance(
                'media_delete',
                $executionTime,
                [
                    'media_id' => $media->id,
                    'user_id' => $userId,
                ]
            );

            Log::info('Media deleted successfully', [
                'media_id' => $media->id,
                'user_id' => $userId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully',
            ]);

        } catch (\Exception $e) {
            $executionTime = microtime(true) - $startTime;

            // Record failed performance metrics
            $this->performanceMonitor->monitorSearchPerformance(
                'media_delete_failed',
                $executionTime,
                [
                    'media_id' => $id,
                    'error' => $e->getMessage(),
                    'user_id' => Auth::id(),
                ]
            );

            Log::error('Media delete failed', [
                'media_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get media information
     * GET /api/media/{id}/info
     */
    public function info(int $id): JsonResponse
    {
        try {
            $media = Media::with('conversions')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $media->id,
                    'name' => $media->name,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'human_size' => $media->human_readable_size,
                    'width' => $media->width,
                    'height' => $media->height,
                    'alt' => $media->alt,
                    'title' => $media->title,
                    'description' => $media->description,
                    'caption' => $media->caption,
                    'status' => $media->status,
                    'processing_status' => $media->processing_status,
                    'is_optimized' => $media->is_optimized,
                    'optimization_ratio' => $media->optimization_ratio,
                    'url' => $media->getUrl(),
                    'cloud_url' => $media->cloud_url,
                    'cdn_url' => $media->cdn_url,
                    'conversions' => $media->conversions->map(function ($conversion) {
                        return [
                            'id' => $conversion->id,
                            'name' => $conversion->conversion_name,
                            'type' => $conversion->conversion_type,
                            'file_name' => $conversion->file_name,
                            'mime_type' => $conversion->mime_type,
                            'size' => $conversion->size,
                            'human_size' => $conversion->human_readable_size,
                            'width' => $conversion->width,
                            'height' => $conversion->height,
                            'dimensions' => $conversion->dimensions,
                            'compression_ratio' => $conversion->compression_ratio,
                            'compression_savings' => $conversion->compression_savings,
                            'quality_score' => $conversion->quality_score,
                            'status' => $conversion->status,
                            'url' => $conversion->getUrl(),
                            'cloud_url' => $conversion->cloud_url,
                            'cdn_url' => $conversion->cdn_url,
                            'generated_at' => $conversion->generated_at,
                        ];
                    }),
                    'created_at' => $media->created_at,
                    'updated_at' => $media->updated_at,
                    'uploaded_by' => $media->uploaded_by,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Media info retrieval failed', [
                'media_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve media information',
            ], 500);
        }
    }

    /**
     * List media files with filtering and pagination
     * GET /api/admin/media
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Media::with('conversions');

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('processing_status')) {
                $query->where('processing_status', $request->processing_status);
            }

            if ($request->has('mime_type')) {
                $query->where('mime_type', 'like', $request->mime_type . '%');
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('file_name', 'like', "%{$search}%")
                      ->orWhere('alt', 'like', "%{$search}%")
                      ->orWhere('title', 'like', "%{$search}%");
                });
            }

            // Apply user filter if not admin (users can only see their own media)
            $user = Auth::user();
            if ($user && !$user->hasRole('admin')) {
                $query->where('uploaded_by', $user->id);
            }

            // Pagination
            $perPage = min($request->get('per_page', 20), 100);
            $media = $query->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_direction', 'desc'))
                          ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $media->items(),
                'pagination' => [
                    'current_page' => $media->currentPage(),
                    'per_page' => $media->perPage(),
                    'total' => $media->total(),
                    'last_page' => $media->lastPage(),
                    'from' => $media->firstItem(),
                    'to' => $media->lastItem(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Media list retrieval failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve media list',
            ], 500);
        }
    }
}
