<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'file_name',
        'mime_type',
        'path',
        'disk',
        'file_hash',
        'size',
        'metadata',
        'alt',
        'title',
        'description',
        'caption',
        'width',
        'height',
        'cloud_url',
        'cdn_url',
        'optimized_at',
        'status',
        'processing_status',
        'uploaded_by',
        'conversion_sizes',
        'is_optimized',
        'optimization_ratio',
    ];

    protected $casts = [
        'metadata' => 'array',
        'conversion_sizes' => 'array',
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'optimized_at' => 'datetime',
        'is_optimized' => 'boolean',
        'optimization_ratio' => 'decimal:2',
    ];

    protected $attributes = [
        'disk' => 'public',
        'status' => 'pending',
        'processing_status' => 'queued',
        'is_optimized' => false,
    ];

    /**
     * Get the user who uploaded this media
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get all conversions for this media
     */
    public function conversions(): HasMany
    {
        return $this->hasMany(MediaConversion::class);
    }

    /**
     * Get a specific conversion by name
     */
    public function getConversion(string $conversionName): ?MediaConversion
    {
        return $this->conversions()->where('conversion_name', $conversionName)->first();
    }

    /**
     * Get the URL for this media file
     */
    public function getUrl(): string
    {
        if ($this->cdn_url) {
            return $this->cdn_url;
        }

        if ($this->cloud_url) {
            return $this->cloud_url;
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Get the full path to the file
     */
    public function getFullPath(): string
    {
        return Storage::disk($this->disk)->path($this->path);
    }

    /**
     * Check if this is an image file
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if this media has been processed
     */
    public function isProcessed(): bool
    {
        return $this->status === 'completed' && $this->processing_status === 'completed';
    }

    /**
     * Check if this media is currently being processed
     */
    public function isProcessing(): bool
    {
        return in_array($this->processing_status, ['processing', 'queued']);
    }

    /**
     * Get human readable file size
     */
    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Scope for images only
     */
    public function scopeImages(Builder $query): Builder
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    /**
     * Scope for processed media
     */
    public function scopeProcessed(Builder $query): Builder
    {
        return $query->where('status', 'completed')
                    ->where('processing_status', 'completed');
    }

    /**
     * Scope for media by status
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for media by processing status
     */
    public function scopeByProcessingStatus(Builder $query, string $status): Builder
    {
        return $query->where('processing_status', $status);
    }

    /**
     * Scope for media uploaded by user
     */
    public function scopeUploadedBy(Builder $query, int $userId): Builder
    {
        return $query->where('uploaded_by', $userId);
    }

    /**
     * Mark media as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'processing_status' => 'processing',
        ]);
    }

    /**
     * Mark media as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'processing_status' => 'completed',
        ]);
    }

    /**
     * Mark media as failed
     */
    public function markAsFailed(string $reason = null): void
    {
        $metadata = $this->metadata ?? [];
        $metadata['failure_reason'] = $reason;

        $this->update([
            'status' => 'failed',
            'processing_status' => 'failed',
            'metadata' => $metadata,
        ]);
    }

    /**
     * Update optimization info
     */
    public function updateOptimizationInfo(bool $isOptimized, float $ratio = null): void
    {
        $this->update([
            'is_optimized' => $isOptimized,
            'optimization_ratio' => $ratio,
            'optimized_at' => now(),
        ]);
    }

    /**
     * Delete media and all its conversions
     */
    public function deleteWithConversions(): bool
    {
        // Delete all conversion files
        foreach ($this->conversions as $conversion) {
            $conversion->deleteFile();
        }

        // Delete the main file
        $this->deleteFile();

        // Delete database records
        return $this->delete();
    }

    /**
     * Delete the physical file
     */
    public function deleteFile(): bool
    {
        if (Storage::disk($this->disk)->exists($this->path)) {
            return Storage::disk($this->disk)->delete($this->path);
        }

        return true;
    }

    /**
     * Get available conversion sizes
     */
    public function getAvailableConversions(): array
    {
        return $this->conversion_sizes ?? [];
    }

    /**
     * Add a conversion size option
     */
    public function addConversionSize(string $name, array $dimensions): void
    {
        $conversions = $this->conversion_sizes ?? [];
        $conversions[$name] = $dimensions;
        $this->update(['conversion_sizes' => $conversions]);
    }
}
