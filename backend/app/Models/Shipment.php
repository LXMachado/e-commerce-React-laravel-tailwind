<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'tracking_number',
        'carrier',
        'status',
        'shipped_at',
        'delivered_at',
        'address_id',
    ];

    protected $casts = [
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * Get the order that owns this shipment
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the address for this shipment
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * Check if shipment is shipped
     */
    public function isShipped(): bool
    {
        return $this->status === 'shipped' || $this->status === 'in_transit';
    }

    /**
     * Check if shipment is delivered
     */
    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    /**
     * Check if shipment is in transit
     */
    public function isInTransit(): bool
    {
        return $this->status === 'in_transit';
    }

    /**
     * Mark shipment as shipped
     */
    public function markAsShipped(string $trackingNumber = null, string $carrier = null): void
    {
        $updates = [
            'status' => 'shipped',
            'shipped_at' => now(),
        ];

        if ($trackingNumber) {
            $updates['tracking_number'] = $trackingNumber;
        }

        if ($carrier) {
            $updates['carrier'] = $carrier;
        }

        $this->update($updates);
    }

    /**
     * Mark shipment as in transit
     */
    public function markAsInTransit(): void
    {
        $this->update(['status' => 'in_transit']);
    }

    /**
     * Mark shipment as delivered
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark shipment as failed
     */
    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }
}