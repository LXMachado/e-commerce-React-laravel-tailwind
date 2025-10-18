<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bundle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'compare_at_price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the bundle items for this bundle
     */
    public function bundleItems(): HasMany
    {
        return $this->hasMany(BundleItem::class);
    }

    /**
     * Get the product variants in this bundle
     */
    public function productVariants()
    {
        return $this->belongsToMany(ProductVariant::class, 'bundle_items')
                    ->withPivot('quantity', 'sort_order');
    }

    /**
     * Get the current price (sale price if available, otherwise regular price)
     */
    public function getCurrentPrice(): float
    {
        return $this->compare_at_price ?? $this->price;
    }

    /**
     * Check if bundle is on sale
     */
    public function isOnSale(): bool
    {
        return $this->compare_at_price !== null && $this->compare_at_price > $this->price;
    }

    /**
     * Calculate the total value of all items in the bundle
     */
    public function getTotalValue(): float
    {
        return $this->bundleItems->sum(function ($item) {
            return $item->productVariant->getCurrentPrice() * $item->quantity;
        });
    }

    /**
     * Get the savings amount if on sale
     */
    public function getSavings(): float
    {
        if (!$this->isOnSale()) {
            return 0;
        }

        return $this->compare_at_price - $this->price;
    }

    /**
     * Get the savings percentage if on sale
     */
    public function getSavingsPercentage(): float
    {
        if (!$this->isOnSale()) {
            return 0;
        }

        return round(($this->getSavings() / $this->compare_at_price) * 100, 1);
    }
}