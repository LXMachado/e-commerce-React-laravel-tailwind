<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Main search endpoint with comprehensive filtering
     */
    public function search(Request $request): JsonResponse
    {
        try {
            // Validate request parameters
            $validated = $request->validate([
                'q' => 'nullable|string|max:255',
                'category_id' => 'nullable|exists:categories,id',
                'category_slug' => 'nullable|string|exists:categories,slug',
                'min_price' => 'nullable|numeric|min:0',
                'max_price' => 'nullable|numeric|min:0',
                'attributes' => 'nullable|array',
                'attributes.*.attribute_id' => 'required|exists:attributes,id',
                'attributes.*.value_ids' => 'required|array',
                'attributes.*.value_ids.*' => 'exists:attribute_values,id',
                'in_stock' => 'nullable|boolean',
                'sort_by' => 'nullable|in:relevance,name,price,created_at,newest',
                'sort_direction' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
            ]);

            $perPage = min($validated['per_page'] ?? 20, 100);
            $page = $validated['page'] ?? 1;

            // Execute search using the service
            $searchResults = $this->searchService->search($validated, $perPage, $page);

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $this->formatPaginatedResults($searchResults),
                    'search_metadata' => [
                        'query' => $validated['q'] ?? '',
                        'result_count' => $searchResults['total'],
                        'filters_applied' => $this->getAppliedFilters($validated),
                        'sort_by' => $validated['sort_by'] ?? 'relevance',
                        'sort_direction' => $validated['sort_direction'] ?? 'desc',
                        'performance_stats' => $this->searchService->getPerformanceStats(),
                    ]
                ],
                'message' => 'Search completed successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Autocomplete suggestions endpoint
     */
    public function suggestions(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'q' => 'required|string|min:2|max:100',
                'limit' => 'nullable|integer|min:1|max:20',
            ]);

            $query = $request->q;
            $limit = min($request->limit ?? 10, 20);

            // Get suggestions using the service
            $suggestions = $this->searchService->getSuggestions($query, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'query' => $query,
                    'suggestions' => $suggestions,
                ],
                'message' => 'Suggestions retrieved successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve suggestions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Format search results as Laravel paginator for consistent API response
     */
    private function formatPaginatedResults(array $searchResults): object
    {
        // Create a simple paginator-like object for API consistency
        return (object) [
            'data' => $searchResults['products'],
            'total' => $searchResults['total'],
            'per_page' => $searchResults['per_page'],
            'current_page' => $searchResults['current_page'],
            'last_page' => $searchResults['last_page'],
            'from' => $searchResults['from'],
            'to' => $searchResults['to'],
        ];
    }

    /**
     * Get applied filters for metadata
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
            $applied['attributes'] = $filters['attributes'];
        }

        if (!empty($filters['in_stock'])) {
            $applied['in_stock'] = $filters['in_stock'];
        }

        return $applied;
    }
}
