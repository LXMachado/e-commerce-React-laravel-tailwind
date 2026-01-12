<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\BundleConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class BundleController extends Controller
{
    /**
     * Get bundle details with options
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function show(string $slug): JsonResponse
    {
        $bundle = Bundle::where('slug', $slug)
                       ->where('is_active', true)
                       ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $bundle->id,
                'name' => $bundle->name,
                'slug' => $bundle->slug,
                'description' => $bundle->description,
                'price' => $bundle->price,
                'compare_at_price' => $bundle->compare_at_price,
                'current_price' => $bundle->getCurrentPrice(),
                'is_on_sale' => $bundle->isOnSale(),
                'savings' => $bundle->getSavings(),
                'savings_percentage' => $bundle->getSavingsPercentage(),
                'kit_type' => $bundle->kit_type,
                'base_weight_g' => $bundle->base_weight_g,
                'formatted_base_weight' => $bundle->getFormattedBaseWeight(),
                'available_options' => $bundle->getAvailableOptions(),
                'default_configuration' => $bundle->getDefaultConfiguration(),
                'weight_thresholds' => $bundle->getWeightThresholds(),
                'minimum_weight' => $bundle->getMinimumWeight(),
                'maximum_weight' => $bundle->getMaximumWeight(),
                'bundle_items' => $bundle->bundleItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_variant' => [
                            'id' => $item->productVariant->id,
                            'name' => $item->productVariant->product->name,
                            'sku' => $item->productVariant->sku,
                            'price' => $item->productVariant->price,
                            'weight_g' => $item->productVariant->weight_g,
                        ],
                        'quantity' => $item->quantity,
                        'line_total' => $item->getLineTotal(),
                    ];
                }),
            ]
        ]);
    }

    /**
     * Create custom bundle configuration
     *
     * @param Request $request
     * @param string $slug
     * @return JsonResponse
     * @throws ValidationException
     */
    public function configure(Request $request, string $slug): JsonResponse
    {
        $bundle = Bundle::where('slug', $slug)
                       ->where('is_active', true)
                       ->firstOrFail();

        $validated = $request->validate([
            'configuration' => 'required|array',
            'configuration.espresso_module' => 'boolean',
            'configuration.filter_attachment' => 'boolean',
            'configuration.fan_accessory' => 'boolean',
            'configuration.solar_panel_size' => 'in:10W,15W,20W',
            'name' => 'nullable|string|max:255',
        ]);

        try {
            $configuration = $bundle->createConfiguration(
                $validated['configuration'],
                Auth::id(),
                $validated['name'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Bundle configuration created successfully',
                'data' => [
                    'id' => $configuration->id,
                    'name' => $configuration->name,
                    'sku' => $configuration->sku,
                    'total_price' => $configuration->total_price,
                    'formatted_price' => $configuration->getFormattedPrice(),
                    'total_weight_g' => $configuration->total_weight_g,
                    'formatted_weight' => $configuration->getFormattedWeight(),
                    'weight_compatibility' => $configuration->weight_compatibility,
                    'compatibility_description' => $configuration->getWeightCompatibilityDescription(),
                    'configuration_summary' => $configuration->getConfigurationSummary(),
                    'share_token' => $configuration->share_token,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create bundle configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get saved configuration
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getConfiguration(int $id): JsonResponse
    {
        $configuration = BundleConfiguration::with('bundle')
                                           ->where('id', $id)
                                           ->where('is_active', true)
                                           ->firstOrFail();

        // Check if user owns this configuration or if it's shared
        if ($configuration->user_id && $configuration->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to configuration'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $configuration->id,
                'name' => $configuration->name,
                'bundle' => [
                    'id' => $configuration->bundle->id,
                    'name' => $configuration->bundle->name,
                    'slug' => $configuration->bundle->slug,
                ],
                'configuration_data' => $configuration->configuration_data,
                'total_price' => $configuration->total_price,
                'formatted_price' => $configuration->getFormattedPrice(),
                'total_weight_g' => $configuration->total_weight_g,
                'formatted_weight' => $configuration->getFormattedWeight(),
                'weight_compatibility' => $configuration->weight_compatibility,
                'compatibility_description' => $configuration->getWeightCompatibilityDescription(),
                'configuration_summary' => $configuration->getConfigurationSummary(),
                'sku' => $configuration->sku,
                'share_token' => $configuration->share_token,
                'created_at' => $configuration->created_at,
                'updated_at' => $configuration->updated_at,
            ]
        ]);
    }

    /**
     * Add configured bundle to cart
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function addToCart(Request $request, int $id): JsonResponse
    {
        $configuration = BundleConfiguration::where('id', $id)
                                           ->where('is_active', true)
                                           ->firstOrFail();

        $validated = $request->validate([
            'quantity' => 'integer|min:1|max:10',
        ]);

        $quantity = $validated['quantity'] ?? 1;

        try {
            // Here you would integrate with your cart system
            // For now, we'll return the cart data structure
            $cartItem = [
                'type' => 'bundle_configuration',
                'id' => $configuration->id,
                'name' => $configuration->name ?? 'Custom ' . $configuration->bundle->name,
                'sku' => $configuration->sku,
                'price' => $configuration->total_price,
                'weight_g' => $configuration->total_weight_g,
                'quantity' => $quantity,
                'line_total' => $configuration->total_price * $quantity,
                'configuration_data' => $configuration->configuration_data,
                'bundle_id' => $configuration->bundle_id,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Bundle configuration added to cart successfully',
                'data' => [
                    'cart_item' => $cartItem,
                    'cart_totals' => [
                        'subtotal' => $cartItem['line_total'],
                        'tax' => 0, // Calculate based on your tax logic
                        'shipping' => 0, // Calculate based on your shipping logic
                        'total' => $cartItem['line_total'],
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add bundle configuration to cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get configuration by share token
     *
     * @param string $shareToken
     * @return JsonResponse
     */
    public function getSharedConfiguration(string $shareToken): JsonResponse
    {
        $configuration = BundleConfiguration::with('bundle')
                                           ->where('share_token', $shareToken)
                                           ->where('is_active', true)
                                           ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $configuration->id,
                'name' => $configuration->name,
                'bundle' => [
                    'id' => $configuration->bundle->id,
                    'name' => $configuration->bundle->name,
                    'slug' => $configuration->bundle->slug,
                ],
                'configuration_data' => $configuration->configuration_data,
                'total_price' => $configuration->total_price,
                'formatted_price' => $configuration->getFormattedPrice(),
                'total_weight_g' => $configuration->total_weight_g,
                'formatted_weight' => $configuration->getFormattedWeight(),
                'weight_compatibility' => $configuration->weight_compatibility,
                'compatibility_description' => $configuration->getWeightCompatibilityDescription(),
                'configuration_summary' => $configuration->getConfigurationSummary(),
                'sku' => $configuration->sku,
            ]
        ]);
    }
}
