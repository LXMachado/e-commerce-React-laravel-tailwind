<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Services\StripeService;
use App\Services\ShippingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    private StripeService $stripeService;
    private ShippingService $shippingService;

    public function __construct(StripeService $stripeService, ShippingService $shippingService)
    {
        $this->stripeService = $stripeService;
        $this->shippingService = $shippingService;
    }

    /**
     * Initiate checkout process
     */
    public function initiate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'cart_id' => 'nullable|exists:carts,id',
                'success_url' => 'nullable|url',
                'cancel_url' => 'nullable|url',
            ]);

            // Get or create cart for current user/session
            $cart = $this->getCartForCheckout($request, $validated['cart_id'] ?? null);

            if (!$cart || $cart->items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty or not found'
                ], 404);
            }

            // Validate cart items are still available
            foreach ($cart->items as $item) {
                if (!$item->isValid()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Some items in cart are no longer available',
                        'invalid_items' => [$item->id]
                    ], 422);
                }

                if (!$item->productVariant->hasStock($item->quantity)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient stock for item: ' . $item->productVariant->product->name,
                        'insufficient_stock_items' => [
                            'id' => $item->id,
                            'product' => $item->productVariant->product->name,
                            'requested' => $item->quantity,
                            'available' => $item->productVariant->stock_quantity
                        ]
                    ], 422);
                }
            }

            // Create payment intent
            $metadata = [
                'cart_id' => $cart->id,
                'user_id' => $cart->user_id,
                'item_count' => $cart->item_count,
            ];

            if (isset($validated['success_url'])) {
                $metadata['success_url'] = $validated['success_url'];
            }

            if (isset($validated['cancel_url'])) {
                $metadata['cancel_url'] = $validated['cancel_url'];
            }

            $paymentIntent = $this->stripeService->createPaymentIntent($cart, $metadata);

            // Update cart with payment intent ID
            $cart->update(['payment_intent_id' => $paymentIntent->id]);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_intent_id' => $paymentIntent->id,
                    'client_secret' => $paymentIntent->client_secret,
                    'amount' => $paymentIntent->amount,
                    'currency' => $paymentIntent->currency,
                    'cart_id' => $cart->id,
                    'item_count' => $cart->item_count,
                    'subtotal' => $cart->subtotal,
                ],
                'message' => 'Checkout initiated successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe checkout initiation failed', [
                'error' => $e->getMessage(),
                'cart_id' => $validated['cart_id'] ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment service unavailable',
                'error' => 'Payment processing failed'
            ], 503);

        } catch (\Exception $e) {
            Log::error('Checkout initiation failed', [
                'error' => $e->getMessage(),
                'cart_id' => $validated['cart_id'] ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate checkout',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process payment confirmation
     */
    public function process(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'payment_intent_id' => 'required|string',
                'cart_id' => 'nullable|exists:carts,id',
            ]);

            $paymentIntentId = $validated['payment_intent_id'];
            $cartId = $validated['cart_id'];

            // Get cart if provided
            $cart = null;
            if ($cartId) {
                $cart = Cart::find($cartId);
            }

            // Confirm payment intent
            $paymentIntent = $this->stripeService->confirmPaymentIntent($paymentIntentId);

            if ($paymentIntent->status !== 'succeeded') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not completed',
                    'payment_status' => $paymentIntent->status
                ], 402);
            }

            // Process successful payment
            $order = null;
            if ($cart) {
                // Get shipping information from request if provided
                $shippingPostcode = $request->input('shipping_postcode');
                $shippingMethodCode = $request->input('shipping_method_code');

                $order = $this->processSuccessfulPayment($cart, $paymentIntent, $shippingPostcode, $shippingMethodCode);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_intent_id' => $paymentIntent->id,
                    'payment_status' => $paymentIntent->status,
                    'order_id' => $order?->id,
                    'order_number' => $order?->order_number,
                ],
                'message' => 'Payment processed successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Stripe\Exception\CardException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment declined',
                'error' => $e->getError()->message
            ], 402);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe payment processing failed', [
                'payment_intent_id' => $validated['payment_intent_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed',
                'error' => 'Payment service error'
            ], 503);

        } catch (\Exception $e) {
            Log::error('Payment processing failed', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $validated['payment_intent_id'] ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle Stripe webhooks
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            $payload = $request->getContent();
            $signature = $request->header('Stripe-Signature');
            $webhookSecret = config('services.stripe.webhook_secret');

            if (!$signature) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing Stripe signature'
                ], 400);
            }

            $result = $this->stripeService->handleWebhook($payload, $signature, $webhookSecret);

            switch ($result['event']->type) {
                case 'payment_intent.succeeded':
                    $this->stripeService->processPaymentSuccess($result['event']->data);
                    break;

                case 'payment_intent.payment_failed':
                    $this->stripeService->processPaymentFailure($result['event']->data);
                    break;

                default:
                    Log::info('Unhandled webhook event', [
                        'event_type' => $result['event']->type
                    ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully'
            ]);

        } catch (\UnexpectedValueException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook payload'
            ], 400);

        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook signature'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed'
            ], 500);
        }
    }

    /**
     * Get payment intent status
     */
    public function paymentStatus(Request $request, string $paymentIntentId): JsonResponse
    {
        try {
            $paymentIntent = $this->stripeService->getPaymentIntent($paymentIntentId);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_intent_id' => $paymentIntent->id,
                    'status' => $paymentIntent->status,
                    'amount' => $paymentIntent->amount,
                    'currency' => $paymentIntent->currency,
                    'last_payment_error' => $paymentIntent->last_payment_error,
                ],
                'message' => 'Payment status retrieved successfully'
            ]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment status',
                'error' => 'Payment not found'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get or create cart for checkout
     */
    private function getCartForCheckout(Request $request, ?int $cartId = null): ?Cart
    {
        if ($cartId) {
            $cart = Cart::find($cartId);
            if ($cart && $cart->is_active) {
                return $cart;
            }
        }

        // Get cart for current user/session
        $user = auth()->user();
        if ($user) {
            return Cart::findOrCreateForUser($user);
        }

        $sessionId = $request->session()->getId();
        return Cart::findOrCreateForUser(null, $sessionId);
    }

    /**
     * Process successful payment and create order
     */
    private function processSuccessfulPayment(Cart $cart, $paymentIntent, ?string $shippingPostcode = null, ?string $shippingMethodCode = null): Order
    {
        return DB::transaction(function () use ($cart, $paymentIntent, $shippingPostcode, $shippingMethodCode) {
            // Calculate shipping if postcode provided
            $shippingAmount = 0;
            $shippingData = null;

            if ($shippingPostcode) {
                $shippingQuote = $this->shippingService->calculateShippingCost(
                    $shippingPostcode,
                    $cart->total_weight,
                    $shippingMethodCode
                );

                if ($shippingQuote['success'] && !empty($shippingQuote['quotes'])) {
                    // Use the first (cheapest) option by default, or specific method if provided
                    $selectedQuote = null;

                    if ($shippingMethodCode) {
                        $selectedQuote = collect($shippingQuote['quotes'])
                            ->firstWhere('method.code', $shippingMethodCode);
                    }

                    if (!$selectedQuote) {
                        $selectedQuote = collect($shippingQuote['quotes'])->first();
                    }

                    if ($selectedQuote) {
                        $shippingAmount = $selectedQuote['rate']['price'];
                        $shippingData = $selectedQuote;
                    }
                }
            }

            // Create order
            $order = Order::create([
                'user_id' => $cart->user_id,
                'order_number' => Order::generateOrderNumber(),
                'status' => 'paid',
                'subtotal' => $cart->subtotal,
                'tax_amount' => 0, // Placeholder
                'shipping_amount' => $shippingAmount,
                'total_amount' => $cart->subtotal + $shippingAmount,
                'currency' => 'AUD', // Changed to AUD for Australian shipping
                'payment_status' => 'paid',
                'shipping_status' => 'pending',
            ]);

            // Create order items from cart items
            foreach ($cart->items as $cartItem) {
                $order->items()->create([
                    'product_variant_id' => $cartItem->product_variant_id,
                    'quantity' => $cartItem->quantity,
                    'price_at_time' => $cartItem->price_at_time,
                    'line_total' => $cartItem->quantity * $cartItem->price_at_time,
                ]);

                // Decrement stock
                $cartItem->productVariant->decrement('stock_quantity', $cartItem->quantity);
            }

            // Create payment record
            $order->payments()->create([
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount / 100, // Convert from cents
                'currency' => $paymentIntent->currency,
                'status' => 'succeeded',
                'payment_method' => $paymentIntent->payment_method_types[0] ?? null,
                'metadata' => $paymentIntent->metadata ?? [],
            ]);

            // Clear the cart
            $cart->items()->delete();

            return $order;
        });
    }
}