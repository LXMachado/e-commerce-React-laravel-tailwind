<?php

namespace App\Services;

use App\Models\ShippingZone;
use App\Models\ShippingRate;
use App\Models\ShippingMethod;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Collection;

class ShippingService
{
    /**
     * Calculate shipping cost for a cart or order.
     */
    public function calculateShippingCost(
        string $postcode,
        float $totalWeight,
        ?string $methodCode = null
    ): array {
        // Find the shipping zone for the postcode
        $zone = ShippingZone::findByPostcode($postcode);

        if (!$zone) {
            return [
                'success' => false,
                'error' => 'No shipping zone found for postcode ' . $postcode,
            ];
        }

        // Get available shipping methods if not specified
        if ($methodCode) {
            $methods = collect([ShippingMethod::findByCode($methodCode)])->filter();
        } else {
            $methods = ShippingMethod::active()->get();
        }

        $quotes = [];

        foreach ($methods as $method) {
            $rate = ShippingRate::findForWeight($zone->id, $method->id, $totalWeight);

            if ($rate) {
                $quotes[] = [
                    'method' => [
                        'id' => $method->id,
                        'name' => $method->name,
                        'code' => $method->code,
                        'description' => $method->description,
                        'estimated_days' => $method->estimated_days,
                        'formatted_delivery' => $method->formatted_delivery,
                    ],
                    'rate' => [
                        'id' => $rate->id,
                        'price' => $rate->price_in_dollars,
                        'formatted_price' => $rate->formatted_price,
                        'currency' => $rate->currency,
                        'weight_range' => $this->formatWeightRange($rate),
                    ],
                    'zone' => [
                        'id' => $zone->id,
                        'name' => $zone->name,
                        'postcode_pattern' => $zone->postcode_pattern,
                    ],
                ];
            }
        }

        return [
            'success' => true,
            'quotes' => $quotes,
            'total_weight' => $totalWeight,
            'postcode' => $postcode,
            'zone' => $zone->toArray(),
        ];
    }

    /**
     * Calculate total weight from cart items.
     */
    public function calculateCartWeight(Cart $cart): float
    {
        $totalWeight = 0.0;

        foreach ($cart->items as $item) {
            $totalWeight += $this->calculateItemWeight($item);
        }

        return $totalWeight;
    }

    /**
     * Calculate weight for a single cart item.
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
     * Validate Australian address format.
     */
    public function validateAustralianAddress(array $address): array
    {
        $errors = [];

        // Required fields
        $requiredFields = ['address_line_1', 'suburb', 'state', 'postcode', 'country'];
        foreach ($requiredFields as $field) {
            if (empty($address[$field])) {
                $errors[] = "Field '{$field}' is required";
            }
        }

        if (!empty($address['country']) && strtoupper($address['country']) !== 'AU') {
            $errors[] = 'Country must be Australia (AU)';
        }

        // Validate postcode format (4 digits)
        if (!empty($address['postcode'])) {
            if (!preg_match('/^\d{4}$/', $address['postcode'])) {
                $errors[] = 'Australian postcode must be 4 digits';
            }
        }

        // Validate state (2-3 letter code)
        if (!empty($address['state'])) {
            $validStates = ['NSW', 'VIC', 'QLD', 'WA', 'SA', 'TAS', 'NT', 'ACT'];
            if (!in_array(strtoupper($address['state']), $validStates)) {
                $errors[] = 'Invalid Australian state code';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get all available shipping zones.
     */
    public function getShippingZones(): Collection
    {
        return ShippingZone::active()
            ->orderBy('name')
            ->get();
    }

    /**
     * Get all available shipping methods.
     */
    public function getShippingMethods(): Collection
    {
        return ShippingMethod::active()
            ->orderBy('name')
            ->get();
    }

    /**
     * Format weight range for display.
     */
    protected function formatWeightRange(ShippingRate $rate): string
    {
        $min = number_format($rate->min_weight, 1) . 'kg';

        if ($rate->max_weight) {
            $max = number_format($rate->max_weight, 1) . 'kg';
            return "{$min} - {$max}";
        }

        return "{$min}+";
    }

    /**
     * Get weight tiers for reference.
     */
    public function getWeightTiers(): array
    {
        return [
            ['min' => 0, 'max' => 1, 'label' => 'Light parcel (<1kg)', 'price' => 10],
            ['min' => 1, 'max' => 5, 'label' => 'Standard parcel (1-5kg)', 'price' => 15],
            ['min' => 5, 'max' => 10, 'label' => 'Heavy parcel (5-10kg)', 'price' => 25],
            ['min' => 10, 'max' => 20, 'label' => 'Bulk parcel (10-20kg)', 'price' => 40],
            ['min' => 20, 'max' => null, 'label' => 'Contact for quote (20kg+)', 'price' => null],
        ];
    }
}