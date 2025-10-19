<?php

use App\Models\Bundle;
use App\Models\BundleConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Bundle API', function () {

    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->bundle = Bundle::factory()->create([
            'name' => 'Weekender Solar Kit',
            'slug' => 'weekender-solar-kit',
            'price' => 299.99,
            'base_weight_g' => 2500,
            'kit_type' => 'weekender',
        ]);
    });

    describe('Bundle Retrieval', function () {
        it('can retrieve bundle details', function () {
            $response = $this->getJson("/api/bundles/{$this->bundle->slug}");

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'data' => [
                            'id' => $this->bundle->id,
                            'name' => 'Weekender Solar Kit',
                            'slug' => 'weekender-solar-kit',
                            'price' => 299.99,
                        ]
                    ]);
        });

        it('returns 404 for non-existent bundle', function () {
            $response = $this->getJson('/api/bundles/non-existent-bundle');

            $response->assertStatus(404);
        });
    });

    describe('Bundle Configuration', function () {
        it('can create a basic bundle configuration', function () {
            $configurationData = [
                'configuration' => [
                    'espresso_module' => false,
                    'filter_attachment' => false,
                    'fan_accessory' => false,
                    'solar_panel_size' => '10W'
                ],
                'name' => 'Basic Weekender Kit'
            ];

            $response = $this->postJson("/api/bundles/{$this->bundle->slug}/configure", $configurationData);

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'message' => 'Bundle configuration created successfully'
                    ])
                    ->assertJsonStructure([
                        'success',
                        'data' => [
                            'id',
                            'bundle_id',
                            'name',
                            'configuration_data',
                            'total_price',
                            'total_weight_g',
                            'sku',
                            'share_token',
                            'weight_compatibility'
                        ],
                        'message'
                    ]);

            $this->assertDatabaseHas('bundle_configurations', [
                'bundle_id' => $this->bundle->id,
                'name' => 'Basic Weekender Kit',
                'total_price' => 324.99, // 299.99 base + 25.00 for 10W panel
            ]);
        });

        it('can create a full-featured bundle configuration', function () {
            $configurationData = [
                'configuration' => [
                    'espresso_module' => true,
                    'filter_attachment' => true,
                    'fan_accessory' => true,
                    'solar_panel_size' => '20W'
                ],
                'name' => 'Full Adventure Kit'
            ];

            $response = $this->postJson("/api/bundles/{$this->bundle->slug}/configure", $configurationData);

            $response->assertStatus(200);

            $responseData = $response->json('data');
            expect($responseData['total_price'])->toBe(819.99); // 299.99 + 150 + 75 + 45 + 100
            expect($responseData['total_weight_g'])->toBe(4600); // 2500 + 800 + 300 + 200 + 600
        });

        it('calculates weight compatibility correctly', function () {
            // Test day-pack compatible configuration (<5kg)
            $lightConfig = [
                'configuration' => [
                    'espresso_module' => false,
                    'filter_attachment' => false,
                    'fan_accessory' => false,
                    'solar_panel_size' => '10W'
                ]
            ];

            $response = $this->postJson("/api/bundles/{$this->bundle->slug}/configure", $lightConfig);
            $responseData = $response->json('data');

            expect($responseData['weight_compatibility']['threshold'])->toBe('<5kg');
            expect($responseData['weight_compatibility']['description'])->toBe('Day-pack compatible');
        });

        it('validates required solar panel size', function () {
            $invalidConfig = [
                'configuration' => [
                    'espresso_module' => false,
                    'filter_attachment' => false,
                    'fan_accessory' => false,
                    // Missing solar_panel_size
                ]
            ];

            $response = $this->postJson("/api/bundles/{$this->bundle->slug}/configure", $invalidConfig);

            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['configuration.solar_panel_size']);
        });

        it('validates solar panel size options', function () {
            $invalidConfig = [
                'configuration' => [
                    'espresso_module' => false,
                    'filter_attachment' => false,
                    'fan_accessory' => false,
                    'solar_panel_size' => '50W' // Invalid size
                ]
            ];

            $response = $this->postJson("/api/bundles/{$this->bundle->slug}/configure", $invalidConfig);

            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['configuration.solar_panel_size']);
        });
    });

    describe('Bundle Configuration Management', function () {
        beforeEach(function () {
            $this->configuration = $this->bundle->createConfiguration([
                'espresso_module' => true,
                'solar_panel_size' => '15W'
            ], $this->user->id, 'My Custom Kit');
        });

        it('can retrieve user configurations', function () {
            Sanctum::actingAs($this->user);

            $response = $this->getJson('/api/bundles/configurations');

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                    ]);
        });

        it('can retrieve specific configuration', function () {
            $response = $this->getJson("/api/bundles/configurations/{$this->configuration->id}");

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'data' => [
                            'id' => $this->configuration->id,
                            'name' => 'My Custom Kit',
                        ]
                    ]);
        });

        it('can add configuration to cart', function () {
            $response = $this->postJson("/api/bundles/configurations/{$this->configuration->id}/add-to-cart", [
                'quantity' => 1
            ]);

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'message' => 'Configuration added to cart successfully'
                    ]);
        });

        it('prevents unauthorized access to other user configurations', function () {
            $otherUser = User::factory()->create();
            $otherConfig = $this->bundle->createConfiguration([
                'solar_panel_size' => '10W'
            ], $otherUser->id);

            $response = $this->getJson("/api/bundles/configurations/{$otherConfig->id}");

            $response->assertStatus(404);
        });
    });

    describe('Bundle Sharing', function () {
        beforeEach(function () {
            $this->configuration = $this->bundle->createConfiguration([
                'espresso_module' => true,
                'fan_accessory' => true,
                'solar_panel_size' => '20W'
            ], $this->user->id, 'Shared Adventure Kit');
        });

        it('can access shared configuration by token', function () {
            $shareToken = $this->configuration->share_token;

            $response = $this->getJson("/api/bundles/shared/{$shareToken}");

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'data' => [
                            'id' => $this->configuration->id,
                            'name' => 'Shared Adventure Kit',
                        ]
                    ]);
        });

        it('returns 404 for invalid share token', function () {
            $response = $this->getJson('/api/bundles/shared/invalid-token');

            $response->assertStatus(404);
        });
    });

    describe('Bundle Business Logic', function () {
        it('generates unique SKUs for configurations', function () {
            $config1 = $this->bundle->createConfiguration(['solar_panel_size' => '10W']);
            $config2 = $this->bundle->createConfiguration(['solar_panel_size' => '15W']);

            expect($config1->sku)->not->toBe($config2->sku);
            expect($config1->sku)->toMatch('/^WEEKENDER-[A-Z0-9]{8}$/');
        });

        it('generates unique share tokens', function () {
            $config1 = $this->bundle->createConfiguration(['solar_panel_size' => '10W']);
            $config2 = $this->bundle->createConfiguration(['solar_panel_size' => '15W']);

            expect($config1->share_token)->not->toBe($config2->share_token);
            expect(strlen($config1->share_token))->toBe(32);
        });

        it('calculates correct total weight', function () {
            $config = $this->bundle->createConfiguration([
                'espresso_module' => true,
                'filter_attachment' => true,
                'solar_panel_size' => '20W'
            ]);

            // Base: 2500g + Espresso: 800g + Filter: 300g + 20W Panel: 600g = 4200g
            expect($config->total_weight_g)->toBe(4200);
        });

        it('calculates correct total price', function () {
            $config = $this->bundle->createConfiguration([
                'espresso_module' => true,
                'fan_accessory' => true,
                'solar_panel_size' => '15W'
            ]);

            // Base: 299.99 + Espresso: 150 + Fan: 45 + 15W Panel: 50 = 544.99
            expect($config->total_price)->toBe(544.99);
        });

        it('determines weight compatibility correctly', function () {
            // Light configuration (<5kg)
            $lightConfig = $this->bundle->createConfiguration([
                'solar_panel_size' => '10W'
            ]);
            expect($lightConfig->isDayPackCompatible())->toBe(true);
            expect($lightConfig->getWeightCompatibilityDescription())->toBe('Day-pack compatible');

            // Heavy configuration (>10kg)
            $heavyConfig = $this->bundle->createConfiguration([
                'espresso_module' => true,
                'filter_attachment' => true,
                'fan_accessory' => true,
                'solar_panel_size' => '20W'
            ]);
            expect($heavyConfig->isBaseCampSetup())->toBe(true);
            expect($heavyConfig->getWeightCompatibilityDescription())->toBe('Base camp setup');
        });
    });

    describe('Bundle Integration', function () {
        it('can add bundle configuration to cart', function () {
            $configuration = $this->bundle->createConfiguration([
                'espresso_module' => true,
                'solar_panel_size' => '15W'
            ]);

            $response = $this->postJson("/api/bundles/configurations/{$configuration->id}/add-to-cart", [
                'quantity' => 2
            ]);

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'message' => 'Configuration added to cart successfully'
                    ]);
        });

        it('validates configuration exists when adding to cart', function () {
            $response = $this->postJson('/api/bundles/configurations/99999/add-to-cart', [
                'quantity' => 1
            ]);

            $response->assertStatus(404);
        });

        it('validates quantity when adding to cart', function () {
            $configuration = $this->bundle->createConfiguration(['solar_panel_size' => '10W']);

            $response = $this->postJson("/api/bundles/configurations/{$configuration->id}/add-to-cart", [
                'quantity' => 0
            ]);

            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['quantity']);
        });
    });

    describe('Bundle Error Handling', function () {
        it('handles invalid bundle slug gracefully', function () {
            $response = $this->getJson('/api/bundles/invalid-slug');

            $response->assertStatus(404)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Bundle not found'
                    ]);
        });

        it('validates configuration data structure', function () {
            $invalidConfig = [
                'configuration' => 'invalid', // Should be array
                'name' => 'Invalid Config'
            ];

            $response = $this->postJson("/api/bundles/{$this->bundle->slug}/configure", $invalidConfig);

            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['configuration']);
        });

        it('handles database errors gracefully', function () {
            // Test with invalid data that might cause database errors
            $invalidConfig = [
                'configuration' => [
                    'solar_panel_size' => '10W'
                ],
                'name' => str_repeat('A', 256) // Too long for database field
            ];

            $response = $this->postJson("/api/bundles/{$this->bundle->slug}/configure", $invalidConfig);

            $response->assertStatus(422);
        });
    });
});