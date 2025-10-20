<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $category;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Create test category with SEO data
        $this->category = Category::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'meta_title' => 'Electronics | Premium Tech Products',
            'meta_description' => 'Discover premium electronics and tech products at great prices',
        ]);

        // Create test product with SEO data
        $this->product = Product::factory()->create([
            'name' => 'Gaming Laptop Pro',
            'slug' => 'gaming-laptop-pro',
            'meta_title' => 'Gaming Laptop Pro | High Performance Gaming',
            'meta_description' => 'Experience ultimate gaming performance with our Gaming Laptop Pro',
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);
    }

    /** @test */
    public function it_generates_correct_meta_tags_for_products()
    {
        $response = $this->getJson("/api/seo/meta/products/{$this->product->slug}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'title',
                        'description',
                        'keywords',
                        'canonical_url',
                        'og_title',
                        'og_description',
                        'og_image',
                        'og_type',
                        'twitter_card',
                        'twitter_title',
                        'twitter_description',
                        'twitter_image',
                        'structured_data'
                    ],
                    'message'
                ]);

        $seoData = $response->json('data');
        $this->assertEquals('Gaming Laptop Pro | High Performance Gaming', $seoData['title']);
        $this->assertEquals('Experience ultimate gaming performance with our Gaming Laptop Pro', $seoData['description']);
        $this->assertStringContainsString($this->product->slug, $seoData['canonical_url']);
    }

    /** @test */
    public function it_generates_correct_meta_tags_for_categories()
    {
        $response = $this->getJson("/api/seo/meta/categories/{$this->category->slug}");

        $response->assertStatus(200);

        $seoData = $response->json('data');
        $this->assertEquals('Electronics | Premium Tech Products', $seoData['title']);
        $this->assertEquals('Discover premium electronics and tech products at great prices', $seoData['description']);
        $this->assertStringContainsString($this->category->slug, $seoData['canonical_url']);
    }

    /** @test */
    public function it_handles_missing_seo_data_gracefully()
    {
        $productWithoutSeo = Product::factory()->create([
            'name' => 'Basic Product',
            'slug' => 'basic-product',
            // No meta_title or meta_description
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/seo/meta/products/{$productWithoutSeo->slug}");

        $response->assertStatus(200);

        $seoData = $response->json('data');
        $this->assertEquals('Basic Product', $seoData['title']); // Should fall back to product name
        $this->assertEquals('', $seoData['description']); // Should be empty if no description
    }

    /** @test */
    public function it_returns_404_for_non_existent_resources()
    {
        $response = $this->getJson('/api/seo/meta/products/non-existent-product');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Product not found'
                ]);
    }

    /** @test */
    public function it_generates_correct_open_graph_tags_for_products()
    {
        $response = $this->getJson("/api/seo/meta/products/{$this->product->slug}");

        $response->assertStatus(200);

        $seoData = $response->json('data');
        $this->assertEquals($seoData['title'], $seoData['og_title']);
        $this->assertEquals('product', $seoData['og_type']);
        $this->assertStringContainsString('http', $seoData['og_image']); // Should be absolute URL
    }

    /** @test */
    public function it_generates_correct_twitter_card_tags()
    {
        $response = $this->getJson("/api/seo/meta/products/{$this->product->slug}");

        $response->assertStatus(200);

        $seoData = $response->json('data');
        $this->assertContains($seoData['twitter_card'], ['summary', 'summary_large_image']);
        $this->assertEquals($seoData['title'], $seoData['twitter_title']);
    }

    /** @test */
    public function it_includes_structured_data_for_rich_snippets()
    {
        $response = $this->getJson("/api/seo/meta/products/{$this->product->slug}");

        $response->assertStatus(200);

        $seoData = $response->json('data');
        $this->assertIsArray($seoData['structured_data']);
        $this->assertArrayHasKey('@context', $seoData['structured_data']);
        $this->assertArrayHasKey('@type', $seoData['structured_data']);
    }

    /** @test */
    public function it_generates_meta_tags_efficiently()
    {
        $startTime = microtime(true);
        $response = $this->getJson("/api/seo/meta/products/{$this->product->slug}");
        $endTime = microtime(true);

        $response->assertStatus(200);
        $generationTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Should generate meta tags within reasonable time (less than 100ms)
        $this->assertLessThan(100, $generationTime);
    }

    /** @test */
    public function it_validates_meta_title_length()
    {
        $productWithLongTitle = Product::factory()->create([
            'name' => 'Product with Extremely Long Name That Exceeds Recommended SEO Title Length Limits',
            'slug' => 'long-title-product',
            'meta_title' => str_repeat('A', 70), // Longer than recommended 60 chars
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/seo/meta/products/{$productWithLongTitle->slug}");

        $response->assertStatus(200);

        $seoData = $response->json('data');
        $this->assertLessThanOrEqual(60, strlen($seoData['title'])); // Should be truncated
    }

    /** @test */
    public function it_sanitizes_meta_content_for_html_entities()
    {
        $productWithHtml = Product::factory()->create([
            'name' => 'Product & Special <Characters>',
            'slug' => 'html-product',
            'meta_description' => 'Description with <script>alert("hack")</script> content',
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/seo/meta/products/{$productWithHtml->slug}");

        $response->assertStatus(200);

        $seoData = $response->json('data');
        $this->assertStringNotContainsString('<', $seoData['title']);
        $this->assertStringNotContainsString('>', $seoData['title']);
        $this->assertStringNotContainsString('<script>', $seoData['description']);
    }

    /** @test */
    public function it_handles_malformed_urls_gracefully()
    {
        $response = $this->getJson('/api/seo/meta/products/');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_provides_fallback_seo_data_when_generation_fails()
    {
        // Create a product with minimal data
        $minimalProduct = Product::factory()->create([
            'name' => 'Minimal Product',
            'slug' => 'minimal-product',
            // No meta data provided
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/seo/meta/products/{$minimalProduct->slug}");

        $response->assertStatus(200);

        $seoData = $response->json('data');
        $this->assertEquals('Minimal Product', $seoData['title']); // Should fall back to name
        $this->assertEquals('', $seoData['description']); // Should be empty but not cause errors
    }

    /** @test */
    public function it_generates_appropriate_keywords_from_content()
    {
        $response = $this->getJson("/api/seo/meta/products/{$this->product->slug}");

        $response->assertStatus(200);

        $seoData = $response->json('data');
        $this->assertIsArray($seoData['keywords']);
        $this->assertContains('gaming', $seoData['keywords']);
        $this->assertContains('laptop', $seoData['keywords']);
    }
}