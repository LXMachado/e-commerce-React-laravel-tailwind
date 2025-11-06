<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createSampleProduct(): array
{
    $product = Product::create([
        'name' => 'Aurora Solar Backpack',
        'slug' => Str::slug('Aurora Solar Backpack'),
        'description' => 'Solar-powered backpack built for weekend adventures.',
        'short_description' => 'Solar-first backpack',
        'sku' => 'AURORA-' . Str::random(6),
        'price' => 199.99,
        'is_active' => true,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'AURORA-V1-' . Str::random(4),
        'price' => 199.99,
        'stock_quantity' => 25,
        'is_active' => true,
    ]);

    return [$product, $variant];
}

it('returns catalog products with primary variants', function () {
    [$product] = createSampleProduct();

    $response = $this->getJson('/api/catalog/products');

    $response
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.data.0.id', $product->id);
});

it('returns product details', function () {
    [$product] = createSampleProduct();

    $response = $this->getJson("/api/catalog/products/{$product->id}");

    $response
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $product->id);
});

it('authenticates a user via the API', function () {
    $password = 'password123';
    $user = User::factory()->create([
        'password' => bcrypt($password),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => $password,
    ]);

    $response
        ->assertStatus(200)
        ->assertJsonStructure(['token', 'user' => ['id', 'email']]);
});

it('allows adding a variant to the cart', function () {
    [, $variant] = createSampleProduct();

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/cart/items', [
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['items']]);
});
