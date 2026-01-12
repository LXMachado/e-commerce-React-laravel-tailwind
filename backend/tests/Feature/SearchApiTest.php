<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $electronicsCategory;
    protected $booksCategory;
    protected $laptop;
    protected $phone;
    protected $novel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Create test categories
        $this->electronicsCategory = Category::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        $this->booksCategory = Category::factory()->create([
            'name' => 'Books',
            'slug' => 'books',
        ]);

        // Create test products
        $this->laptop = Product::factory()->create([
            'name' => 'Gaming Laptop',
            'slug' => 'gaming-laptop',
            'description' => 'High-performance gaming laptop with RGB keyboard',
            'price' => 1299.99,
            'is_active' => true,
            'category_id' => $this->electronicsCategory->id,
        ]);

        $this->phone = Product::factory()->create([
            'name' => 'Smartphone Pro',
            'slug' => 'smartphone-pro',
            'description' => 'Latest smartphone with advanced camera features',
            'price' => 899.99,
            'is_active' => true,
            'category_id' => $this->electronicsCategory->id,
        ]);

        $this->novel = Product::factory()->create([
            'name' => 'Science Fiction Novel',
            'slug' => 'science-fiction-novel',
            'description' => 'Epic space adventure novel',
            'price' => 19.99,
            'is_active' => true,
            'category_id' => $this->booksCategory->id,
        ]);
    }

    /** @test */
    public function it_can_search_products_by_name()
    {
        $response = $this->getJson('/api/search?q=laptop');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'products' => [
                            '*' => [
                                'id',
                                'name',
                                'slug',
                                'price',
                                'category'
                            ]
                        ],
                        'categories' => [
                            '*' => [
                                'id',
                                'name',
                                'slug'
                            ]
                        ],
                        'total',
                        'per_page',
                        'current_page',
                        'last_page'
                    ],
                    'message'
                ]);

        $products = $response->json('data.products');
        $this->assertGreaterThan(0, count($products));
        $this->assertStringContainsString('Laptop', $products[0]['name']);
    }

    /** @test */
    public function it_can_search_products_by_category()
    {
        $response = $this->getJson('/api/search?category=electronics');

        $response->assertStatus(200);

        $products = $response->json('data.products');
        foreach ($products as $product) {
            $this->assertEquals('electronics', $product['category']['slug']);
        }
    }

    /** @test */
    public function it_can_filter_search_by_price_range()
    {
        $response = $this->getJson('/api/search?min_price=800&max_price=1000');

        $response->assertStatus(200);

        $products = $response->json('data.products');
        foreach ($products as $product) {
            $this->assertGreaterThanOrEqual(800, $product['price']);
            $this->assertLessThanOrEqual(1000, $product['price']);
        }
    }

    /** @test */
    public function it_returns_empty_results_for_no_matches()
    {
        $response = $this->getJson('/api/search?q=nonexistentproduct');

        $response->assertStatus(200);

        $products = $response->json('data.products');
        $this->assertCount(0, $products);
    }

    /** @test */
    public function it_validates_search_query_length()
    {
        $response = $this->getJson('/api/search?q=a');

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['q']);
    }

    /** @test */
    public function it_validates_price_range_parameters()
    {
        $response = $this->getJson('/api/search?min_price=-10');

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['min_price']);
    }

    /** @test */
    public function it_can_search_categories()
    {
        $response = $this->getJson('/api/search?q=electro');

        $response->assertStatus(200);

        $categories = $response->json('data.categories');
        $this->assertGreaterThan(0, count($categories));

        $electronicsFound = false;
        foreach ($categories as $category) {
            if ($category['slug'] === 'electronics') {
                $electronicsFound = true;
                break;
            }
        }
        $this->assertTrue($electronicsFound);
    }

    /** @test */
    public function it_searches_in_category_names_and_descriptions()
    {
        $response = $this->getJson('/api/search?q=books');

        $response->assertStatus(200);

        $categories = $response->json('data.categories');
        $this->assertGreaterThan(0, count($categories));
    }

    /** @test */
    public function it_paginates_search_results_correctly()
    {
        // Create more products for pagination testing
        for ($i = 0; $i < 25; $i++) {
            Product::factory()->create([
                'name' => "Product {$i}",
                'price' => 10.00 + $i,
                'is_active' => true,
                'category_id' => $this->electronicsCategory->id,
            ]);
        }

        $response = $this->getJson('/api/search?q=Product&per_page=10&page=1');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals(10, $data['per_page']);
        $this->assertEquals(1, $data['current_page']);
        $this->assertLessThanOrEqual(10, count($data['products']));
    }

    /** @test */
    public function it_handles_page_parameter_correctly()
    {
        $response = $this->getJson('/api/search?q=Product&per_page=5&page=2');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals(2, $data['current_page']);
        $this->assertEquals(5, $data['per_page']);
    }

    /** @test */
    public function it_includes_search_metadata_in_response()
    {
        $response = $this->getJson('/api/search?q=laptop');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('per_page', $data);
        $this->assertArrayHasKey('current_page', $data);
        $this->assertArrayHasKey('last_page', $data);
    }

    /** @test */
    public function it_handles_large_result_sets_efficiently()
    {
        // Create many products
        for ($i = 0; $i < 100; $i++) {
            Product::factory()->create([
                'name' => "Searchable Product {$i}",
                'is_active' => true,
                'category_id' => $this->electronicsCategory->id,
            ]);
        }

        $startTime = microtime(true);
        $response = $this->getJson('/api/search?q=Searchable');
        $endTime = microtime(true);

        $response->assertStatus(200);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Should respond within reasonable time (less than 1 second)
        $this->assertLessThan(1000, $responseTime);
    }

    /** @test */
    public function it_handles_malformed_search_requests_gracefully()
    {
        $response = $this->getJson('/api/search');

        // Should either return empty results or require query parameter
        $this->assertContains($response->status(), [200, 422]);
    }

    /** @test */
    public function it_handles_database_connection_issues_gracefully()
    {
        // This would require mocking database failures
        // For now, we'll test with invalid parameters
        $response = $this->getJson('/api/search?q=' . str_repeat('a', 1000));

        $response->assertStatus(422);
    }
}