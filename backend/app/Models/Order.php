<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'total_amount',
        'currency',
        'payment_status',
        'shipping_status',
        'notes',
        'billing_address_id',
        'shipping_address_id',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the user that owns this order
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order items for this order
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the payments for this order
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the shipments for this order
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * Get the billing address for this order
     */
    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    /**
     * Get the shipping address for this order
     */
    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    /**
     * Get the latest payment for this order
     */
    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /**
     * Get the latest shipment for this order
     */
    public function latestShipment()
    {
        return $this->hasOne(Shipment::class)->latestOfMany();
    }

    /**
     * Check if order is paid
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if order is shipped
     */
    public function isShipped(): bool
    {
        return $this->shipping_status === 'shipped';
    }

    /**
     * Check if order is delivered
     */
    public function isDelivered(): bool
    {
        return $this->shipping_status === 'delivered';
    }

    /**
     * Check if order is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Generate a unique order number
     */
    public static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (static::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Calculate totals from order items
     */
    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('line_total');
        $this->total_amount = $this->subtotal + $this->tax_amount + $this->shipping_amount;
        $this->save();
    }

    /**
     * Mark order as paid
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Mark order as shipped
     */
    public function markAsShipped(): void
    {
        $this->update([
            'status' => 'shipped',
            'shipping_status' => 'shipped',
        ]);
    }

    /**
     * Mark order as delivered
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'shipping_status' => 'delivered',
        ]);
    }

    /**
     * Cancel the order
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }
}