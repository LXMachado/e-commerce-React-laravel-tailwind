<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ShippingZone;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;

class ShippingRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Weight tiers with pricing
        $weightTiers = [
            ['min' => 0, 'max' => 1, 'price' => 10, 'label' => 'Light parcel'],
            ['min' => 1, 'max' => 5, 'price' => 15, 'label' => 'Standard parcel'],
            ['min' => 5, 'max' => 10, 'price' => 25, 'label' => 'Heavy parcel'],
            ['min' => 10, 'max' => 20, 'price' => 40, 'label' => 'Bulk parcel'],
            ['min' => 20, 'max' => null, 'price' => null, 'label' => 'Contact for quote'],
        ];

        // Get all zones and methods
        $zones = ShippingZone::all();
        $methods = ShippingMethod::all();

        $createdRates = 0;

        foreach ($zones as $zone) {
            foreach ($methods as $method) {
                foreach ($weightTiers as $tier) {
                    // Skip tiers that require contact for quote (price is null)
                    if ($tier['price'] === null) {
                        continue;
                    }

                    ShippingRate::firstOrCreate(
                        [
                            'shipping_zone_id' => $zone->id,
                            'shipping_method_id' => $method->id,
                            'min_weight' => $tier['min'],
                            'max_weight' => $tier['max'],
                        ],
                        [
                            'price' => $tier['price'] * 100, // Convert to cents
                            'currency' => 'AUD',
                            'is_active' => true,
                        ]
                    );

                    $createdRates++;
                }
            }
        }

        $this->command->info("Created {$createdRates} shipping rates for {$zones->count()} zones and {$methods->count()} methods");
    }
}
