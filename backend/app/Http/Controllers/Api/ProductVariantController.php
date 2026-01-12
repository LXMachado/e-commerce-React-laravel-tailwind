<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ProductVariantController extends Controller
{
    /**
     * Display a listing of product variants.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ProductVariant::query()->with('product');

            // Filter by product
            if ($request->has('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            // Filter by active status
            if ($request->has('active')) {
                $query->where('is_active', $request->boolean('active'));
            }

            // Filter by stock availability
            if ($request->has('in_stock')) {
                if ($request->boolean('in_stock')) {
                    $query->where('stock_quantity', '>', 0);
                } else {
                    $query->where('stock_quantity', 0);
                }
            }

            // Filter by price range
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            // Search by SKU or barcode
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('sku', 'like', "%{$search}%")
                      ->orWhere('barcode', 'like', "%{$search}%");
                });
            }

            $variants = $query->orderBy('product_id')
                             ->orderBy('price')
                             ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $variants,
                'message' => 'Product variants retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve product variants',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created product variant.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'sku' => 'required|string|max:100|unique:product_variants',
                'price' => 'required|numeric|min:0',
                'compare_at_price' => 'nullable|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'stock_quantity' => 'required|integer|min:0',
                'weight_g' => 'nullable|integer|min:0',
                'barcode' => 'nullable|string|max:255',
                'is_active' => 'boolean',
            ]);

            $variant = ProductVariant::create($validated);

            return response()->json([
                'success' => true,
                'data' => $variant->load('product'),
                'message' => 'Product variant created successfully'
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
                'message' => 'Failed to create product variant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified product variant.
     */
    public function show(ProductVariant $variant): JsonResponse
    {
        try {
            $variant->load('product');

            return response()->json([
                'success' => true,
                'data' => $variant,
                'message' => 'Product variant retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve product variant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified product variant.
     */
    public function update(Request $request, ProductVariant $variant): JsonResponse
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'sku' => ['required', 'string', 'max:100', Rule::unique('product_variants')->ignore($variant->id)],
                'price' => 'required|numeric|min:0',
                'compare_at_price' => 'nullable|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'stock_quantity' => 'required|integer|min:0',
                'weight_g' => 'nullable|integer|min:0',
                'barcode' => 'nullable|string|max:255',
                'is_active' => 'boolean',
            ]);

            $variant->update($validated);

            return response()->json([
                'success' => true,
                'data' => $variant->fresh()->load('product'),
                'message' => 'Product variant updated successfully'
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
                'message' => 'Failed to update product variant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified product variant.
     */
    public function destroy(ProductVariant $variant): JsonResponse
    {
        try {
            // Check if variant is used in orders
            if ($variant->orderItems()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete variant that has been ordered'
                ], 422);
            }

            // Check if variant is used in bundles
            if ($variant->bundleItems()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete variant that is used in bundles'
                ], 422);
            }

            $variant->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product variant deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product variant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update stock quantity for a variant.
     */
    public function updateStock(Request $request, ProductVariant $variant): JsonResponse
    {
        try {
            $validated = $request->validate([
                'stock_quantity' => 'required|integer|min:0',
                'operation' => 'required|in:add,subtract,set',
            ]);

            $currentStock = $variant->stock_quantity;
            $newStock = $currentStock;

            switch ($validated['operation']) {
                case 'add':
                    $newStock = $currentStock + $validated['stock_quantity'];
                    break;
                case 'subtract':
                    $newStock = max(0, $currentStock - $validated['stock_quantity']);
                    break;
                case 'set':
                    $newStock = $validated['stock_quantity'];
                    break;
            }

            $variant->update(['stock_quantity' => $newStock]);

            return response()->json([
                'success' => true,
                'data' => $variant->fresh(),
                'message' => 'Stock updated successfully',
                'previous_stock' => $currentStock,
                'new_stock' => $newStock,
                'change' => $newStock - $currentStock
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
                'message' => 'Failed to update stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get variants for a specific product.
     */
    public function byProduct(Request $request, $productId): JsonResponse
    {
        try {
            $query = ProductVariant::where('product_id', $productId)
                                  ->where('is_active', true);

            // Filter by stock availability
            if ($request->has('in_stock')) {
                if ($request->boolean('in_stock')) {
                    $query->where('stock_quantity', '>', 0);
                } else {
                    $query->where('stock_quantity', 0);
                }
            }

            $variants = $query->orderBy('price')->get();

            return response()->json([
                'success' => true,
                'data' => $variants,
                'message' => 'Product variants retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve product variants',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}