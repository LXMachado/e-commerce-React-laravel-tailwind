<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingMethod extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'estimated_days',
        'is_active',
    ];

    protected $casts = [
        'estimated_days' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the shipping rates for this method.
     */
    public function shippingRates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    /**
     * Scope to only active methods.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get method by code.
     */
    public static function findByCode(string $code): ?self
    {
        return self::where('code', $code)->first();
    }

    /**
     * Get standard shipping method.
     */
    public static function standard(): ?self
    {
        return self::findByCode('STD');
    }

    /**
     * Get express shipping method.
     */
    public static function express(): ?self
    {
        return self::findByCode('EXP');
    }

    /**
     * Get overnight shipping method.
     */
    public static function overnight(): ?self
    {
        return self::findByCode('OVN');
    }

    /**
     * Get formatted estimated delivery.
     */
    public function getFormattedDeliveryAttribute(): string
    {
        if (!$this->estimated_days) {
            return 'Varies';
        }

        return $this->estimated_days === 1
            ? 'Next business day'
            : $this->estimated_days . ' business days';
    }
}
