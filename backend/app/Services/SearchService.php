<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SearchService
{
    /**
     * Search configuration constants
     */
    private const CACHE_TTL_SEARCH = 300; // 5 minutes
    private const CACHE_TTL_SUGGESTIONS = 3600; // 1 hour
    private const MAX_RESULTS = 1000;
    private const PERFORMANCE_THRESHOLD = 250; // milliseconds

    /**
     * Execute comprehensive search with performance monitoring
     */
    public function search(array $filters, int $perPage = 20, int $page = 1): array
    {
        $startTime = microtime(true);

        try {
            // Generate cache key
            $cacheKey = $this->generateCacheKey($filters, $perPage, $page);

            // Check cache first
            $cachedResult = Cache::get($cacheKey);
            if ($cachedResult && config('cache.search_enabled', true)) {
                $this->logPerformance('CACHE_HIT', microtime(true) - $startTime, $filters);
                return $cachedResult;
            }

            // Build and execute search query
            $queryBuilder = $this->buildSearchQuery($filters);
            $results = $this->executeSearch($queryBuilder, $perPage, $page);

            // Cache results if we got data
            if ($results['total'] > 0 && config('cache.search_enabled', true)) {
                Cache::put($cacheKey, $results, now()->addSeconds(self::CACHE_TTL_SEARCH));
            }

            $searchTime = microtime(true) - $startTime;
            $this->logPerformance('SEARCH_EXECUTED', $searchTime, $filters, $results['total']);

            return $results;

        } catch (\Exception $e) {
            $searchTime = microtime(true) - $startTime;
            $this->logPerformance('SEARCH_ERROR', $searchTime, $filters, 0, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate search suggestions with caching
     */
    public function getSuggestions(string $query, int $limit = 10): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $cacheKey = "search_suggestions:" . md5(strtolower($query) . $limit);

        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SUGGESTIONS), function() use ($query, $limit) {
            return $this->generateSuggestions($query, $limit);
        });
    }

    /**
     * Build optimized search query
     */
    private function buildSearchQuery(array $filters): Builder
    {
        // Base query with optimized joins
        $query = Product::query()
            ->select([
                'products.*',
                DB::raw('COUNT(DISTINCT product_variants.id) as variant_count'),
                DB::raw('MIN(product_variants.price) as min_variant_price'),
                DB::raw('MAX(product_variants.price) as max_variant_price'),
                DB::raw('SUM(CASE WHEN product_variants.stock_quantity > 0 THEN 1 ELSE 0 END) as available_variants'),
            ])
            ->leftJoin('product_variants', function($join) {
                $join->on('products.id', '=', 'product_variants.product_id')
                     ->where('product_variants.is_active', '=', true);
            })
            ->leftJoin('product_categories', 'products.id', '=', 'product_categories.product_id')
            ->leftJoin('categories', function($join) {
                $join->on('product_categories.category_id', '=', 'categories.id')
                     ->where('categories.is_active', '=', true);
            })
            ->where('products.is_active', true)
            ->groupBy('products.id');

        // Apply search term if provided
        if (!empty($filters['q'])) {
            $this->applySearchTerm($query, $filters['q']);
        }

        // Apply category filter
        if (!empty($filters['category_id'])) {
            $query->where('product_categories.category_id', $filters['category_id']);
        }

        if (!empty($filters['category_slug'])) {
            $query->where('categories.slug', $filters['category_slug']);
        }

        // Apply price range filters with optimization
        $this->applyPriceFilters($query, $filters);

        // Apply stock filter
        if (!empty($filters['in_stock'])) {
            $this->applyStockFilter($query);
        }

        // Apply attribute filters
        if (!empty($filters['attributes'])) {
            $this->applyAttributeFilters($query, $filters['attributes']);
        }

        return $query;
    }

    /**
     * Apply search term with relevance scoring
     */
    private function applySearchTerm(Builder $query, string $searchTerm): void
    {
        $searchTerm = trim($searchTerm);
        $likeTerm = "%{$searchTerm}%";

        $query->where(function($q) use ($searchTerm, $likeTerm) {
            // Exact matches get highest priority
            $q->where('products.name', 'like', $likeTerm)
              ->orWhere('products.sku', 'like', $likeTerm)
              ->orWhere('product_variants.sku', 'like', $likeTerm)

              // Partial matches in description get lower priority
              ->orWhere('products.description', 'like', $likeTerm)
              ->orWhere('products.short_description', 'like', $likeTerm)

              // Category matches get lowest priority
              ->orWhere('categories.name', 'like', $likeTerm);
        });
    }

    /**
     * Apply price range filters with optimization
     */
    private function applyPriceFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['min_price'])) {
            $minPrice = (float) $filters['min_price'];
            $query->where(function($q) use ($minPrice) {
                $q->where('products.price', '>=', $minPrice)
                  ->orWhere('product_variants.price', '>=', $minPrice);
            });
        }

        if (!empty($filters['max_price'])) {
            $maxPrice = (float) $filters['max_price'];
            $query->where(function($q) use ($maxPrice) {
                $q->where('products.price', '<=', $maxPrice)
                  ->orWhere('product_variants.price', '<=', $maxPrice);
            });
        }
    }

    /**
     * Apply stock availability filter
     */
    private function applyStockFilter(Builder $query): void
    {
        $query->where(function($q) {
            $q->where('products.track_inventory', false)
              ->orWhere(function($qq) {
                  $qq->where('products.track_inventory', true)
                    ->whereRaw('products.id IN (
                        SELECT product_id FROM product_variants
                        WHERE stock_quantity > 0 AND is_active = true
                    )');
              });
        });
    }

    /**
     * Apply attribute-based filters
     */
    private function applyAttributeFilters(Builder $query, array $attributes): void
    {
        foreach ($attributes as $attributeFilter) {
            if (empty($attributeFilter['attribute_id']) || empty($attributeFilter['value_ids'])) {
                continue;
            }

            $attributeId = $attributeFilter['attribute_id'];
            $valueIds = $attributeFilter['value_ids'];

            $query->whereExists(function($q) use ($attributeId, $valueIds) {
                $q->selectRaw(1)
                  ->from('product_attribute_values')
                  ->whereRaw('product_attribute_values.product_id = products.id')
                  ->where('product_attribute_values.attribute_value_id', 'IN', $valueIds);
            });
        }
    }

    /**
     * Execute search with pagination and sorting
     */
    private function executeSearch(Builder $query, int $perPage, int $page): array
    {
        // Apply sorting
        $this->applySorting($query, $filters ?? []);

        // Get total count for pagination
        $total = DB::table(DB::raw("({$query->toSql()}) as search_count"))
            ->mergeBindings($query->getQuery())
            ->count();

        // Limit maximum results to prevent performance issues
        $total = min($total, self::MAX_RESULTS);

        // Get paginated results
        $offset = ($page - 1) * $perPage;
        $products = $query->offset($offset)->limit($perPage)->get();

        return [
            'products' => $products,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total),
        ];
    }

    /**
     * Apply sorting with relevance scoring
     */
    private function applySorting(Builder $query, array $filters): void
    {
        $sortBy = $filters['sort_by'] ?? 'relevance';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        switch ($sortBy) {
            case 'relevance':
                if (!empty($filters['q'])) {
                    // Sort by relevance score
                    $query->orderByRaw("
                        CASE
                            WHEN products.name LIKE ? THEN 4
                            WHEN products.sku LIKE ? THEN 3
                            WHEN product_variants.sku LIKE ? THEN 3
                            WHEN products.description LIKE ? THEN 2
                            WHEN products.short_description LIKE ? THEN 2
                            WHEN categories.name LIKE ? THEN 1
                            ELSE 0
                        END DESC,
                        products.name ASC
                    ", [
                        "%{$filters['q']}%",
                        "%{$filters['q']}%",
                        "%{$filters['q']}%",
                        "%{$filters['q']}%",
                        "%{$filters['q']}%",
                        "%{$filters['q']}%"
                    ]);
                } else {
                    $query->orderBy('products.created_at', 'desc');
                }
                break;

            case 'price':
                $query->orderByRaw("COALESCE(MIN(product_variants.price), products.price, 0) {$sortDirection}");
                break;

            case 'name':
                $query->orderBy('products.name', $sortDirection);
                break;

            case 'newest':
            case 'created_at':
                $query->orderBy('products.created_at', $sortDirection);
                break;

            default:
                $query->orderBy('products.name', 'asc');
                break;
        }
    }

    /**
     * Generate search suggestions
     */
    private function generateSuggestions(string $query, int $limit): array
    {
        $suggestions = [];

        // Product name suggestions (highest priority)
        $productNames = Product::where('is_active', true)
            ->where('name', 'like', "%{$query}%")
            ->orderByRaw("
                CASE
                    WHEN name LIKE ? THEN 1
                    ELSE 2
                END, name ASC
            ", ["{$query}%"])
            ->limit($limit)
            ->pluck('name')
            ->toArray();

        $suggestions = array_merge($suggestions, $productNames);

        // SKU suggestions (high priority)
        $productSkus = Product::where('is_active', true)
            ->where('sku', 'like', "%{$query}%")
            ->limit($limit)
            ->pluck('sku')
            ->toArray();

        $suggestions = array_merge($suggestions, $productSkus);

        $variantSkus = ProductVariant::where('is_active', true)
            ->where('sku', 'like', "%{$query}%")
            ->limit($limit)
            ->pluck('sku')
            ->toArray();

        $suggestions = array_merge($suggestions, $variantSkus);

        // Category suggestions (lower priority)
        $categoryNames = Category::where('is_active', true)
            ->where('name', 'like', "%{$query}%")
            ->limit($limit)
            ->pluck('name')
            ->toArray();

        $suggestions = array_merge($suggestions, $categoryNames);

        // Remove duplicates and limit results
        $suggestions = array_unique($suggestions);
        $suggestions = array_slice($suggestions, 0, $limit);

        return array_values($suggestions);
    }

    /**
     * Generate cache key for search results
     */
    private function generateCacheKey(array $filters, int $perPage, int $page): string
    {
        $keyData = [
            'q' => $filters['q'] ?? '',
            'category_id' => $filters['category_id'] ?? '',
            'category_slug' => $filters['category_slug'] ?? '',
            'min_price' => $filters['min_price'] ?? '',
            'max_price' => $filters['max_price'] ?? '',
            'attributes' => md5(serialize($filters['attributes'] ?? '')),
            'in_stock' => $filters['in_stock'] ?? '',
            'sort_by' => $filters['sort_by'] ?? 'relevance',
            'sort_direction' => $filters['sort_direction'] ?? 'desc',
            'per_page' => $perPage,
            'page' => $page,
        ];

        return 'search_results:' . md5(serialize($keyData));
    }

    /**
     * Log search performance metrics
     */
    private function logPerformance(string $type, float $executionTime, array $filters, int $resultCount = 0, string $error = null): void
    {
        $logData = [
            'type' => $type,
            'execution_time_ms' => round($executionTime * 1000, 2),
            'query' => $filters['q'] ?? '',
            'result_count' => $resultCount,
            'filters' => $this->getAppliedFilters($filters),
            'performance_ok' => ($executionTime * 1000) < self::PERFORMANCE_THRESHOLD,
        ];

        if ($error) {
            $logData['error'] = $error;
            Log::warning('Search performance issue', $logData);
        } elseif (($executionTime * 1000) > self::PERFORMANCE_THRESHOLD) {
            Log::info('Slow search query detected', $logData);
        } else {
            Log::info('Search performance metrics', $logData);
        }
    }

    /**
     * Get applied filters for logging
     */
    private function getAppliedFilters(array $filters): array
    {
        $applied = [];

        if (!empty($filters['category_id'])) {
            $applied['category_id'] = $filters['category_id'];
        }

        if (!empty($filters['category_slug'])) {
            $applied['category_slug'] = $filters['category_slug'];
        }

        if (!empty($filters['min_price'])) {
            $applied['min_price'] = $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $applied['max_price'] = $filters['max_price'];
        }

        if (!empty($filters['attributes'])) {
            $applied['attributes_count'] = count($filters['attributes']);
        }

        if (!empty($filters['in_stock'])) {
            $applied['in_stock'] = $filters['in_stock'];
        }

        return $applied;
    }

    /**
     * Clear search cache (useful for admin operations)
     */
    public function clearCache(): void
    {
        Cache::flush();
        Log::info('Search cache cleared');
    }

    /**
     * Get search performance statistics
     */
    public function getPerformanceStats(): array
    {
        return [
            'cache_enabled' => config('cache.search_enabled', true),
            'cache_ttl' => self::CACHE_TTL_SEARCH,
            'performance_threshold_ms' => self::PERFORMANCE_THRESHOLD,
            'max_results' => self::MAX_RESULTS,
        ];
    }
}