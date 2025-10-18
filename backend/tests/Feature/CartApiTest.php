<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Cart API', function () {

    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->product = Product::factory()->create(['price' => 100.00]);
        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'price' => 100.00,
            'stock_quantity' => 10,
            'is_active' => true
        ]);
    });

    describe('Guest Cart Operations', function () {
        it('can create a cart for guest users', function () {
            $response = $this->postJson('/api/cart/items', [
                'product_variant_id' => $this->variant->id,
                'quantity' => 2
            ]);

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'message' => 'Item added to cart successfully'
                    ]);

            $this->assertDatabaseHas('carts', [
                'user_id' => null,
                'is_active' => true
            ]);

            $this->assertDatabaseHas('cart_items', [
                'product_variant_id' => $this->variant->id,
                'quantity' => 2,
                'price_at_time' => 100.00
            ]);
        });

        it('can retrieve guest cart', function () {
            // Create a guest cart first
            $cart = Cart::factory()->create(['session_id' => 'test-session']);
            $item = CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 1
            ]);

            // Simulate session
            session(['test_session_id' => 'test-session']);

            $response = $this->getJson('/api/cart');

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'data' => [
                            'id' => $cart->id,
                            'items' => [
                                [
                                    'id' => $item->id,
                                    'quantity' => 1,
                                    'product_variant_id' => $this->variant->id
                                ]
                            ]
                        ]
                    ]);
        });

        it('can add multiple items to guest cart', function () {
            $variant2 = ProductVariant::factory()->create([
                'product_id' => $this->product->id,
                'price' => 150.00,
                'stock_quantity' => 5,
                'is_active' => true
            ]);

            // Add first item
            $this->postJson('/api/cart/items', [
                'product_variant_id' => $this->variant->id,
                'quantity' => 2
            ])->assertStatus(200);

            // Add second item
            $response = $this->postJson('/api/cart/items', [
                'product_variant_id' => $variant2->id,
                'quantity' => 1
            ]);

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'message' => 'Item added to cart successfully'
                    ]);

            $this->assertDatabaseHas('cart_items', [
                'product_variant_id' => $this->variant->id,
                'quantity' => 2
            ]);

            $this->assertDatabaseHas('cart_items', [
                'product_variant_id' => $variant2->id,
                'quantity' => 1
            ]);
        });

        it('can update item quantity in guest cart', function () {
            $cart = Cart::factory()->create(['session_id' => 'test-session']);
            $item = CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 1
            ]);

            $response = $this->putJson("/api/cart/items/{$item->id}", [
                'quantity' => 3
            ]);

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'message' => 'Cart item updated successfully'
                    ]);

            $this->assertDatabaseHas('cart_items', [
                'id' => $item->id,
                'quantity' => 3
            ]);
        });

        it('can remove item from guest cart', function () {
            $cart = Cart::factory()->create(['session_id' => 'test-session']);
            $item = CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 1
            ]);

            $response = $this->deleteJson("/api/cart/items/{$item->id}");

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'message' => 'Item removed from cart successfully'
                    ]);

            $this->assertDatabaseMissing('cart_items', [
                'id' => $item->id
            ]);
        });

        it('can clear guest cart', function () {
            $cart = Cart::factory()->create(['session_id' => 'test-session']);
            CartItem::factory()->count(3)->create(['cart_id' => $cart->id]);

            $response = $this->deleteJson('/api/cart');

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'message' => 'Cart cleared successfully'
                    ]);

            expect($cart->fresh()->items)->toHaveCount(0);
        });

        it('validates stock availability when adding items', function () {
            $lowStockVariant = ProductVariant::factory()->create([
                'product_id' => $this->product->id,
                'price' => 50.00,
                'stock_quantity' => 2,
                'is_active' => true
            ]);

            $response = $this->postJson('/api/cart/items', [
                'product_variant_id' => $lowStockVariant->id,
                'quantity' => 5
            ]);

            $response->assertStatus(422)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Insufficient stock available'
                    ]);
        });

        it('prevents adding inactive variants to cart', function () {
            $inactiveVariant = ProductVariant::factory()->create([
                'product_id' => $this->product->id,
                'price' => 75.00,
                'stock_quantity' => 10,
                'is_active' => false
            ]);

            $response = $this->postJson('/api/cart/items', [
                'product_variant_id' => $inactiveVariant->id,
                'quantity' => 1
            ]);

            $response->assertStatus(404)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Product variant not found or inactive'
                    ]);
        });
    });

    describe('Authenticated User Cart Operations', function () {
        beforeEach(function () {
            Sanctum::actingAs($this->user);
        });

        it('can retrieve authenticated user cart', function () {
            $cart = Cart::factory()->create(['user_id' => $this->user->id]);
            $item = CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 2
            ]);

            $response = $this->getJson('/api/cart');

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'data' => [
                            'id' => $cart->id,
                            'user_id' => $this->user->id,
                            'items' => [
                                [
                                    'id' => $item->id,
                                    'quantity' => 2,
                                    'product_variant_id' => $this->variant->id
                                ]
                            ]
                        ]
                    ]);
        });

        it('can add items to authenticated user cart', function () {
            $response = $this->postJson('/api/cart/items', [
                'product_variant_id' => $this->variant->id,
                'quantity' => 3
            ]);

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'message' => 'Item added to cart successfully'
                    ]);

            $this->assertDatabaseHas('carts', [
                'user_id' => $this->user->id,
                'is_active' => true
            ]);

            $this->assertDatabaseHas('cart_items', [
                'product_variant_id' => $this->variant->id,
                'quantity' => 3
            ]);
        });

        it('can merge guest cart with user cart', function () {
            // Create guest cart
            $guestCart = Cart::factory()->create(['session_id' => 'guest-session']);
            $guestItem = CartItem::factory()->create([
                'cart_id' => $guestCart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 2
            ]);

            $response = $this->postJson('/api/cart/merge-guest', [
                'guest_cart_id' => $guestCart->id
            ]);

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'message' => 'Guest cart merged successfully'
                    ]);

            // Check that user now has the merged items
            $userCart = Cart::where('user_id', $this->user->id)->first();
            expect($userCart->items)->toHaveCount(1);
            expect($userCart->items->first()->quantity)->toBe(2);

            // Check that guest cart is deleted
            $this->assertDatabaseMissing('carts', ['id' => $guestCart->id]);
        });

        it('validates guest cart exists when merging', function () {
            $response = $this->postJson('/api/cart/merge-guest', [
                'guest_cart_id' => 99999
            ]);

            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['guest_cart_id']);
        });
    });

    describe('Cart Totals and Calculations', function () {
        it('can get cart totals for guest cart', function () {
            $cart = Cart::factory()->create(['session_id' => 'test-session']);
            CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 2,
                'price_at_time' => 100.00
            ]);

            $response = $this->getJson('/api/cart/totals');

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'data' => [
                            'item_count' => 2,
                            'subtotal' => 200.00,
                            'tax_amount' => 0,
                            'shipping_amount' => 0,
                            'total_amount' => 200.00,
                            'currency' => 'USD'
                        ]
                    ]);
        });

        it('returns zero totals for empty cart', function () {
            $response = $this->getJson('/api/cart/totals');

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'data' => [
                            'item_count' => 0,
                            'subtotal' => 0,
                            'tax_amount' => 0,
                            'shipping_amount' => 0,
                            'total_amount' => 0,
                            'currency' => 'USD'
                        ],
                        'message' => 'Cart is empty'
                    ]);
        });

        it('calculates correct totals with multiple items', function () {
            $variant2 = ProductVariant::factory()->create([
                'product_id' => $this->product->id,
                'price' => 150.00,
                'stock_quantity' => 5,
                'is_active' => true
            ]);

            $cart = Cart::factory()->create(['session_id' => 'test-session']);

            CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 2,
                'price_at_time' => 100.00
            ]);

            CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $variant2->id,
                'quantity' => 1,
                'price_at_time' => 150.00
            ]);

            $response = $this->getJson('/api/cart/totals');

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'data' => [
                            'item_count' => 3,
                            'subtotal' => 350.00, // (2 * 100) + (1 * 150)
                            'total_amount' => 350.00
                        ]
                    ]);
        });
    });

    describe('Cart Item Management', function () {
        it('can add item with existing item in cart (increases quantity)', function () {
            $cart = Cart::factory()->create(['session_id' => 'test-session']);
            $existingItem = CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 1,
                'price_at_time' => 100.00
            ]);

            $response = $this->postJson('/api/cart/items', [
                'product_variant_id' => $this->variant->id,
                'quantity' => 2
            ]);

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'message' => 'Item added to cart successfully'
                    ]);

            $existingItem->refresh();
            expect($existingItem->quantity)->toBe(3);
        });

        it('validates quantity when updating cart item', function () {
            $cart = Cart::factory()->create(['session_id' => 'test-session']);
            $item = CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 1
            ]);

            $response = $this->putJson("/api/cart/items/{$item->id}", [
                'quantity' => 0
            ]);

            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['quantity']);
        });

        it('prevents removing non-existent cart item', function () {
            $response = $this->deleteJson('/api/cart/items/99999');

            $response->assertStatus(404)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Cart item not found'
                    ]);
        });
    });

    describe('Cart Business Logic', function () {
        it('creates separate carts for different sessions', function () {
            // Create cart for session 1
            $this->postJson('/api/cart/items', [
                'product_variant_id' => $this->variant->id,
                'quantity' => 1
            ])->assertStatus(200);

            // Create cart for session 2
            $response = $this->postJson('/api/cart/items', [
                'product_variant_id' => $this->variant->id,
                'quantity' => 2
            ]);

            $response->assertStatus(200);

            // Should have 2 separate carts
            expect(Cart::count())->toBe(2);
        });

        it('reuses existing cart for same session', function () {
            // Create initial cart
            $this->postJson('/api/cart/items', [
                'product_variant_id' => $this->variant->id,
                'quantity' => 1
            ])->assertStatus(200);

            $initialCartCount = Cart::count();

            // Add another item to same session
            $this->postJson('/api/cart/items', [
                'product_variant_id' => $this->variant->id,
                'quantity' => 1
            ])->assertStatus(200);

            // Should still have same number of carts
            expect(Cart::count())->toBe($initialCartCount);
        });

        it('validates product variant exists when adding to cart', function () {
            $response = $this->postJson('/api/cart/items', [
                'product_variant_id' => 99999,
                'quantity' => 1
            ]);

            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['product_variant_id']);
        });

        it('validates quantity is positive when adding to cart', function () {
            $response = $this->postJson('/api/cart/items', [
                'product_variant_id' => $this->variant->id,
                'quantity' => 0
            ]);

            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['quantity']);
        });
    });

    describe('Cart API Error Handling', function () {
        it('returns proper error for invalid cart item update', function () {
            $response = $this->putJson('/api/cart/items/99999', [
                'quantity' => 5
            ]);

            $response->assertStatus(404)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Cart item not found'
                    ]);
        });

        it('handles database errors gracefully', function () {
            // This would test database constraint violations
            // For now, we'll test validation errors
            $response = $this->postJson('/api/cart/items', [
                'product_variant_id' => '',
                'quantity' => 'invalid'
            ]);

            $response->assertStatus(422);
        });
    });
});