<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    /**
     * Display a listing of the user's orders.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $query = Order::where('user_id', $user->id)
                         ->with(['items.productVariant.product', 'payments', 'shipments']);

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
                case 'created_at':
                default:
                    $query->orderBy('created_at', $sortDirection);
                    break;
            }

            $perPage = min($request->get('per_page', 15), 50);
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
     * Display the specified order.
     */
    public function show(Order $order): JsonResponse
    {
        try {
            $user = auth()->user();

            // Ensure user can only view their own orders
            if ($order->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            $order->load(['items.productVariant.product', 'payments', 'shipments', 'billingAddress', 'shippingAddress']);

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
     * Cancel an order.
     */
    public function cancel(Order $order): JsonResponse
    {
        try {
            $user = auth()->user();

            // Ensure user can only cancel their own orders
            if ($order->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Check if order can be cancelled
            if (!in_array($order->status, ['pending', 'paid'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order cannot be cancelled in its current status'
                ], 422);
            }

            $order->cancel();

            return response()->json([
                'success' => true,
                'data' => $order->fresh(),
                'message' => 'Order cancelled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order statistics for the authenticated user.
     */
    public function stats(): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $stats = [
                'total_orders' => Order::where('user_id', $user->id)->count(),
                'total_spent' => Order::where('user_id', $user->id)->sum('total_amount'),
                'pending_orders' => Order::where('user_id', $user->id)->where('status', 'pending')->count(),
                'completed_orders' => Order::where('user_id', $user->id)->where('status', 'delivered')->count(),
                'cancelled_orders' => Order::where('user_id', $user->id)->where('status', 'cancelled')->count(),
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
}