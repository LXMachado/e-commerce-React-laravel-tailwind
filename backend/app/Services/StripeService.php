<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a payment intent for the given cart
     */
    public function createPaymentIntent(Cart $cart, array $metadata = []): PaymentIntent
    {
        try {
            $amount = $this->calculateCartTotal($cart);

            $paymentIntentData = [
                'amount' => $amount, // Amount in cents
                'currency' => 'usd',
                'metadata' => array_merge([
                    'cart_id' => $cart->id,
                    'item_count' => $cart->item_count,
                ], $metadata),
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ];

            $paymentIntent = PaymentIntent::create($paymentIntentData);

            Log::info('Payment intent created', [
                'payment_intent_id' => $paymentIntent->id,
                'cart_id' => $cart->id,
                'amount' => $amount
            ]);

            return $paymentIntent;

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe payment intent creation failed', [
                'cart_id' => $cart->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Confirm a payment intent
     */
    public function confirmPaymentIntent(string $paymentIntentId): PaymentIntent
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            $paymentIntent->confirm();

            Log::info('Payment intent confirmed', [
                'payment_intent_id' => $paymentIntentId,
                'status' => $paymentIntent->status
            ]);

            return $paymentIntent;

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe payment intent confirmation failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle Stripe webhook
     */
    public function handleWebhook(string $payload, string $signature, string $webhookSecret): array
    {
        try {
            $event = Webhook::constructEvent($payload, $signature, $webhookSecret);

            Log::info('Stripe webhook received', [
                'event_type' => $event->type,
                'event_id' => $event->id
            ]);

            return [
                'event' => $event,
                'processed' => true
            ];

        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process payment success webhook
     */
    public function processPaymentSuccess(array $eventData): void
    {
        $paymentIntent = $eventData['object'];
        $cartId = $paymentIntent['metadata']['cart_id'] ?? null;

        if (!$cartId) {
            Log::error('Cart ID not found in payment intent metadata', [
                'payment_intent_id' => $paymentIntent['id']
            ]);
            return;
        }

        $cart = Cart::find($cartId);
        if (!$cart) {
            Log::error('Cart not found for payment processing', [
                'cart_id' => $cartId,
                'payment_intent_id' => $paymentIntent['id']
            ]);
            return;
        }

        // Create order from cart
        $this->createOrderFromCart($cart, $paymentIntent);

        Log::info('Payment success processed', [
            'cart_id' => $cartId,
            'payment_intent_id' => $paymentIntent['id']
        ]);
    }

    /**
     * Process payment failure webhook
     */
    public function processPaymentFailure(array $eventData): void
    {
        $paymentIntent = $eventData['object'];

        Log::warning('Payment failed', [
            'payment_intent_id' => $paymentIntent['id'],
            'last_payment_error' => $paymentIntent['last_payment_error'] ?? null
        ]);

        // Here you could update order status to failed
        // or send notification to customer
    }

    /**
     * Calculate total amount for cart in cents
     */
    private function calculateCartTotal(Cart $cart): int
    {
        $subtotal = $cart->subtotal;
        $taxAmount = 0; // Placeholder for tax calculation
        $shippingAmount = 0; // Placeholder for shipping calculation

        $total = $subtotal + $taxAmount + $shippingAmount;

        // Convert to cents for Stripe
        return (int) ($total * 100);
    }

    /**
     * Create order from successful cart payment
     */
    private function createOrderFromCart(Cart $cart, array $paymentIntent): Order
    {
        return DB::transaction(function () use ($cart, $paymentIntent) {
            // Create order
            $order = Order::create([
                'user_id' => $cart->user_id,
                'order_number' => Order::generateOrderNumber(),
                'status' => 'paid',
                'subtotal' => $cart->subtotal,
                'tax_amount' => 0, // Placeholder
                'shipping_amount' => 0, // Placeholder
                'total_amount' => $cart->subtotal,
                'currency' => 'USD',
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
                'payment_intent_id' => $paymentIntent['id'],
                'amount' => $paymentIntent['amount'] / 100, // Convert from cents
                'currency' => $paymentIntent['currency'],
                'status' => 'succeeded',
                'payment_method' => $paymentIntent['payment_method_types'][0] ?? null,
                'metadata' => $paymentIntent['metadata'] ?? [],
            ]);

            // Clear the cart
            $cart->items()->delete();

            return $order;
        });
    }

    /**
     * Get payment intent details
     */
    public function getPaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }

    /**
     * Cancel payment intent
     */
    public function cancelPaymentIntent(string $paymentIntentId): PaymentIntent
    {
        $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
        $paymentIntent->cancel();

        return $paymentIntent;
    }

    /**
     * Refund payment
     */
    public function refundPayment(string $paymentIntentId, int $amount = null): array
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            $refundData = [
                'payment_intent' => $paymentIntentId,
            ];

            if ($amount) {
                $refundData['amount'] = $amount; // Amount in cents
            }

            $refund = \Stripe\Refund::create($refundData);

            Log::info('Payment refunded', [
                'payment_intent_id' => $paymentIntentId,
                'refund_id' => $refund->id,
                'amount' => $amount
            ]);

            return [
                'refund' => $refund,
                'success' => true
            ];

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Payment refund failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}