<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ShippingMethod;

class ShippingMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'name' => 'Standard',
                'code' => 'STD',
                'description' => 'Standard delivery service (3-5 business days)',
                'estimated_days' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Express',
                'code' => 'EXP',
                'description' => 'Express delivery service (1-2 business days)',
                'estimated_days' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Overnight',
                'code' => 'OVN',
                'description' => 'Overnight delivery service (next business day)',
                'estimated_days' => 1,
                'is_active' => true,
            ],
        ];

        foreach ($methods as $method) {
            ShippingMethod::firstOrCreate(
                ['code' => $method['code']],
                $method
            );
        }

        $this->command->info('Created ' . count($methods) . ' shipping methods');
    }
}
