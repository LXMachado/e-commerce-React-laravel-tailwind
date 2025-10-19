<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\ShippingService;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'is_active',
        'payment_intent_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns this cart
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the cart items for this cart
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get the subtotal for this cart
     */
    public function getSubtotalAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return $item->quantity * $item->price_at_time;
        });
    }

    /**
     * Get the total item count for this cart
     */
    public function getItemCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Check if cart is empty
     */
    public function isEmpty(): bool
    {
        return $this->items()->count() === 0;
    }

    /**
     * Calculate total weight of cart items
     */
    public function getTotalWeightAttribute(): float
    {
        $totalWeight = 0.0;

        foreach ($this->items as $item) {
            $totalWeight += $this->calculateItemWeight($item);
        }

        return $totalWeight;
    }

    /**
     * Calculate weight for a single cart item
     */
    protected function calculateItemWeight(CartItem $item): float
    {
        $product = $item->product;
        $variant = $item->productVariant;

        // Use variant weight if available, otherwise product weight
        $weightPerUnit = $variant?->weight ?? $product->weight ?? 0;

        return $weightPerUnit * $item->quantity;
    }

    /**
     * Get shipping quote for this cart
     */
    public function getShippingQuote(string $postcode, ?string $methodCode = null): array
    {
        $shippingService = app(ShippingService::class);

        return $shippingService->calculateShippingCost(
            $postcode,
            $this->total_weight,
            $methodCode
        );
    }

    /**
     * Get the cheapest shipping option for this cart
     */
    public function getCheapestShipping(string $postcode): ?array
    {
        $quote = $this->getShippingQuote($postcode);

        if (!$quote['success'] || empty($quote['quotes'])) {
            return null;
        }

        return collect($quote['quotes'])->sortBy('rate.price')->first();
    }

    /**
     * Get the most expensive shipping option for this cart
     */
    public function getMostExpensiveShipping(string $postcode): ?array
    {
        $quote = $this->getShippingQuote($postcode);

        if (!$quote['success'] || empty($quote['quotes'])) {
            return null;
        }

        return collect($quote['quotes'])->sortByDesc('rate.price')->first();
    }

    /**
     * Find or create a cart for the current user/session
     */
    public static function findOrCreateForUser(?User $user = null, ?string $sessionId = null): Cart
    {
        if ($user) {
            $cart = static::where('user_id', $user->id)->where('is_active', true)->first();
            if ($cart) {
                return $cart;
            }

            return static::create(['user_id' => $user->id]);
        }

        if ($sessionId) {
            $cart = static::where('session_id', $sessionId)->where('is_active', true)->first();
            if ($cart) {
                return $cart;
            }

            return static::create(['session_id' => $sessionId]);
        }

        return static::create();
    }

    /**
     * Merge guest cart items into user cart
     */
    public function mergeGuestCart(Cart $guestCart): void
    {
        foreach ($guestCart->items as $guestItem) {
            $existingItem = $this->items()->where('product_variant_id', $guestItem->product_variant_id)->first();

            if ($existingItem) {
                $existingItem->quantity += $guestItem->quantity;
                $existingItem->save();
            } else {
                $guestItem->cart_id = $this->id;
                $guestItem->save();
            }
        }

        $guestCart->delete();
    }
}