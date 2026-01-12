<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Bundle;
use App\Models\BundleConfiguration;

class BundleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the main Weekender Kit bundle (check if it already exists)
        $weekenderKit = Bundle::where('slug', 'weekender-solar-kit')->first();

        if (!$weekenderKit) {
            $weekenderKit = Bundle::create([
            'name' => 'Weekender Solar Kit',
            'slug' => 'weekender-solar-kit',
            'description' => 'Complete solar power system for weekend adventures. Lightweight, portable, and powerful enough for all your off-grid needs.',
            'price' => 299.00,
            'compare_at_price' => 399.00,
            'is_active' => true,
            'kit_type' => 'weekender',
            'base_weight_g' => 2500, // 2.5kg base weight
            'sku_prefix' => 'WEEKENDER',
            'available_options' => [
                'espresso_module' => [
                    'name' => 'Espresso Module',
                    'description' => 'Hot water system for coffee and tea',
                    'price' => 150.00,
                    'weight_g' => 800,
                    'available' => true
                ],
                'filter_attachment' => [
                    'name' => 'Filter Attachment',
                    'description' => 'Water purification system',
                    'price' => 75.00,
                    'weight_g' => 300,
                    'available' => true
                ],
                'fan_accessory' => [
                    'name' => 'Fan Accessory',
                    'description' => 'Air circulation system',
                    'price' => 45.00,
                    'weight_g' => 200,
                    'available' => true
                ],
                'solar_panel_sizes' => [
                    '10W' => ['name' => '10W Solar Panel', 'price' => 25.00, 'weight_g' => 250],
                    '15W' => ['name' => '15W Solar Panel', 'price' => 50.00, 'weight_g' => 400],
                    '20W' => ['name' => '20W Solar Panel', 'price' => 100.00, 'weight_g' => 600]
                ]
            ],
            'default_configuration' => [
                'espresso_module' => false,
                'filter_attachment' => false,
                'fan_accessory' => false,
                'solar_panel_size' => '10W'
            ],
            'weight_threshold_compatibility' => [
                'day_pack' => ['max_weight_g' => 5000, 'description' => 'Day-pack compatible'],
                'overnight_pack' => ['max_weight_g' => 10000, 'description' => 'Overnight pack compatible'],
                'base_camp' => ['max_weight_g' => 999999, 'description' => 'Base camp setup']
            ]
            ]);
        }

        // Create some sample bundle items (these would typically reference actual product variants)
        // For now, we'll skip this since we don't have product variants in the seed data
        // $this->createSampleBundleItems($weekenderKit);

        // Create sample configurations
        $this->createSampleConfigurations($weekenderKit);
    }


    /**
     * Create sample configurations
     */
    private function createSampleConfigurations(Bundle $bundle): void
    {
        $configurations = [
            [
                'name' => 'Minimalist Setup',
                'configuration_data' => [
                    'espresso_module' => false,
                    'filter_attachment' => false,
                    'fan_accessory' => false,
                    'solar_panel_size' => '10W'
                ],
            ],
            [
                'name' => 'Coffee Lover\'s Kit',
                'configuration_data' => [
                    'espresso_module' => true,
                    'filter_attachment' => false,
                    'fan_accessory' => false,
                    'solar_panel_size' => '15W'
                ],
            ],
            [
                'name' => 'Full Adventure Kit',
                'configuration_data' => [
                    'espresso_module' => true,
                    'filter_attachment' => true,
                    'fan_accessory' => true,
                    'solar_panel_size' => '20W'
                ],
            ],
            [
                'name' => 'Water Purification Kit',
                'configuration_data' => [
                    'espresso_module' => false,
                    'filter_attachment' => true,
                    'fan_accessory' => false,
                    'solar_panel_size' => '15W'
                ],
            ],
            [
                'name' => 'Comfort Kit',
                'configuration_data' => [
                    'espresso_module' => true,
                    'filter_attachment' => false,
                    'fan_accessory' => true,
                    'solar_panel_size' => '15W'
                ],
            ],
        ];

        foreach ($configurations as $config) {
            $bundle->createConfiguration(
                $config['configuration_data'],
                null, // No specific user
                $config['name']
            );
        }

        // Create a shared configuration example
        $sharedConfig = $bundle->createConfiguration([
            'espresso_module' => true,
            'filter_attachment' => true,
            'fan_accessory' => false,
            'solar_panel_size' => '20W'
        ], null, 'Premium Weekender Setup');

        // Make this configuration shareable
        $sharedConfig->generateShareToken();
        $sharedConfig->save();
    }
}
