<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ShippingZone;

class ShippingZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zones = [
            [
                'name' => 'Sydney & New South Wales',
                'postcode_pattern' => '2xxx',
                'description' => 'Sydney metropolitan area and all NSW regional areas',
                'is_active' => true,
            ],
            [
                'name' => 'Melbourne & Victoria',
                'postcode_pattern' => '3xxx',
                'description' => 'Melbourne metropolitan area and all Victorian regional areas',
                'is_active' => true,
            ],
            [
                'name' => 'Brisbane & Queensland/SEQ',
                'postcode_pattern' => '4xxx',
                'description' => 'Brisbane metropolitan area and South East Queensland',
                'is_active' => true,
            ],
            [
                'name' => 'Adelaide & South Australia',
                'postcode_pattern' => '5xxx',
                'description' => 'Adelaide metropolitan area and all South Australian regional areas',
                'is_active' => true,
            ],
            [
                'name' => 'Perth & Western Australia',
                'postcode_pattern' => '6xxx',
                'description' => 'Perth metropolitan area and all Western Australian regional areas',
                'is_active' => true,
            ],
            [
                'name' => 'Northern Territory',
                'postcode_pattern' => '0xxx',
                'description' => 'Darwin and all Northern Territory areas',
                'is_active' => true,
            ],
            [
                'name' => 'Tasmania',
                'postcode_pattern' => '7xxx',
                'description' => 'Hobart and all Tasmanian areas',
                'is_active' => true,
            ],
        ];

        foreach ($zones as $zone) {
            ShippingZone::firstOrCreate(
                ['postcode_pattern' => $zone['postcode_pattern']],
                $zone
            );
        }

        $this->command->info('Created ' . count($zones) . ' Australian shipping zones');
    }
}
