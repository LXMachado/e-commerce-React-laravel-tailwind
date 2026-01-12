<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

class MediaConversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'media_id',
        'conversion_name',
        'conversion_type',
        'file_name',
        'mime_type',
        'path',
        'disk',
        'size',
        'width',
        'height',
        'conversion_options',
        'cloud_url',
        'cdn_url',
        'generated_at',
        'uploaded_at',
        'status',
        'compression_ratio',
        'quality_score',
    ];

    protected $casts = [
        'conversion_options' => 'array',
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'generated_at' => 'datetime',
        'uploaded_at' => 'datetime',
        'compression_ratio' => 'decimal:2',
        'quality_score' => 'decimal:2',
    ];

    protected $attributes = [
        'disk' => 'public',
        'status' => 'pending',
    ];

    /**
     * Get the media this conversion belongs to
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * Get the URL for this conversion file
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
     * Check if this conversion is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if this conversion is currently processing
     */
    public function isProcessing(): bool
    {
        return in_array($this->status, ['processing', 'pending']);
    }

    /**
     * Check if this conversion failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
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
     * Get compression savings percentage
     */
    public function getCompressionSavingsAttribute(): float
    {
        if (!$this->compression_ratio) {
            return 0;
        }

        return round((1 - $this->compression_ratio) * 100, 2);
    }

    /**
     * Mark conversion as processing
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    /**
     * Mark conversion as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'generated_at' => now(),
        ]);
    }

    /**
     * Mark conversion as failed
     */
    public function markAsFailed(string $reason = null): void
    {
        $this->update(['status' => 'failed']);
    }

    /**
     * Update conversion info after upload
     */
    public function updateUploadInfo(): void
    {
        $this->update(['uploaded_at' => now()]);
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
     * Scope for completed conversions
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for pending conversions
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for failed conversions
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for conversions by type
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('conversion_type', $type);
    }

    /**
     * Scope for conversions by name
     */
    public function scopeByName(Builder $query, string $name): Builder
    {
        return $query->where('conversion_name', $name);
    }

    /**
     * Scope for conversions for specific media
     */
    public function scopeForMedia(Builder $query, int $mediaId): Builder
    {
        return $query->where('media_id', $mediaId);
    }

    /**
     * Get conversion dimensions as string
     */
    public function getDimensionsAttribute(): string
    {
        if ($this->width && $this->height) {
            return "{$this->width}x{$this->height}";
        }

        return '';
    }

    /**
     * Check if this is a thumbnail conversion
     */
    public function isThumbnail(): bool
    {
        return str_contains($this->conversion_name, 'thumb');
    }

    /**
     * Check if this is a format conversion (WebP, AVIF, etc.)
     */
    public function isFormatConversion(): bool
    {
        return $this->conversion_type === 'format';
    }

    /**
     * Check if this is a resize conversion
     */
    public function isResizeConversion(): bool
    {
        return $this->conversion_type === 'resize';
    }
}
