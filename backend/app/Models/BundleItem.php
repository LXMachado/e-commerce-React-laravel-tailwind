<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BundleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bundle_id',
        'product_variant_id',
        'quantity',
        'sort_order',
    ];

    /**
     * Get the bundle that owns this item
     */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    /**
     * Get the product variant for this bundle item
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the line total for this bundle item
     */
    public function getLineTotal(): float
    {
        return $this->productVariant->getCurrentPrice() * $this->quantity;
    }
}