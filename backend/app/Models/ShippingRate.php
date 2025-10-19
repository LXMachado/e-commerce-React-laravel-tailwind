<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    protected $fillable = [
        'shipping_zone_id',
        'shipping_method_id',
        'min_weight',
        'max_weight',
        'price',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'min_weight' => 'decimal:3',
        'max_weight' => 'decimal:3',
        'price' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the shipping zone for this rate.
     */
    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    /**
     * Get the shipping method for this rate.
     */
    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    /**
     * Scope to only active rates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if a weight falls within this rate's range.
     */
    public function coversWeight(float $weight): bool
    {
        if ($weight < $this->min_weight) {
            return false;
        }

        if ($this->max_weight !== null && $weight > $this->max_weight) {
            return false;
        }

        return true;
    }

    /**
     * Get price in dollars (formatted).
     */
    public function getPriceInDollarsAttribute(): float
    {
        return $this->price / 100;
    }

    /**
     * Get formatted price string.
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price_in_dollars, 2);
    }

    /**
     * Find applicable rate for given zone, method, and weight.
     */
    public static function findForWeight(int $zoneId, int $methodId, float $weight): ?self
    {
        return self::where('shipping_zone_id', $zoneId)
            ->where('shipping_method_id', $methodId)
            ->active()
            ->where('min_weight', '<=', $weight)
            ->when(true, function ($query) use ($weight) {
                $query->where(function ($q) use ($weight) {
                    $q->whereNull('max_weight')
                      ->orWhere('max_weight', '>=', $weight);
                });
            })
            ->orderBy('min_weight')
            ->first();
    }
}
