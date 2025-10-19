<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ShippingService;
use App\Models\Cart;
use App\Http\Requests\ShippingQuoteRequest;
use App\Http\Requests\ValidateAddressRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ShippingController extends Controller
{
    protected ShippingService $shippingService;

    public function __construct(ShippingService $shippingService)
    {
        $this->shippingService = $shippingService;
    }

    /**
     * Calculate shipping cost for cart/order.
     * POST /api/shipping/quote
     */
    public function quote(ShippingQuoteRequest $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'postcode' => 'required|string|size:4|regex:/^\d{4}$/',
            'weight' => 'nullable|numeric|min:0',
            'cart_id' => 'nullable|exists:carts,id',
            'method_code' => 'nullable|string|exists:shipping_methods,code',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $postcode = $request->input('postcode');
            $methodCode = $request->input('method_code');

            // Calculate weight from cart if cart_id provided
            if ($request->filled('cart_id')) {
                $cart = Cart::find($request->input('cart_id'));
                if (!$cart) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Cart not found',
                    ], 404);
                }
                $weight = $this->shippingService->calculateCartWeight($cart);
            } else {
                $weight = $request->input('weight', 0);
            }

            $result = $this->shippingService->calculateShippingCost($postcode, $weight, $methodCode);

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to calculate shipping cost: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List available shipping zones.
     * GET /api/shipping/zones
     */
    public function zones(): JsonResponse
    {
        try {
            $zones = $this->shippingService->getShippingZones();

            return response()->json([
                'success' => true,
                'zones' => $zones,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve shipping zones: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List available shipping methods.
     * GET /api/shipping/methods
     */
    public function methods(): JsonResponse
    {
        try {
            $methods = $this->shippingService->getShippingMethods();

            return response()->json([
                'success' => true,
                'methods' => $methods,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve shipping methods: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate Australian address format.
     * POST /api/shipping/validate-address
     */
    public function validateAddress(ValidateAddressRequest $request): JsonResponse
    {

        try {
            $address = $request->only([
                'address_line_1',
                'address_line_2',
                'suburb',
                'state',
                'postcode',
                'country'
            ]);

            $validation = $this->shippingService->validateAustralianAddress($address);

            return response()->json($validation);

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'errors' => ['Failed to validate address: ' . $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Get weight tiers for reference.
     * GET /api/shipping/weight-tiers
     */
    public function weightTiers(): JsonResponse
    {
        try {
            $tiers = $this->shippingService->getWeightTiers();

            return response()->json([
                'success' => true,
                'tiers' => $tiers,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve weight tiers: ' . $e->getMessage(),
            ], 500);
        }
    }
}
