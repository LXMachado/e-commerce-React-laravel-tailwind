<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    protected $fillable = [
        'name',
        'postcode_pattern',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the shipping rates for this zone.
     */
    public function shippingRates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    /**
     * Scope to only active zones.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if a postcode matches this zone's pattern.
     */
    public function matchesPostcode(string $postcode): bool
    {
        // Convert pattern like "2xxx" to regex pattern "^2\d{3}$"
        $pattern = str_replace('x', '\d', $this->postcode_pattern);
        $pattern = '/^' . $pattern . '$/';

        return preg_match($pattern, $postcode) === 1;
    }

    /**
     * Find the shipping zone for a given postcode.
     */
    public static function findByPostcode(string $postcode): ?self
    {
        return self::active()
            ->get()
            ->first(function ($zone) use ($postcode) {
                return $zone->matchesPostcode($postcode);
            });
    }
}
