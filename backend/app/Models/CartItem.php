<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_variant_id',
        'quantity',
        'price_at_time',
    ];

    protected $casts = [
        'price_at_time' => 'decimal:2',
    ];

    /**
     * Get the cart that owns this item
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the product variant for this cart item
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the product for this cart item
     */
    public function product()
    {
        return $this->hasOneThrough(Product::class, ProductVariant::class, 'id', 'id', 'product_variant_id', 'product_id');
    }

    /**
     * Get the line total for this cart item
     */
    public function getLineTotalAttribute(): float
    {
        return $this->quantity * $this->price_at_time;
    }

    /**
     * Update the quantity for this cart item
     */
    public function updateQuantity(int $quantity): bool
    {
        if ($quantity <= 0) {
            return $this->delete();
        }

        if (!$this->productVariant->hasStock($quantity)) {
            return false;
        }

        $this->quantity = $quantity;
        return $this->save();
    }

    /**
     * Check if this cart item is still valid (product variant exists and is active)
     */
    public function isValid(): bool
    {
        return $this->productVariant && $this->productVariant->is_active;
    }
}