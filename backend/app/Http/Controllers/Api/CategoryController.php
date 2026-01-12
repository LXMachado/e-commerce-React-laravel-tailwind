<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Category::query()->with('children');

            // Filter by parent category
            if ($request->has('parent_id')) {
                $query->where('parent_id', $request->parent_id);
            }

            // Filter by active status
            if ($request->has('active')) {
                $query->where('is_active', $request->boolean('active'));
            }

            // Only top-level categories if no parent specified
            if (!$request->has('parent_id')) {
                $query->whereNull('parent_id');
            }

            $categories = $query->orderBy('sort_order')
                              ->orderBy('name')
                              ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Categories retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:categories',
                'description' => 'nullable|string',
                'parent_id' => 'nullable|exists:categories,id',
                'sort_order' => 'integer|min:0',
                'is_active' => 'boolean',
                'seo_title' => 'nullable|string|max:255',
                'seo_description' => 'nullable|string',
            ]);

            $category = Category::create($validated);

            return response()->json([
                'success' => true,
                'data' => $category->load('parent'),
                'message' => 'Category created successfully'
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
                'message' => 'Failed to create category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category): JsonResponse
    {
        try {
            $category->load(['parent', 'children', 'products' => function($query) {
                $query->where('is_active', true)->with('primaryVariant');
            }]);

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Category retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => ['required', 'string', 'max:255', Rule::unique('categories')->ignore($category->id)],
                'description' => 'nullable|string',
                'parent_id' => 'nullable|exists:categories,id',
                'sort_order' => 'integer|min:0',
                'is_active' => 'boolean',
                'seo_title' => 'nullable|string|max:255',
                'seo_description' => 'nullable|string',
            ]);

            $category->update($validated);

            return response()->json([
                'success' => true,
                'data' => $category->fresh()->load('parent'),
                'message' => 'Category updated successfully'
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
                'message' => 'Failed to update category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category): JsonResponse
    {
        try {
            // Check if category has children
            if ($category->children()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category with child categories'
                ], 422);
            }

            // Check if category has products
            if ($category->products()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category with associated products'
                ], 422);
            }

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category tree structure.
     */
    public function tree(): JsonResponse
    {
        try {
            $categories = Category::whereNull('parent_id')
                                ->where('is_active', true)
                                ->with(['children' => function($query) {
                                    $query->where('is_active', true)
                                          ->orderBy('sort_order')
                                          ->orderBy('name');
                                }])
                                ->orderBy('sort_order')
                                ->orderBy('name')
                                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Category tree retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve category tree',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}