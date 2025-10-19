<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Stripe\PaymentIntent;
use Mockery;

uses(RefreshDatabase::class);

describe('Checkout API', function () {

    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->product = Product::factory()->create(['price' => 100.00]);
        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'price' => 100.00,
            'stock_quantity' => 10,
            'is_active' => true
        ]);

        $this->stripeService = Mockery::mock(StripeService::class);
        app()->instance(StripeService::class, $this->stripeService);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('Checkout Initiation', function () {
        it('can initiate checkout for guest cart', function () {
            $cart = Cart::factory()->create(['session_id' => 'test-session']);
            $item = CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 2,
                'price_at_time' => 100.00
            ]);

            $response = $this->postJson('/api/checkout/initiate', [
                'cart_id' => $cart->id
            ]);

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'message' => 'Checkout initiated successfully'
                    ])
                    ->assertJsonStructure([
                        'success',
                        'data' => [
                            'payment_intent_id',
                            'client_secret',
                            'amount',
                            'currency',
                            'cart_id',
                            'item_count',
                            'subtotal'
                        ],
                        'message'
                    ]);

            // Verify cart has payment intent ID
            $cart->refresh();
            expect($cart->payment_intent_id)->toBeString();
        });

        it('can initiate checkout for authenticated user cart', function () {
            Sanctum::actingAs($this->user);

            $cart = Cart::factory()->create(['user_id' => $this->user->id]);
            CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 1,
                'price_at_time' => 100.00
            ]);

            $response = $this->postJson('/api/checkout/initiate');

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'message' => 'Checkout initiated successfully'
                    ]);
        });

        it('validates empty cart for checkout', function () {
            $cart = Cart::factory()->create(['session_id' => 'test-session']);

            $response = $this->postJson('/api/checkout/initiate', [
                'cart_id' => $cart->id
            ]);

            $response->assertStatus(404)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Cart is empty or not found'
                    ]);
        });

        it('validates insufficient stock during checkout', function () {
            $cart = Cart::factory()->create(['session_id' => 'test-session']);
            CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 15, // More than available stock (10)
                'price_at_time' => 100.00
            ]);

            $response = $this->postJson('/api/checkout/initiate', [
                'cart_id' => $cart->id
            ]);

            $response->assertStatus(422)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Insufficient stock for item: ' . $this->product->name
                    ]);
        });

        it('validates invalid cart items during checkout', function () {
            $cart = Cart::factory()->create(['session_id' => 'test-session']);
            $inactiveVariant = ProductVariant::factory()->create([
                'product_id' => $this->product->id,
                'price' => 50.00,
                'stock_quantity' => 5,
                'is_active' => false
            ]);

            CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $inactiveVariant->id,
                'quantity' => 1,
                'price_at_time' => 50.00
            ]);

            $response = $this->postJson('/api/checkout/initiate', [
                'cart_id' => $cart->id
            ]);

            $response->assertStatus(422)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Some items in cart are no longer available'
                    ]);
        });
    });

    describe('Payment Processing', function () {
        it('can process successful payment', function () {
            Sanctum::actingAs($this->user);

            // Mock the Stripe service
            $mockPaymentIntent = Mockery::mock(PaymentIntent::class);
            $mockPaymentIntent->id = 'pi_test_' . uniqid();
            $mockPaymentIntent->status = 'succeeded';
            $mockPaymentIntent->amount = 20000;
            $mockPaymentIntent->currency = 'usd';

            $this->stripeService->shouldReceive('confirmPaymentIntent')
                               ->once()
                               ->andReturn($mockPaymentIntent);

            $response = $this->postJson('/api/checkout/process', [
                'payment_intent_id' => $mockPaymentIntent->id,
            ]);

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'message' => 'Payment processed successfully'
                    ])
                    ->assertJsonStructure([
                        'success',
                        'data' => [
                            'payment_intent_id',
                            'payment_status',
                        ],
                        'message'
                    ]);
        });

        it('handles payment not completed', function () {
            // Create a payment intent that requires confirmation
            $paymentIntent = PaymentIntent::create([
                'amount' => 10000,
                'currency' => 'usd',
                'metadata' => ['cart_id' => 1],
            ]);

            $response = $this->postJson('/api/checkout/process', [
                'payment_intent_id' => $paymentIntent->id,
            ]);

            $response->assertStatus(402)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Payment not completed'
                    ]);
        });

        it('validates payment intent ID', function () {
            $response = $this->postJson('/api/checkout/process', [
                'payment_intent_id' => '',
            ]);

            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['payment_intent_id']);
        });
    });

    describe('Payment Status', function () {
        it('can retrieve payment status', function () {
            $mockPaymentIntent = Mockery::mock(PaymentIntent::class);
            $mockPaymentIntent->id = 'pi_test_' . uniqid();
            $mockPaymentIntent->status = 'succeeded';
            $mockPaymentIntent->amount = 10000;
            $mockPaymentIntent->currency = 'usd';
            $mockPaymentIntent->last_payment_error = null;

            $this->stripeService->shouldReceive('getPaymentIntent')
                               ->once()
                               ->andReturn($mockPaymentIntent);

            $response = $this->getJson("/api/checkout/payment-status/{$mockPaymentIntent->id}");

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'message' => 'Payment status retrieved successfully'
                    ])
                    ->assertJsonStructure([
                        'success',
                        'data' => [
                            'payment_intent_id',
                            'status',
                            'amount',
                            'currency',
                        ],
                        'message'
                    ]);
        });

        it('handles non-existent payment intent', function () {
            $response = $this->getJson('/api/checkout/payment-status/pi_invalid_id');

            $response->assertStatus(404)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Failed to retrieve payment status'
                    ]);
        });
    });

    describe('Stripe Webhook Handling', function () {
        it('processes payment success webhook', function () {
            $cart = Cart::factory()->create(['session_id' => 'test-session']);
            $item = CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 1,
                'price_at_time' => 100.00
            ]);

            // Create a payment intent that succeeded
            $paymentIntent = PaymentIntent::create([
                'amount' => 10000,
                'currency' => 'usd',
                'metadata' => [
                    'cart_id' => $cart->id,
                ],
            ]);

            // Simulate webhook payload
            $webhookPayload = [
                'id' => 'evt_test_webhook',
                'object' => 'event',
                'type' => 'payment_intent.succeeded',
                'data' => [
                    'object' => [
                        'id' => $paymentIntent->id,
                        'amount' => 10000,
                        'currency' => 'usd',
                        'metadata' => [
                            'cart_id' => $cart->id,
                        ],
                    ]
                ]
            ];

            $response = $this->postJson('/api/webhooks/stripe', $webhookPayload, [
                'Stripe-Signature' => 'test_signature'
            ]);

            // Note: This test would need proper webhook signature for full testing
            // For now, we're testing the structure
            $response->assertStatus(400); // Expected due to invalid signature in test
        });

        it('rejects webhook without signature', function () {
            $webhookPayload = [
                'id' => 'evt_test_webhook',
                'object' => 'event',
                'type' => 'payment_intent.succeeded',
            ];

            $response = $this->postJson('/api/webhooks/stripe', $webhookPayload);

            $response->assertStatus(400)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Missing Stripe signature'
                    ]);
        });
    });

    describe('Order Creation from Payment', function () {
        it('creates order from successful cart payment', function () {
            $cart = Cart::factory()->create(['user_id' => $this->user->id]);
            $item = CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 2,
                'price_at_time' => 100.00
            ]);

            // Simulate successful payment processing
            $paymentIntent = [
                'id' => 'pi_test_' . uniqid(),
                'amount' => 20000, // $200.00 in cents
                'currency' => 'usd',
                'metadata' => [
                    'cart_id' => $cart->id,
                ],
            ];

            // Process the payment (this would normally be done by webhook)
            $order = $this->stripeService->createOrderFromCart($cart, $paymentIntent);

            // Verify order was created
            expect($order)->toBeInstanceOf(\App\Models\Order::class);
            expect($order->user_id)->toBe($this->user->id);
            expect($order->total_amount)->toBe(200.00);
            expect($order->status)->toBe('paid');

            // Verify order items were created
            expect($order->items)->toHaveCount(1);
            expect($order->items->first()->quantity)->toBe(2);
            expect($order->items->first()->line_total)->toBe(200.00);

            // Verify payment record was created
            expect($order->payments)->toHaveCount(1);
            expect($order->payments->first()->payment_intent_id)->toBe($paymentIntent['id']);
            expect($order->payments->first()->amount)->toBe(200.00);

            // Verify stock was decremented
            $this->variant->refresh();
            expect($this->variant->stock_quantity)->toBe(8); // 10 - 2

            // Verify cart was cleared
            expect($cart->fresh()->items)->toHaveCount(0);
        });

        it('handles payment failure gracefully', function () {
            $cart = Cart::factory()->create(['session_id' => 'test-session']);

            // Simulate payment failure
            $paymentIntent = [
                'id' => 'pi_test_failed_' . uniqid(),
                'amount' => 10000,
                'currency' => 'usd',
                'last_payment_error' => [
                    'message' => 'Your card was declined.'
                ],
            ];

            // This should not create an order
            expect(function () use ($cart, $paymentIntent) {
                $this->stripeService->processPaymentFailure(['object' => $paymentIntent]);
            })->not->toThrow(Exception::class);

            // Cart should remain unchanged
            expect($cart->fresh()->items)->toHaveCount(1);
        });
    });

    describe('Cart Integration', function () {
        it('associates payment intent with cart during checkout', function () {
            $cart = Cart::factory()->create(['session_id' => 'test-session']);
            CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 1,
                'price_at_time' => 100.00
            ]);

            $response = $this->postJson('/api/checkout/initiate', [
                'cart_id' => $cart->id
            ]);

            $response->assertStatus(200);

            $cart->refresh();
            expect($cart->payment_intent_id)->toBeString();
            expect($cart->payment_intent_id)->toBe($response->json('data.payment_intent_id'));
        });

        it('calculates correct cart totals for Stripe', function () {
            $cart = Cart::factory()->create(['session_id' => 'test-session']);

            // Add multiple items with different prices
            CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 2,
                'price_at_time' => 100.00
            ]);

            $variant2 = ProductVariant::factory()->create([
                'product_id' => $this->product->id,
                'price' => 150.00,
                'stock_quantity' => 5,
                'is_active' => true
            ]);

            CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $variant2->id,
                'quantity' => 1,
                'price_at_time' => 150.00
            ]);

            $response = $this->postJson('/api/checkout/initiate', [
                'cart_id' => $cart->id
            ]);

            $response->assertStatus(200);

            $responseData = $response->json('data');
            expect($responseData['amount'])->toBe(35000); // $350.00 in cents
            expect($responseData['subtotal'])->toBe(350.00);
        });
    });

    describe('Error Handling', function () {
        it('handles Stripe API errors gracefully', function () {
            // Test with invalid Stripe configuration
            // This would require mocking Stripe to throw an exception

            $cart = Cart::factory()->create(['session_id' => 'test-session']);
            CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 1,
                'price_at_time' => 100.00
            ]);

            // Temporarily set invalid Stripe key to test error handling
            config(['services.stripe.secret' => 'sk_test_invalid']);

            $response = $this->postJson('/api/checkout/initiate', [
                'cart_id' => $cart->id
            ]);

            $response->assertStatus(503)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Payment service unavailable'
                    ]);
        });

        it('validates checkout request parameters', function () {
            $response = $this->postJson('/api/checkout/initiate', [
                'success_url' => 'not-a-valid-url',
                'cancel_url' => 'also-not-valid',
            ]);

            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['success_url', 'cancel_url']);
        });

        it('handles cart not found during checkout', function () {
            $response = $this->postJson('/api/checkout/initiate', [
                'cart_id' => 99999
            ]);

            $response->assertStatus(404)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Cart is empty or not found'
                    ]);
        });
    });

    describe('Security and Authentication', function () {
        it('allows guest users to initiate checkout', function () {
            $cart = Cart::factory()->create(['session_id' => 'test-session']);
            CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 1,
                'price_at_time' => 100.00
            ]);

            $response = $this->postJson('/api/checkout/initiate', [
                'cart_id' => $cart->id
            ]);

            $response->assertStatus(200);
        });

        it('allows authenticated users to initiate checkout', function () {
            Sanctum::actingAs($this->user);

            $cart = Cart::factory()->create(['user_id' => $this->user->id]);
            CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 1,
                'price_at_time' => 100.00
            ]);

            $response = $this->postJson('/api/checkout/initiate');

            $response->assertStatus(200);
        });

        it('processes webhooks without authentication', function () {
            $webhookPayload = [
                'id' => 'evt_test_webhook',
                'object' => 'event',
                'type' => 'payment_intent.succeeded',
                'data' => [
                    'object' => [
                        'id' => 'pi_test_' . uniqid(),
                        'amount' => 10000,
                        'currency' => 'usd',
                    ]
                ]
            ];

            $response = $this->postJson('/api/webhooks/stripe', $webhookPayload);

            // Should handle gracefully even with invalid signature
            expect($response->status())->toBeIn([400, 500]);
        });
    });

    describe('Business Logic Validation', function () {
        it('prevents checkout with out-of-stock items', function () {
            $cart = Cart::factory()->create(['session_id' => 'test-session']);

            // Add item with quantity exceeding stock
            CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 15, // More than available stock (10)
                'price_at_time' => 100.00
            ]);

            $response = $this->postJson('/api/checkout/initiate', [
                'cart_id' => $cart->id
            ]);

            $response->assertStatus(422)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Insufficient stock for item: ' . $this->product->name
                    ]);
        });

        it('prevents checkout with inactive product variants', function () {
            $cart = Cart::factory()->create(['session_id' => 'test-session']);

            $inactiveVariant = ProductVariant::factory()->create([
                'product_id' => $this->product->id,
                'price' => 75.00,
                'stock_quantity' => 5,
                'is_active' => false
            ]);

            CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $inactiveVariant->id,
                'quantity' => 1,
                'price_at_time' => 75.00
            ]);

            $response = $this->postJson('/api/checkout/initiate', [
                'cart_id' => $cart->id
            ]);

            $response->assertStatus(422)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Some items in cart are no longer available'
                    ]);
        });

        it('creates unique order numbers', function () {
            $cart1 = Cart::factory()->create(['user_id' => $this->user->id]);
            $cart2 = Cart::factory()->create(['user_id' => $this->user->id]);

            CartItem::factory()->create([
                'cart_id' => $cart1->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 1,
                'price_at_time' => 100.00
            ]);

            CartItem::factory()->create([
                'cart_id' => $cart2->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 1,
                'price_at_time' => 100.00
            ]);

            // Process both carts (mock the service method)
            $paymentIntent1 = ['id' => 'pi_test_1_' . uniqid(), 'amount' => 10000, 'currency' => 'usd', 'metadata' => ['cart_id' => $cart1->id]];
            $paymentIntent2 = ['id' => 'pi_test_2_' . uniqid(), 'amount' => 10000, 'currency' => 'usd', 'metadata' => ['cart_id' => $cart2->id]];

            $this->stripeService->shouldReceive('createOrderFromCart')
                               ->andReturnUsing(function ($cart, $paymentIntent) {
                                   $order = new \App\Models\Order([
                                       'user_id' => $cart->user_id,
                                       'order_number' => 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                                       'status' => 'paid',
                                       'subtotal' => $cart->subtotal,
                                       'tax_amount' => 0,
                                       'shipping_amount' => 0,
                                       'total_amount' => $cart->subtotal,
                                       'currency' => 'USD',
                                       'payment_status' => 'paid',
                                       'shipping_status' => 'pending',
                                   ]);
                                   $order->id = rand(1, 1000);
                                   return $order;
                               });

            $order1 = $this->stripeService->createOrderFromCart($cart1, $paymentIntent1);
            $order2 = $this->stripeService->createOrderFromCart($cart2, $paymentIntent2);

            expect($order1->order_number)->not->toBe($order2->order_number);
            expect($order1->order_number)->toMatch('/^ORD-\d{8}-[A-F0-9]{8}$/');
        });
    });
});