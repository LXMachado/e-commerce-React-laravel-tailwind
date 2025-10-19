import React, { useEffect, useState } from 'react'
import { useSearchParams, useNavigate } from 'react-router-dom'
import api from '../services/api'

interface OrderData {
  id: number
  order_number: string
  total_amount: number
  status: string
  created_at: string
  items: Array<{
    id: number
    quantity: number
    price_at_time: number
    product: {
      name: string
      slug: string
    }
  }>
}

const PaymentSuccess: React.FC = () => {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const [orderData, setOrderData] = useState<OrderData | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string>('')

  const paymentIntentId = searchParams.get('payment_intent')
  const orderId = searchParams.get('order_id')

  useEffect(() => {
    if (paymentIntentId) {
      // Poll for order creation via webhook
      pollForOrder()
    } else if (orderId) {
      // Load order data directly
      loadOrderData(orderId)
    } else {
      setError('Payment information not found')
      setLoading(false)
    }
  }, [paymentIntentId, orderId])

  const pollForOrder = async () => {
    let attempts = 0
    const maxAttempts = 10

    const poll = async () => {
      try {
        // Try to find order by payment intent ID
        // This would typically call an API endpoint like /api/orders/by-payment-intent/{paymentIntentId}
        // For now, we'll poll the payment status and assume order creation
        const response = await api.get(`/checkout/payment-status/${paymentIntentId}`)

        if (response.data.success) {
          const paymentStatus = response.data.data

          if (paymentStatus.status === 'succeeded') {
            // Payment succeeded, try to get order details
            // Since we don't have a specific order lookup endpoint yet,
            // we'll show a generic success message
            setOrderData({
              id: 1,
              order_number: 'ORD-PENDING',
              total_amount: paymentStatus.amount / 100, // Convert from cents
              status: 'paid',
              created_at: new Date().toISOString(),
              items: [
                {
                  id: 1,
                  quantity: 1,
                  price_at_time: paymentStatus.amount / 100,
                  product: {
                    name: 'Order Processed',
                    slug: 'order-processed'
                  }
                }
              ]
            })
            setLoading(false)
          } else if (attempts >= maxAttempts) {
            setError('Order confirmation timeout. Please check your email for order details.')
            setLoading(false)
          } else {
            attempts++
            setTimeout(poll, 2000) // Poll every 2 seconds
          }
        } else {
          attempts++
          if (attempts >= maxAttempts) {
            setError('Failed to confirm payment status')
            setLoading(false)
          } else {
            setTimeout(poll, 2000)
          }
        }
      } catch (err: any) {
        attempts++
        if (attempts >= maxAttempts) {
          setError('Failed to retrieve order information')
          setLoading(false)
        } else {
          setTimeout(poll, 2000)
        }
      }
    }

    poll()
  }

  const loadOrderData = async (orderId: string) => {
    try {
      // This would call the orders API endpoint: /api/orders/{orderId}
      // For now, we'll show a message that the order is being processed
      setOrderData({
        id: parseInt(orderId),
        order_number: 'ORD-PENDING',
        total_amount: 0,
        status: 'processing',
        created_at: new Date().toISOString(),
        items: [
          {
            id: 1,
            quantity: 1,
            price_at_time: 0,
            product: {
              name: 'Order Being Processed',
              slug: 'order-processing'
            }
          }
        ]
      })
    } catch (err: any) {
      setError('Failed to load order information')
    } finally {
      setLoading(false)
    }
  }

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div className="max-w-md w-full space-y-8">
          <div className="text-center">
            <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
              <svg className="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
              </svg>
            </div>
            <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
              Confirming Your Order...
            </h2>
            <p className="mt-2 text-sm text-gray-600">
              Please wait while we finalize your order details.
            </p>
          </div>
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div className="max-w-md w-full space-y-8">
          <div className="text-center">
            <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
              <svg className="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
            </div>
            <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
              Order Confirmation Error
            </h2>
            <p className="mt-2 text-sm text-gray-600">
              {error}
            </p>
            <div className="mt-6 space-y-3">
              <button
                onClick={() => navigate('/checkout')}
                className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
              >
                Return to Checkout
              </button>
              <button
                onClick={() => navigate('/support')}
                className="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
              >
                Contact Support
              </button>
            </div>
          </div>
        </div>
      </div>
    )
  }

  if (!orderData) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div className="max-w-md w-full space-y-8">
          <div className="text-center">
            <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
              Order Not Found
            </h2>
            <p className="mt-2 text-sm text-gray-600">
              We couldn't find your order information.
            </p>
            <div className="mt-6">
              <button
                onClick={() => navigate('/')}
                className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
              >
                Continue Shopping
              </button>
            </div>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-3xl mx-auto">
        <div className="bg-white shadow-lg rounded-lg overflow-hidden">
          {/* Success Header */}
          <div className="px-6 py-4 bg-green-50 border-b border-green-200">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <svg className="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div className="ml-3">
                <h2 className="text-lg font-medium text-green-800">
                  Payment Successful!
                </h2>
                <p className="text-sm text-green-700">
                  Thank you for your order. You will receive a confirmation email shortly.
                </p>
              </div>
            </div>
          </div>

          <div className="px-6 py-6">
            {/* Order Information */}
            <div className="mb-8">
              <h3 className="text-lg font-medium text-gray-900 mb-4">Order Details</h3>
              <div className="bg-gray-50 rounded-lg p-4 space-y-3">
                <div className="flex justify-between">
                  <span className="text-sm font-medium text-gray-600">Order Number:</span>
                  <span className="text-sm text-gray-900 font-mono">{orderData.order_number}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-sm font-medium text-gray-600">Order Date:</span>
                  <span className="text-sm text-gray-900">
                    {new Date(orderData.created_at).toLocaleDateString()}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-sm font-medium text-gray-600">Status:</span>
                  <span className="text-sm text-green-600 capitalize">{orderData.status}</span>
                </div>
                <div className="flex justify-between text-lg font-bold pt-2 border-t border-gray-200">
                  <span>Total:</span>
                  <span>${orderData.total_amount.toFixed(2)}</span>
                </div>
              </div>
            </div>

            {/* Order Items */}
            <div className="mb-8">
              <h3 className="text-lg font-medium text-gray-900 mb-4">Items Ordered</h3>
              <div className="space-y-4">
                {orderData.items.map((item) => (
                  <div key={item.id} className="flex items-center space-x-4 py-4 border-b border-gray-100">
                    <div className="flex-1">
                      <h4 className="text-sm font-medium text-gray-900">
                        {item.product.name}
                      </h4>
                      <p className="text-sm text-gray-500">
                        Quantity: {item.quantity}
                      </p>
                    </div>
                    <div className="text-right">
                      <p className="text-sm font-medium text-gray-900">
                        ${(item.price_at_time * item.quantity).toFixed(2)}
                      </p>
                      <p className="text-xs text-gray-500">
                        ${item.price_at_time.toFixed(2)} each
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* What's Next */}
            <div className="bg-blue-50 rounded-lg p-4 mb-8">
              <h3 className="text-lg font-medium text-blue-900 mb-2">What's Next?</h3>
              <ul className="text-sm text-blue-800 space-y-1">
                <li>• You'll receive an order confirmation email within the next few minutes</li>
                <li>• We'll send shipping updates as your order is processed</li>
                <li>• Track your order status in your account dashboard</li>
                <li>• Contact support if you have any questions</li>
              </ul>
            </div>

            {/* Action Buttons */}
            <div className="flex flex-col sm:flex-row gap-4">
              <button
                onClick={() => navigate('/products')}
                className="flex-1 flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
              >
                Continue Shopping
              </button>
              <button
                onClick={() => navigate('/orders')}
                className="flex-1 flex justify-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
              >
                View Order History
              </button>
            </div>

            {/* Support Information */}
            <div className="mt-8 pt-6 border-t border-gray-200">
              <div className="text-center text-sm text-gray-500">
                <p>Questions about your order?</p>
                <button
                  onClick={() => navigate('/support')}
                  className="text-green-600 hover:text-green-500 font-medium"
                >
                  Contact Support
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default PaymentSuccess