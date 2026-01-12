<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'is_published',
        'seo_title',
        'seo_description',
        'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    /**
     * Get the URL for this content page
     */
    public function getUrlAttribute(): string
    {
        return route('content.show', $this->slug);
    }

    /**
     * Scope to get only published pages
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }
}