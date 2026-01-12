<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Catalog API', function () {

    beforeEach(function () {
        $this->user = User::factory()->create();
    });

    describe('Categories API', function () {
        it('can list categories', function () {
            Category::factory()->count(3)->create();

            $response = $this->getJson('/api/catalog/categories');

            $response->assertStatus(200)
                    ->assertJsonStructure([
                        'success',
                        'data' => [
                            '*' => [
                                'id',
                                'name',
                                'slug',
                                'description',
                                'parent_id',
                                'sort_order',
                                'is_active',
                                'created_at',
                                'updated_at'
                            ]
                        ],
                        'message'
                    ]);
        });

        it('can create a category when authenticated', function () {
            Sanctum::actingAs($this->user);

            $categoryData = [
                'name' => 'Solar Panels',
                'slug' => 'solar-panels',
                'description' => 'High-quality solar panels',
                'is_active' => true,
            ];

            $response = $this->postJson('/api/admin/catalog/categories', $categoryData);

            $response->assertStatus(201)
                    ->assertJsonStructure([
                        'success',
                        'data' => [
                            'id',
                            'name',
                            'slug',
                            'description',
                            'is_active',
                            'created_at',
                            'updated_at'
                        ],
                        'message'
                    ]);

            $this->assertDatabaseHas('categories', $categoryData);
        });

        it('can show a specific category', function () {
            $category = Category::factory()->create();

            $response = $this->getJson("/api/catalog/categories/{$category->id}");

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'data' => [
                            'id' => $category->id,
                            'name' => $category->name,
                            'slug' => $category->slug,
                        ]
                    ]);
        });

        it('prevents unauthenticated category creation', function () {
            $categoryData = [
                'name' => 'Solar Panels',
                'slug' => 'solar-panels',
            ];

            $response = $this->postJson('/api/admin/catalog/categories', $categoryData);

            $response->assertStatus(401);
        });
    });

    describe('Products API', function () {
        it('can list products', function () {
            Product::factory()->count(3)->create();

            $response = $this->getJson('/api/catalog/products');

            $response->assertStatus(200)
                    ->assertJsonStructure([
                        'success',
                        'data' => [
                            'data' => [
                                '*' => [
                                    'id',
                                    'name',
                                    'slug',
                                    'sku',
                                    'price',
                                    'is_active',
                                    'created_at',
                                    'updated_at'
                                ]
                            ]
                        ],
                        'message'
                    ]);
        });

        it('can filter products by category', function () {
            $category = Category::factory()->create();
            $product = Product::factory()->create();
            $product->categories()->attach($category);

            $response = $this->getJson("/api/catalog/categories/{$category->slug}/products");

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'category' => [
                            'id' => $category->id,
                            'name' => $category->name,
                        ]
                    ]);
        });

        it('can search products', function () {
            Product::factory()->create(['name' => 'Solar Panel 100W']);
            Product::factory()->create(['name' => 'Solar Battery 50Ah']);

            $response = $this->getJson('/api/catalog/products?search=Solar');

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                    ]);
        });

        it('can create a product when authenticated', function () {
            Sanctum::actingAs($this->user);

            $productData = [
                'name' => 'Solar Panel Pro',
                'slug' => 'solar-panel-pro',
                'sku' => 'SP-PRO-001',
                'price' => 299.99,
                'description' => 'Professional solar panel',
                'is_active' => true,
            ];

            $response = $this->postJson('/api/admin/catalog/products', $productData);

            $response->assertStatus(201)
                    ->assertJson([
                        'success' => true,
                        'message' => 'Product created successfully'
                    ]);

            $this->assertDatabaseHas('products', $productData);
        });
    });

    describe('Product Variants API', function () {
        it('can list product variants', function () {
            $product = Product::factory()->create();
            ProductVariant::factory()->count(3)->create(['product_id' => $product->id]);

            $response = $this->getJson('/api/catalog/variants');

            $response->assertStatus(200)
                    ->assertJsonStructure([
                        'success',
                        'data' => [
                            '*' => [
                                'id',
                                'product_id',
                                'sku',
                                'price',
                                'stock_quantity',
                                'is_active',
                                'created_at',
                                'updated_at'
                            ]
                        ],
                        'message'
                    ]);
        });

        it('can create a product variant when authenticated', function () {
            Sanctum::actingAs($this->user);

            $product = Product::factory()->create();

            $variantData = [
                'product_id' => $product->id,
                'sku' => 'SP-PRO-001-BLK',
                'price' => 299.99,
                'stock_quantity' => 50,
                'is_active' => true,
            ];

            $response = $this->postJson('/api/admin/catalog/variants', $variantData);

            $response->assertStatus(201)
                    ->assertJson([
                        'success' => true,
                        'message' => 'Product variant created successfully'
                    ]);

            $this->assertDatabaseHas('product_variants', $variantData);
        });

        it('can update variant stock', function () {
            Sanctum::actingAs($this->user);

            $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

            $response = $this->patchJson("/api/admin/catalog/variants/{$variant->id}/stock", [
                'stock_quantity' => 5,
                'operation' => 'add'
            ]);

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'message' => 'Stock updated successfully',
                        'new_stock' => 15,
                        'change' => 5
                    ]);

            $this->assertDatabaseHas('product_variants', [
                'id' => $variant->id,
                'stock_quantity' => 15
            ]);
        });
    });

    describe('API Error Handling', function () {
        it('returns 404 for non-existent category', function () {
            $response = $this->getJson('/api/catalog/categories/99999');

            $response->assertStatus(404);
        });

        it('returns 404 for non-existent product', function () {
            $response = $this->getJson('/api/catalog/products/99999');

            $response->assertStatus(404);
        });

        it('returns 404 for non-existent variant', function () {
            $response = $this->getJson('/api/catalog/variants/99999');

            $response->assertStatus(404);
        });

        it('validates required fields for category creation', function () {
            Sanctum::actingAs($this->user);

            $response = $this->postJson('/api/admin/catalog/categories', []);

            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['name', 'slug']);
        });

        it('validates required fields for product creation', function () {
            Sanctum::actingAs($this->user);

            $response = $this->postJson('/api/admin/catalog/products', []);

            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['name', 'slug', 'sku', 'price']);
        });

        it('validates required fields for variant creation', function () {
            Sanctum::actingAs($this->user);

            $response = $this->postJson('/api/admin/catalog/variants', []);

            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['product_id', 'sku', 'price', 'stock_quantity']);
        });
    });

    describe('API Relationships', function () {
        it('loads category relationships correctly', function () {
            $category = Category::factory()->create();
            $product = Product::factory()->create();
            $product->categories()->attach($category);

            $response = $this->getJson("/api/catalog/categories/{$category->id}");

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'data' => [
                            'id' => $category->id,
                        ]
                    ]);
        });

        it('loads product relationships correctly', function () {
            $product = Product::factory()->create();
            $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

            $response = $this->getJson("/api/catalog/products/{$product->id}");

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'data' => [
                            'id' => $product->id,
                        ]
                    ]);
        });
    });
});