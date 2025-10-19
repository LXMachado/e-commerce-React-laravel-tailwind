<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Shipping API', function () {

    beforeEach(function () {
        $this->user = User::factory()->create();
    });

    describe('Shipping Zones', function () {
        it('can list shipping zones', function () {
            $response = $this->getJson('/api/shipping/zones');

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                    ])
                    ->assertJsonStructure([
                        'success',
                        'data' => [
                            '*' => [
                                'id',
                                'name',
                                'postcode_pattern',
                                'description',
                                'is_active'
                            ]
                        ],
                        'message'
                    ]);
        });
    });

    describe('Shipping Methods', function () {
        it('can list shipping methods', function () {
            $response = $this->getJson('/api/shipping/methods');

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                    ])
                    ->assertJsonStructure([
                        'success',
                        'data' => [
                            '*' => [
                                'id',
                                'name',
                                'description',
                                'estimated_days',
                                'is_active'
                            ]
                        ],
                        'message'
                    ]);
        });
    });

    describe('Shipping Quote Calculation', function () {
        it('can calculate shipping quote for valid address', function () {
            $quoteRequest = [
                'postcode' => '2000', // Sydney, NSW
                'weight_g' => 2500,   // 2.5kg
                'method_id' => 1      // Standard shipping
            ];

            $response = $this->postJson('/api/shipping/quote', $quoteRequest);

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                    ])
                    ->assertJsonStructure([
                        'success',
                        'data' => [
                            'zone' => [
                                'id',
                                'name',
                                'postcode_pattern'
                            ],
                            'method' => [
                                'id',
                                'name',
                                'estimated_days'
                            ],
                            'weight_g',
                            'weight_kg',
                            'shipping_cost',
                            'currency',
                            'estimated_delivery'
                        ],
                        'message'
                    ]);
        });

        it('validates required fields for quote', function () {
            $response = $this->postJson('/api/shipping/quote', []);

            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['postcode', 'weight_g']);
        });

        it('validates postcode format', function () {
            $quoteRequest = [
                'postcode' => 'invalid',
                'weight_g' => 2500,
                'method_id' => 1
            ];

            $response = $this->postJson('/api/shipping/quote', $quoteRequest);

            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['postcode']);
        });

        it('validates weight is positive', function () {
            $quoteRequest = [
                'postcode' => '2000',
                'weight_g' => 0,
                'method_id' => 1
            ];

            $response = $this->postJson('/api/shipping/quote', $quoteRequest);

            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['weight_g']);
        });

        it('calculates correct shipping cost for different zones', function () {
            // Test Sydney (2000) - should be $15 for 2.5kg standard
            $sydneyQuote = [
                'postcode' => '2000',
                'weight_g' => 2500,
                'method_id' => 1
            ];

            $response = $this->postJson('/api/shipping/quote', $sydneyQuote);
            $response->assertStatus(200);
            expect($response->json('data.shipping_cost'))->toBe(15.00);

            // Test Brisbane (4000) - should also be $15 for 2.5kg standard
            $brisbaneQuote = [
                'postcode' => '4000',
                'weight_g' => 2500,
                'method_id' => 1
            ];

            $response = $this->postJson('/api/shipping/quote', $brisbaneQuote);
            $response->assertStatus(200);
            expect($response->json('data.shipping_cost'))->toBe(15.00);
        });

        it('calculates correct shipping cost for different weight tiers', function () {
            // Test <1kg tier
            $lightQuote = [
                'postcode' => '2000',
                'weight_g' => 500,
                'method_id' => 1
            ];

            $response = $this->postJson('/api/shipping/quote', $lightQuote);
            $response->assertStatus(200);
            expect($response->json('data.shipping_cost'))->toBe(10.00);

            // Test 1-5kg tier
            $standardQuote = [
                'postcode' => '2000',
                'weight_g' => 2500,
                'method_id' => 1
            ];

            $response = $this->postJson('/api/shipping/quote', $standardQuote);
            $response->assertStatus(200);
            expect($response->json('data.shipping_cost'))->toBe(15.00);

            // Test 5-10kg tier
            $heavyQuote = [
                'postcode' => '2000',
                'weight_g' => 7500,
                'method_id' => 1
            ];

            $response = $this->postJson('/api/shipping/quote', $heavyQuote);
            $response->assertStatus(200);
            expect($response->json('data.shipping_cost'))->toBe(25.00);
        });
    });

    describe('Address Validation', function () {
        it('validates correct Australian postcode format', function () {
            $validPostcodes = ['2000', '3000', '4000', '5000', '6000', '7000'];

            foreach ($validPostcodes as $postcode) {
                $response = $this->postJson('/api/shipping/validate-address', [
                    'postcode' => $postcode
                ]);

                $response->assertStatus(200)
                        ->assertJson([
                            'success' => true,
                            'valid' => true
                        ]);
            }
        });

        it('rejects invalid postcode formats', function () {
            $invalidPostcodes = ['123', '12345', 'abcd', '12.3', ''];

            foreach ($invalidPostcodes as $postcode) {
                $response = $this->postJson('/api/shipping/validate-address', [
                    'postcode' => $postcode
                ]);

                $response->assertStatus(200)
                        ->assertJson([
                            'success' => true,
                            'valid' => false
                        ]);
            }
        });

        it('identifies correct shipping zones', function () {
            // Test NSW zone (2xxx)
            $response = $this->postJson('/api/shipping/validate-address', [
                'postcode' => '2000'
            ]);

            $response->assertStatus(200);
            expect($response->json('zone.name'))->toBe('Sydney & NSW');

            // Test QLD zone (4xxx)
            $response = $this->postJson('/api/shipping/validate-address', [
                'postcode' => '4000'
            ]);

            $response->assertStatus(200);
            expect($response->json('zone.name'))->toBe('Brisbane & Queensland');
        });
    });

    describe('Weight Tiers', function () {
        it('can list weight tiers', function () {
            $response = $this->getJson('/api/shipping/weight-tiers');

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                    ])
                    ->assertJsonStructure([
                        'success',
                        'data' => [
                            '*' => [
                                'id',
                                'name',
                                'min_weight_g',
                                'max_weight_g',
                                'base_price',
                                'description'
                            ]
                        ],
                        'message'
                    ]);
        });
    });

    describe('Error Handling', function () {
        it('handles non-existent shipping method', function () {
            $quoteRequest = [
                'postcode' => '2000',
                'weight_g' => 2500,
                'method_id' => 99999
            ];

            $response = $this->postJson('/api/shipping/quote', $quoteRequest);

            $response->assertStatus(422);
        });

        it('handles invalid zone for postcode', function () {
            $quoteRequest = [
                'postcode' => '9999', // Non-existent zone
                'weight_g' => 2500,
                'method_id' => 1
            ];

            $response = $this->postJson('/api/shipping/quote', $quoteRequest);

            $response->assertStatus(422)
                    ->assertJson([
                        'success' => false,
                        'message' => 'No shipping zone found for this postcode'
                    ]);
        });
    });
});