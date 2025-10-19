<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BundleConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class BundleConfigurationController extends Controller
{
    /**
     * Get user's bundle configurations
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = BundleConfiguration::with('bundle')
                                   ->where(function ($q) {
                                       $q->where('user_id', Auth::id())
                                         ->orWhereNull('user_id');
                                   })
                                   ->where('is_active', true);

        // Filter by bundle if specified
        if ($request->has('bundle_id')) {
            $query->where('bundle_id', $request->bundle_id);
        }

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $configurations = $query->orderBy('updated_at', 'desc')
                               ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $configurations->map(function ($configuration) {
                return [
                    'id' => $configuration->id,
                    'name' => $configuration->name,
                    'bundle' => [
                        'id' => $configuration->bundle->id,
                        'name' => $configuration->bundle->name,
                        'slug' => $configuration->bundle->slug,
                    ],
                    'sku' => $configuration->sku,
                    'total_price' => $configuration->total_price,
                    'formatted_price' => $configuration->getFormattedPrice(),
                    'total_weight_g' => $configuration->total_weight_g,
                    'formatted_weight' => $configuration->getFormattedWeight(),
                    'weight_compatibility' => $configuration->weight_compatibility,
                    'compatibility_description' => $configuration->getWeightCompatibilityDescription(),
                    'share_token' => $configuration->share_token,
                    'created_at' => $configuration->created_at,
                    'updated_at' => $configuration->updated_at,
                ];
            }),
            'pagination' => [
                'current_page' => $configurations->currentPage(),
                'per_page' => $configurations->perPage(),
                'total' => $configurations->total(),
                'last_page' => $configurations->lastPage(),
                'from' => $configurations->firstItem(),
                'to' => $configurations->lastItem(),
            ]
        ]);
    }

    /**
     * Update bundle configuration
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $configuration = BundleConfiguration::where('id', $id)
                                           ->where(function ($q) {
                                               $q->where('user_id', Auth::id())
                                                 ->orWhereNull('user_id');
                                           })
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
            $configuration->update([
                'configuration_data' => array_merge(
                    $configuration->configuration_data,
                    $validated['configuration']
                ),
                'name' => $validated['name'] ?? $configuration->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bundle configuration updated successfully',
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
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update bundle configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete bundle configuration
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $configuration = BundleConfiguration::where('id', $id)
                                           ->where(function ($q) {
                                               $q->where('user_id', Auth::id())
                                                 ->orWhereNull('user_id');
                                           })
                                           ->where('is_active', true)
                                           ->firstOrFail();

        try {
            $configuration->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Bundle configuration deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete bundle configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicate bundle configuration
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function duplicate(Request $request, int $id): JsonResponse
    {
        $originalConfiguration = BundleConfiguration::where('id', $id)
                                                   ->where(function ($q) {
                                                       $q->where('user_id', Auth::id())
                                                         ->orWhereNull('user_id');
                                                   })
                                                   ->where('is_active', true)
                                                   ->firstOrFail();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
        ]);

        try {
            $newConfiguration = $originalConfiguration->bundle->createConfiguration(
                $originalConfiguration->configuration_data,
                Auth::id(),
                $validated['name'] ?? 'Copy of ' . ($originalConfiguration->name ?? 'Configuration')
            );

            return response()->json([
                'success' => true,
                'message' => 'Bundle configuration duplicated successfully',
                'data' => [
                    'id' => $newConfiguration->id,
                    'name' => $newConfiguration->name,
                    'sku' => $newConfiguration->sku,
                    'total_price' => $newConfiguration->total_price,
                    'formatted_price' => $newConfiguration->getFormattedPrice(),
                    'total_weight_g' => $newConfiguration->total_weight_g,
                    'formatted_weight' => $newConfiguration->getFormattedWeight(),
                    'weight_compatibility' => $newConfiguration->weight_compatibility,
                    'compatibility_description' => $newConfiguration->getWeightCompatibilityDescription(),
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate bundle configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get configuration statistics for user
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        $stats = BundleConfiguration::where(function ($q) {
                                     $q->where('user_id', Auth::id())
                                       ->orWhereNull('user_id');
                                   })
                                   ->where('is_active', true)
                                   ->selectRaw('
                                       COUNT(*) as total_configurations,
                                       AVG(total_price) as avg_price,
                                       AVG(total_weight_g) as avg_weight,
                                       SUM(CASE WHEN total_weight_g < 5000 THEN 1 ELSE 0 END) as day_pack_compatible,
                                       SUM(CASE WHEN total_weight_g BETWEEN 5000 AND 10000 THEN 1 ELSE 0 END) as overnight_pack_compatible,
                                       SUM(CASE WHEN total_weight_g > 10000 THEN 1 ELSE 0 END) as base_camp_setups
                                   ')
                                   ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'total_configurations' => (int) $stats->total_configurations,
                'average_price' => round((float) $stats->avg_price, 2),
                'average_weight' => round((float) $stats->avg_weight / 1000, 2),
                'compatibility_breakdown' => [
                    'day_pack_compatible' => (int) $stats->day_pack_compatible,
                    'overnight_pack_compatible' => (int) $stats->overnight_pack_compatible,
                    'base_camp_setups' => (int) $stats->base_camp_setups,
                ]
            ]
        ]);
    }
}
