<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Product::query()->with(['categories', 'primaryVariant', 'attributeValues.attribute']);

            // Filter by category
            if ($request->has('category_id')) {
                $query->whereHas('categories', function($q) use ($request) {
                    $q->where('categories.id', $request->category_id);
                });
            }

            // Filter by active status
            if ($request->has('active')) {
                $query->where('is_active', $request->boolean('active'));
            }

            // Search by name or description
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            // Filter by price range
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            // Filter by attributes
            if ($request->has('attributes')) {
                $attributes = $request->attributes;
                foreach ($attributes as $attributeId => $valueIds) {
                    $query->whereHas('attributeValues', function($q) use ($attributeId, $valueIds) {
                        $q->where('attribute_id', $attributeId)
                          ->whereIn('id', $valueIds);
                    });
                }
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortDirection = $request->get('sort_direction', 'asc');

            switch ($sortBy) {
                case 'price':
                    $query->orderBy('price', $sortDirection);
                    break;
                case 'created_at':
                    $query->orderBy('created_at', $sortDirection);
                    break;
                case 'name':
                default:
                    $query->orderBy('name', $sortDirection);
                    break;
            }

            $perPage = min($request->get('per_page', 15), 100);
            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Products retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:products',
                'description' => 'nullable|string',
                'short_description' => 'nullable|string|max:500',
                'sku' => 'required|string|max:100|unique:products',
                'price' => 'required|numeric|min:0',
                'compare_at_price' => 'nullable|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'track_inventory' => 'boolean',
                'weight_g' => 'nullable|integer|min:0',
                'dimensions' => 'nullable|string|max:255',
                'is_active' => 'boolean',
                'seo_title' => 'nullable|string|max:255',
                'seo_description' => 'nullable|string',
                'category_ids' => 'nullable|array',
                'category_ids.*' => 'exists:categories,id',
                'attribute_value_ids' => 'nullable|array',
                'attribute_value_ids.*' => 'exists:attribute_values,id',
            ]);

            $product = Product::create($validated);

            // Attach categories
            if (isset($validated['category_ids'])) {
                $product->categories()->attach($validated['category_ids']);
            }

            // Attach attribute values
            if (isset($validated['attribute_value_ids'])) {
                $product->attributeValues()->attach($validated['attribute_value_ids']);
            }

            return response()->json([
                'success' => true,
                'data' => $product->load(['categories', 'attributeValues.attribute']),
                'message' => 'Product created successfully'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        try {
            $product->load([
                'categories',
                'variants' => function($query) {
                    $query->where('is_active', true)->orderBy('price');
                },
                'attributeValues.attribute'
            ]);

            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Product retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => ['required', 'string', 'max:255', Rule::unique('products')->ignore($product->id)],
                'description' => 'nullable|string',
                'short_description' => 'nullable|string|max:500',
                'sku' => ['required', 'string', 'max:100', Rule::unique('products')->ignore($product->id)],
                'price' => 'required|numeric|min:0',
                'compare_at_price' => 'nullable|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'track_inventory' => 'boolean',
                'weight_g' => 'nullable|integer|min:0',
                'dimensions' => 'nullable|string|max:255',
                'is_active' => 'boolean',
                'seo_title' => 'nullable|string|max:255',
                'seo_description' => 'nullable|string',
                'category_ids' => 'nullable|array',
                'category_ids.*' => 'exists:categories,id',
                'attribute_value_ids' => 'nullable|array',
                'attribute_value_ids.*' => 'exists:attribute_values,id',
            ]);

            $product->update($validated);

            // Sync categories
            if (isset($validated['category_ids'])) {
                $product->categories()->sync($validated['category_ids']);
            }

            // Sync attribute values
            if (isset($validated['attribute_value_ids'])) {
                $product->attributeValues()->sync($validated['attribute_value_ids']);
            }

            return response()->json([
                'success' => true,
                'data' => $product->fresh()->load(['categories', 'attributeValues.attribute']),
                'message' => 'Product updated successfully'
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
                'message' => 'Failed to update product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): JsonResponse
    {
        try {
            // Check if product has variants
            if ($product->variants()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete product with existing variants'
                ], 422);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products by category slug.
     */
    public function byCategory(string $categorySlug): JsonResponse
    {
        try {
            $category = Category::where('slug', $categorySlug)->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            $products = Product::where('is_active', true)
                              ->whereHas('categories', function($q) use ($category) {
                                  $q->where('categories.id', $category->id);
                              })
                              ->with(['categories', 'primaryVariant', 'attributeValues.attribute'])
                              ->orderBy('name')
                              ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $products,
                'category' => $category,
                'message' => 'Products retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve products by category',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}