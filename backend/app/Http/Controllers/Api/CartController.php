<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    /**
     * Display the current user's cart.
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $cart = $this->getOrCreateCart($request);

            if (!$cart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart not found'
                ], 404);
            }

            $cart->load(['items.productVariant.product']);

            return response()->json([
                'success' => true,
                'data' => $cart,
                'message' => 'Cart retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add item to cart.
     */
    public function addItem(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'product_variant_id' => 'required|exists:product_variants,id',
                'quantity' => 'required|integer|min:1|max:100',
            ]);

            $variant = ProductVariant::where('id', $validated['product_variant_id'])
                                   ->where('is_active', true)
                                   ->first();

            if (!$variant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product variant not found or inactive'
                ], 404);
            }

            if (!$variant->hasStock($validated['quantity'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock available',
                    'available_stock' => $variant->stock_quantity
                ], 422);
            }

            $cart = $this->getOrCreateCart($request);

            // Check if item already exists in cart
            $existingItem = $cart->items()->where('product_variant_id', $variant->id)->first();

            if ($existingItem) {
                $newQuantity = $existingItem->quantity + $validated['quantity'];

                if (!$variant->hasStock($newQuantity)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient stock for requested quantity',
                        'available_stock' => $variant->stock_quantity,
                        'requested_quantity' => $newQuantity
                    ], 422);
                }

                $existingItem->update([
                    'quantity' => $newQuantity,
                    'price_at_time' => $variant->getCurrentPrice()
                ]);
            } else {
                $cart->items()->create([
                    'product_variant_id' => $variant->id,
                    'quantity' => $validated['quantity'],
                    'price_at_time' => $variant->getCurrentPrice()
                ]);
            }

            $cart->load(['items.productVariant.product']);

            return response()->json([
                'success' => true,
                'data' => $cart,
                'message' => 'Item added to cart successfully'
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
                'message' => 'Failed to add item to cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update cart item quantity.
     */
    public function updateItem(Request $request, $itemId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'quantity' => 'required|integer|min:1|max:100',
            ]);

            $cart = $this->getOrCreateCart($request);
            $item = $cart->items()->find($itemId);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart item not found'
                ], 404);
            }

            if (!$item->productVariant->hasStock($validated['quantity'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock available',
                    'available_stock' => $item->productVariant->stock_quantity
                ], 422);
            }

            $item->update([
                'quantity' => $validated['quantity'],
                'price_at_time' => $item->productVariant->getCurrentPrice()
            ]);

            $cart->load(['items.productVariant.product']);

            return response()->json([
                'success' => true,
                'data' => $cart,
                'message' => 'Cart item updated successfully'
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
                'message' => 'Failed to update cart item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove item from cart.
     */
    public function removeItem(Request $request, $itemId): JsonResponse
    {
        try {
            $cart = $this->getOrCreateCart($request);
            $item = $cart->items()->find($itemId);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart item not found'
                ], 404);
            }

            $item->delete();

            $cart->load(['items.productVariant.product']);

            return response()->json([
                'success' => true,
                'data' => $cart,
                'message' => 'Item removed from cart successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item from cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all items from cart.
     */
    public function clear(Request $request): JsonResponse
    {
        try {
            $cart = $this->getOrCreateCart($request);

            if (!$cart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart not found'
                ], 404);
            }

            $cart->items()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cart cleared successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cart totals and summary.
     */
    public function totals(Request $request): JsonResponse
    {
        try {
            $cart = $this->getOrCreateCart($request);

            if (!$cart || $cart->items->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'item_count' => 0,
                        'subtotal' => 0,
                        'tax_amount' => 0,
                        'shipping_amount' => 0,
                        'total_amount' => 0,
                        'currency' => 'USD'
                    ],
                    'message' => 'Cart is empty'
                ]);
            }

            $subtotal = $cart->subtotal;
            $taxAmount = 0; // Placeholder for tax calculation
            $shippingAmount = 0; // Placeholder for shipping calculation
            $totalAmount = $subtotal + $taxAmount + $shippingAmount;

            return response()->json([
                'success' => true,
                'data' => [
                    'item_count' => $cart->item_count,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'shipping_amount' => $shippingAmount,
                    'total_amount' => $totalAmount,
                    'currency' => 'USD',
                    'cart_id' => $cart->id
                ],
                'message' => 'Cart totals retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cart totals',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Merge guest cart with user cart on login.
     */
    public function mergeGuestCart(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'guest_cart_id' => 'required|exists:carts,id',
            ]);

            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $guestCart = Cart::find($validated['guest_cart_id']);
            if (!$guestCart || !$guestCart->items()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Guest cart not found or empty'
                ], 404);
            }

            $userCart = Cart::findOrCreateForUser($user);

            // Merge guest cart items into user cart
            $userCart->mergeGuestCart($guestCart);

            $userCart->load(['items.productVariant.product']);

            return response()->json([
                'success' => true,
                'data' => $userCart,
                'message' => 'Guest cart merged successfully'
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
                'message' => 'Failed to merge guest cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get or create cart for current user/session.
     */
    private function getOrCreateCart(Request $request): ?Cart
    {
        $user = auth()->user();

        if ($user) {
            return Cart::findOrCreateForUser($user);
        }

        // For guest users, use session ID
        $sessionId = $request->session()->getId();
        return Cart::findOrCreateForUser(null, $sessionId);
    }
}