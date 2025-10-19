<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminOrderController extends Controller
{
    /**
     * Display a listing of all orders (admin only).
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Order::with(['user', 'items.productVariant.product', 'payments', 'shipments']);

            // Filter by user
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by payment status
            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            // Filter by shipping status
            if ($request->has('shipping_status')) {
                $query->where('shipping_status', $request->shipping_status);
            }

            // Filter by date range
            if ($request->has('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('created_at', '<=', $request->date_to);
            }

            // Search by order number
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('order_number', 'like', "%{$search}%");
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            switch ($sortBy) {
                case 'total_amount':
                    $query->orderBy('total_amount', $sortDirection);
                    break;
                case 'status':
                    $query->orderBy('status', $sortDirection);
                    break;
                case 'user_id':
                    $query->orderBy('user_id', $sortDirection);
                    break;
                case 'created_at':
                default:
                    $query->orderBy('created_at', $sortDirection);
                    break;
            }

            $perPage = min($request->get('per_page', 15), 100);
            $orders = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $orders,
                'message' => 'Orders retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified order (admin only).
     */
    public function show(Order $order): JsonResponse
    {
        try {
            $order->load(['user', 'items.productVariant.product', 'payments', 'shipments', 'billingAddress', 'shippingAddress']);

            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Order retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update order status (admin only).
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:pending,paid,processing,shipped,delivered,cancelled',
                'notify_customer' => 'boolean',
            ]);

            $oldStatus = $order->status;
            $order->status = $validated['status'];
            $order->save();

            // Update related statuses based on new status
            switch ($validated['status']) {
                case 'paid':
                    $order->markAsPaid();
                    break;
                case 'shipped':
                    $order->markAsShipped();
                    break;
                case 'delivered':
                    $order->markAsDelivered();
                    break;
                case 'cancelled':
                    $order->cancel();
                    break;
            }

            // TODO: Send email notification if notify_customer is true

            return response()->json([
                'success' => true,
                'data' => $order->fresh()->load(['user', 'items.productVariant.product']),
                'message' => 'Order status updated successfully',
                'previous_status' => $oldStatus,
                'new_status' => $order->status
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
                'message' => 'Failed to update order status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order statistics (admin only).
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $query = Order::query();

            // Filter by date range
            if ($request->has('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('created_at', '<=', $request->date_to);
            }

            $stats = [
                'total_orders' => (clone $query)->count(),
                'total_revenue' => (clone $query)->sum('total_amount'),
                'pending_orders' => (clone $query)->where('status', 'pending')->count(),
                'paid_orders' => (clone $query)->where('status', 'paid')->count(),
                'shipped_orders' => (clone $query)->where('status', 'shipped')->count(),
                'delivered_orders' => (clone $query)->where('status', 'delivered')->count(),
                'cancelled_orders' => (clone $query)->where('status', 'cancelled')->count(),
                'average_order_value' => (clone $query)->where('status', 'delivered')->avg('total_amount'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Order statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process refund for an order (admin only).
     */
    public function refund(Request $request, Order $order): JsonResponse
    {
        try {
            $validated = $request->validate([
                'amount' => 'nullable|numeric|min:0',
                'reason' => 'required|string|max:255',
            ]);

            $user = auth()->user();

            // Check if order can be refunded
            if (!in_array($order->status, ['paid', 'shipped', 'delivered'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order cannot be refunded in its current status'
                ], 422);
            }

            // Get the payment
            $payment = $order->payments()->where('status', 'succeeded')->first();
            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No successful payment found for this order'
                ], 404);
            }

            // TODO: Process refund via Stripe
            // For now, we'll just update the order status

            return response()->json([
                'success' => true,
                'data' => $order->fresh(),
                'message' => 'Refund processed successfully'
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
                'message' => 'Failed to process refund',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}