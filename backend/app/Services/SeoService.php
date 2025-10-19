<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use App\Models\ContentPage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SeoService
{
    /**
     * SEO configuration constants
     */
    private const CACHE_TTL_SEO = 3600; // 1 hour
    private const CACHE_TTL_SITEMAP = 86400; // 24 hours
    private const DEFAULT_SITE_NAME = 'Weekender Solar';
    private const DEFAULT_SITE_DESCRIPTION = 'Premium solar-powered products and accessories for sustainable living';

    /**
     * Generate meta tags for products
     */
    public function generateProductMeta(Product $product): array
    {
        $cacheKey = "seo:product:{$product->id}";

        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SEO), function() use ($product) {
            return [
                'title' => $this->generateProductTitle($product),
                'description' => $this->generateProductDescription($product),
                'keywords' => $this->generateProductKeywords($product),
                'canonical_url' => $this->generateProductCanonicalUrl($product),
                'open_graph' => $this->generateProductOpenGraph($product),
                'structured_data' => $this->generateProductStructuredData($product),
            ];
        });
    }

    /**
     * Generate meta tags for categories
     */
    public function generateCategoryMeta(Category $category): array
    {
        $cacheKey = "seo:category:{$category->id}";

        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SEO), function() use ($category) {
            return [
                'title' => $this->generateCategoryTitle($category),
                'description' => $this->generateCategoryDescription($category),
                'keywords' => $this->generateCategoryKeywords($category),
                'canonical_url' => $this->generateCategoryCanonicalUrl($category),
                'open_graph' => $this->generateCategoryOpenGraph($category),
                'structured_data' => $this->generateCategoryStructuredData($category),
            ];
        });
    }

    /**
     * Generate meta tags for search pages
     */
    public function generateSearchMeta(string $query, int $resultCount = 0): array
    {
        $title = $this->generateSearchTitle($query, $resultCount);
        $description = $this->generateSearchDescription($query, $resultCount);

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $this->generateSearchKeywords($query),
            'canonical_url' => $this->generateSearchCanonicalUrl($query),
            'open_graph' => $this->generateSearchOpenGraph($query, $title, $description),
            'structured_data' => $this->generateSearchStructuredData($query, $resultCount),
        ];
    }

    /**
     * Generate meta tags for content pages
     */
    public function generateContentPageMeta(ContentPage $page): array
    {
        $cacheKey = "seo:content:{$page->id}";

        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SEO), function() use ($page) {
            return [
                'title' => $page->seo_title ?: $page->title,
                'description' => $page->seo_description ?: Str::limit(strip_tags($page->content), 160),
                'keywords' => $this->extractKeywordsFromContent($page->content),
                'canonical_url' => $this->generateContentPageCanonicalUrl($page),
                'open_graph' => $this->generateContentPageOpenGraph($page),
                'structured_data' => $this->generateContentPageStructuredData($page),
            ];
        });
    }

    /**
     * Generate XML sitemap
     */
    public function generateSitemap(): string
    {
        $cacheKey = 'seo:sitemap';

        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SITEMAP), function() {
            return $this->buildSitemap();
        });
    }

    /**
     * Generate robots.txt content
     */
    public function generateRobotsTxt(): string
    {
        $cacheKey = 'seo:robots';

        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SITEMAP), function() {
            return $this->buildRobotsTxt();
        });
    }

    /**
     * Generate product title
     */
    private function generateProductTitle(Product $product): string
    {
        if ($product->seo_title) {
            return $product->seo_title;
        }

        $brand = config('app.seo.brand_name', self::DEFAULT_SITE_NAME);
        return "{$product->name} - {$brand} | Solar Power Products";
    }

    /**
     * Generate product description
     */
    private function generateProductDescription(Product $product): string
    {
        if ($product->seo_description) {
            return $product->seo_description;
        }

        $description = $product->short_description ?: Str::limit(strip_tags($product->description), 160);
        return $description ?: "High-quality {$product->name} available at " . self::DEFAULT_SITE_NAME;
    }

    /**
     * Generate product keywords
     */
    private function generateProductKeywords(Product $product): string
    {
        $keywords = [];

        // Add product name words
        $nameWords = explode(' ', strtolower($product->name));
        $keywords = array_merge($keywords, $nameWords);

        // Add category names if available
        foreach ($product->categories as $category) {
            $categoryWords = explode(' ', strtolower($category->name));
            $keywords = array_merge($keywords, $categoryWords);
        }

        // Add solar-related keywords
        $solarKeywords = ['solar', 'solar power', 'solar energy', 'renewable', 'sustainable', 'eco-friendly'];
        $keywords = array_merge($keywords, $solarKeywords);

        // Remove duplicates and short words
        $keywords = array_unique(array_filter($keywords, fn($word) => strlen($word) > 2));

        return implode(', ', array_slice($keywords, 0, 10));
    }

    /**
     * Generate product canonical URL
     */
    private function generateProductCanonicalUrl(Product $product): string
    {
        return url("/products/{$product->slug}");
    }

    /**
     * Generate product Open Graph tags
     */
    private function generateProductOpenGraph(Product $product): array
    {
        return [
            'og:type' => 'product',
            'og:title' => $this->generateProductTitle($product),
            'og:description' => $this->generateProductDescription($product),
            'og:url' => $this->generateProductCanonicalUrl($product),
            'og:site_name' => self::DEFAULT_SITE_NAME,
            'og:image' => $this->getProductImage($product),
            'og:price:amount' => $product->price,
            'og:price:currency' => 'AUD',
            'og:availability' => $product->is_active ? 'in stock' : 'out of stock',
        ];
    }

    /**
     * Generate product structured data (Schema.org)
     */
    private function generateProductStructuredData(Product $product): array
    {
        $offers = [];

        // Main product offer
        if ($product->price) {
            $offers[] = [
                '@type' => 'Offer',
                'price' => $product->price,
                'priceCurrency' => 'AUD',
                'availability' => $product->is_active ?
                    'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url' => $this->generateProductCanonicalUrl($product),
            ];
        }

        // Variant offers
        foreach ($product->variants as $variant) {
            if ($variant->is_active && $variant->price) {
                $offers[] = [
                    '@type' => 'Offer',
                    'price' => $variant->price,
                    'priceCurrency' => 'AUD',
                    'availability' => $variant->stock_quantity > 0 ?
                        'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'sku' => $variant->sku,
                    'url' => $this->generateProductCanonicalUrl($product),
                ];
            }
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $this->generateProductDescription($product),
            'sku' => $product->sku,
            'brand' => [
                '@type' => 'Brand',
                'name' => self::DEFAULT_SITE_NAME,
            ],
            'category' => $product->categories->pluck('name')->join(', '),
            'offers' => $offers,
            'image' => $this->getProductImage($product),
        ];
    }

    /**
     * Generate category title
     */
    private function generateCategoryTitle(Category $category): string
    {
        if ($category->seo_title) {
            return $category->seo_title;
        }

        $brand = config('app.seo.brand_name', self::DEFAULT_SITE_NAME);
        return "{$category->name} - {$brand} | Solar Products Category";
    }

    /**
     * Generate category description
     */
    private function generateCategoryDescription(Category $category): string
    {
        if ($category->seo_description) {
            return $category->seo_description;
        }

        $productCount = $category->products()->where('is_active', true)->count();
        return "Browse {$productCount} high-quality {$category->name} products at " . self::DEFAULT_SITE_NAME . ". " .
               ($category->description ?: "Find the best solar-powered {$category->name} for your needs.");
    }

    /**
     * Generate category keywords
     */
    private function generateCategoryKeywords(Category $category): string
    {
        $keywords = [];

        // Add category name words
        $nameWords = explode(' ', strtolower($category->name));
        $keywords = array_merge($keywords, $nameWords);

        // Add solar-related keywords
        $solarKeywords = ['solar', 'solar power', 'solar energy', 'renewable', 'sustainable'];
        $keywords = array_merge($keywords, $solarKeywords);

        // Remove duplicates and short words
        $keywords = array_unique(array_filter($keywords, fn($word) => strlen($word) > 2));

        return implode(', ', array_slice($keywords, 0, 8));
    }

    /**
     * Generate category canonical URL
     */
    private function generateCategoryCanonicalUrl(Category $category): string
    {
        return url("/categories/{$category->slug}");
    }

    /**
     * Generate category Open Graph tags
     */
    private function generateCategoryOpenGraph(Category $category): array
    {
        return [
            'og:type' => 'website',
            'og:title' => $this->generateCategoryTitle($category),
            'og:description' => $this->generateCategoryDescription($category),
            'og:url' => $this->generateCategoryCanonicalUrl($category),
            'og:site_name' => self::DEFAULT_SITE_NAME,
        ];
    }

    /**
     * Generate category structured data
     */
    private function generateCategoryStructuredData(Category $category): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $category->name,
            'description' => $this->generateCategoryDescription($category),
            'url' => $this->generateCategoryCanonicalUrl($category),
        ];
    }

    /**
     * Generate search page title
     */
    private function generateSearchTitle(string $query, int $resultCount): string
    {
        if ($resultCount > 0) {
            return "Search results for '{$query}' - {$resultCount} products found | " . self::DEFAULT_SITE_NAME;
        }

        return "Search results for '{$query}' | " . self::DEFAULT_SITE_NAME;
    }

    /**
     * Generate search page description
     */
    private function generateSearchDescription(string $query, int $resultCount): string
    {
        if ($resultCount > 0) {
            return "Found {$resultCount} solar products for '{$query}'. Browse our selection of high-quality solar-powered products.";
        }

        return "No products found for '{$query}'. Try searching for solar panels, batteries, or other solar accessories.";
    }

    /**
     * Generate search keywords
     */
    private function generateSearchKeywords(string $query): string
    {
        $keywords = explode(' ', strtolower($query));
        $keywords[] = 'solar';
        $keywords[] = 'solar power';
        $keywords[] = 'solar products';

        return implode(', ', array_unique(array_filter($keywords, fn($word) => strlen($word) > 2)));
    }

    /**
     * Generate search canonical URL
     */
    private function generateSearchCanonicalUrl(string $query): string
    {
        return url('/search?q=' . urlencode($query));
    }

    /**
     * Generate search Open Graph tags
     */
    private function generateSearchOpenGraph(string $query, string $title, string $description): array
    {
        return [
            'og:type' => 'website',
            'og:title' => $title,
            'og:description' => $description,
            'og:url' => $this->generateSearchCanonicalUrl($query),
            'og:site_name' => self::DEFAULT_SITE_NAME,
        ];
    }

    /**
     * Generate search structured data
     */
    private function generateSearchStructuredData(string $query, int $resultCount): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'SearchResultsPage',
            'name' => "Search results for {$query}",
            'description' => $this->generateSearchDescription($query, $resultCount),
        ];
    }

    /**
     * Generate content page canonical URL
     */
    private function generateContentPageCanonicalUrl(ContentPage $page): string
    {
        return url("/{$page->slug}");
    }

    /**
     * Generate content page Open Graph tags
     */
    private function generateContentPageOpenGraph(ContentPage $page): array
    {
        return [
            'og:type' => 'article',
            'og:title' => $page->seo_title ?: $page->title,
            'og:description' => $page->seo_description ?: Str::limit(strip_tags($page->content), 160),
            'og:url' => $this->generateContentPageCanonicalUrl($page),
            'og:site_name' => self::DEFAULT_SITE_NAME,
        ];
    }

    /**
     * Generate content page structured data
     */
    private function generateContentPageStructuredData(ContentPage $page): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $page->title,
            'description' => $page->seo_description ?: Str::limit(strip_tags($page->content), 160),
            'datePublished' => $page->created_at->toISOString(),
            'dateModified' => $page->updated_at->toISOString(),
        ];
    }

    /**
     * Get product image URL for Open Graph
     */
    private function getProductImage(Product $product): ?string
    {
        // This would typically get the primary product image
        // For now, return a default image or null
        return config('app.seo.default_image_url');
    }

    /**
     * Extract keywords from content
     */
    private function extractKeywordsFromContent(?string $content): string
    {
        if (!$content) {
            return 'solar, solar power, renewable energy';
        }

        // Simple keyword extraction - split by spaces and filter
        $words = str_word_count(strip_tags(strtolower($content)), 1);
        $wordCount = array_count_values($words);

        // Filter out common stop words and short words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $keywords = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 3 && !in_array($word, $stopWords);
        });

        // Sort by frequency and take top keywords
        arsort($wordCount);
        $topKeywords = array_slice(array_keys($wordCount), 0, 8);

        return implode(', ', $topKeywords);
    }

    /**
     * Build XML sitemap
     */
    private function buildSitemap(): string
    {
        $urls = [];

        // Add static pages
        $staticPages = [
            '/' => ['priority' => '1.0', 'changefreq' => 'daily'],
            '/categories' => ['priority' => '0.8', 'changefreq' => 'weekly'],
            '/search' => ['priority' => '0.6', 'changefreq' => 'monthly'],
        ];

        foreach ($staticPages as $url => $meta) {
            $urls[] = [
                'loc' => url($url),
                'priority' => $meta['priority'],
                'changefreq' => $meta['changefreq'],
                'lastmod' => now()->toISOString(),
            ];
        }

        // Add categories
        $categories = Category::where('is_active', true)->get();
        foreach ($categories as $category) {
            $urls[] = [
                'loc' => $this->generateCategoryCanonicalUrl($category),
                'priority' => '0.7',
                'changefreq' => 'weekly',
                'lastmod' => $category->updated_at->toISOString(),
            ];
        }

        // Add products
        $products = Product::where('is_active', true)->get();
        foreach ($products as $product) {
            $urls[] = [
                'loc' => $this->generateProductCanonicalUrl($product),
                'priority' => '0.6',
                'changefreq' => 'monthly',
                'lastmod' => $product->updated_at->toISOString(),
            ];
        }

        // Generate XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . "\n";
            $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
            $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
            $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Build robots.txt content
     */
    private function buildRobotsTxt(): string
    {
        $content = "User-agent: *\n";
        $content .= "Allow: /\n";
        $content .= "Disallow: /admin\n";
        $content .= "Disallow: /api/\n";
        $content .= "Disallow: /checkout\n";
        $content .= "Disallow: /cart\n";
        $content .= "\n";
        $content .= "Sitemap: " . url('/sitemap.xml') . "\n";
        $content .= "\n";
        $content .= "Crawl-delay: 1\n";

        return $content;
    }

    /**
     * Clear SEO cache
     */
    public function clearCache(): void
    {
        Cache::flush();
    }

    /**
     * Get SEO configuration
     */
    public function getConfig(): array
    {
        return [
            'site_name' => self::DEFAULT_SITE_NAME,
            'site_description' => self::DEFAULT_SITE_DESCRIPTION,
            'cache_ttl' => self::CACHE_TTL_SEO,
            'sitemap_cache_ttl' => self::CACHE_TTL_SITEMAP,
        ];
    }
}