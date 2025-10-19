import React, { useState, useEffect } from 'react'
import { Elements } from '@stripe/react-stripe-js'
import stripePromise from '../services/stripe'
import PaymentForm from './PaymentForm'
import { usePayment, PaymentIntent } from '../hooks/usePayment'

interface CartItem {
  id: number
  product_variant_id: number
  quantity: number
  price_at_time: number
  productVariant: {
    id: number
    sku: string
    price: number
    product: {
      id: number
      name: string
      slug: string
    }
  }
}

interface Cart {
  id: number
  items: CartItem[]
  subtotal: number
  item_count: number
}

const CheckoutPage: React.FC = () => {
  const [cart, setCart] = useState<Cart | null>(null)
  const [paymentIntent, setPaymentIntent] = useState<PaymentIntent | null>(null)
  const [currentStep, setCurrentStep] = useState<'cart' | 'payment' | 'processing' | 'success' | 'error'>('cart')
  const [error, setError] = useState<string>('')

  const { loading, initiatePayment, error: paymentError, clearError } = usePayment()

  // Load cart data
  useEffect(() => {
    loadCart()
  }, [])

  const loadCart = async () => {
    try {
      const response = await fetch('/api/cart')
      const data = await response.json()

      if (data.success) {
        setCart(data.data)
      } else {
        setError('Failed to load cart')
        setCurrentStep('error')
      }
    } catch (err: any) {
      setError('Failed to load cart')
      setCurrentStep('error')
    }
  }

  const handleInitiatePayment = async () => {
    if (!cart) return

    setCurrentStep('payment')
    clearError()

    const intent = await initiatePayment(cart.id)

    if (intent) {
      setPaymentIntent(intent)
    } else {
      setError(paymentError?.message || 'Failed to initiate payment')
      setCurrentStep('error')
    }
  }

  const handlePaymentSuccess = (paymentIntentId: string) => {
    setCurrentStep('processing')
    // The webhook will handle the actual order creation
    // We'll redirect to success page after a brief delay
    setTimeout(() => {
      setCurrentStep('success')
    }, 2000)
  }

  const handlePaymentError = (errorMessage: string) => {
    setError(errorMessage)
    setCurrentStep('error')
  }

  const handleRetry = () => {
    setError('')
    setCurrentStep('cart')
  }

  if (currentStep === 'error') {
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
              Checkout Error
            </h2>
            <p className="mt-2 text-sm text-gray-600">
              {error || 'Something went wrong with your checkout'}
            </p>
            <div className="mt-6">
              <button
                onClick={handleRetry}
                className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
              >
                Try Again
              </button>
            </div>
          </div>
        </div>
      </div>
    )
  }

  if (currentStep === 'success') {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div className="max-w-md w-full space-y-8">
          <div className="text-center">
            <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
              <svg className="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
              Payment Successful!
            </h2>
            <p className="mt-2 text-sm text-gray-600">
              Thank you for your order. You will receive a confirmation email shortly.
            </p>
            <div className="mt-6 space-y-3">
              <button
                onClick={() => window.location.href = '/'}
                className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
              >
                Continue Shopping
              </button>
              <button
                onClick={() => window.location.href = '/orders'}
                className="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
              >
                View Orders
              </button>
            </div>
          </div>
        </div>
      </div>
    )
  }

  if (currentStep === 'processing') {
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
              Processing Payment...
            </h2>
            <p className="mt-2 text-sm text-gray-600">
              Please wait while we confirm your payment.
            </p>
          </div>
        </div>
      </div>
    )
  }

  if (!cart || cart.items.length === 0) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div className="max-w-md w-full space-y-8">
          <div className="text-center">
            <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
              Your cart is empty
            </h2>
            <p className="mt-2 text-sm text-gray-600">
              Add some items to your cart before checking out.
            </p>
            <div className="mt-6">
              <button
                onClick={() => window.location.href = '/products'}
                className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
              >
                Browse Products
              </button>
            </div>
          </div>
        </div>
      </div>
    )
  }

  if (currentStep === 'payment' && paymentIntent) {
    return (
      <div className="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div className="max-w-3xl mx-auto">
          <div className="bg-white shadow-lg rounded-lg overflow-hidden">
            <div className="px-6 py-4 border-b border-gray-200">
              <h2 className="text-2xl font-bold text-gray-900">Complete Payment</h2>
              <p className="mt-1 text-sm text-gray-600">
                Review your order and complete your payment securely
              </p>
            </div>

            <div className="p-6">
              <Elements
                stripe={stripePromise}
                options={{
                  clientSecret: paymentIntent.client_secret,
                  appearance: {
                    theme: 'stripe',
                    variables: {
                      colorPrimary: '#10b981',
                    },
                  },
                }}
              >
                <PaymentForm
                  paymentIntentId={paymentIntent.payment_intent_id}
                  clientSecret={paymentIntent.client_secret}
                  onPaymentSuccess={handlePaymentSuccess}
                  onPaymentError={handlePaymentError}
                />
              </Elements>
            </div>
          </div>
        </div>
      </div>
    )
  }

  // Cart review step
  return (
    <div className="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-4xl mx-auto">
        <div className="bg-white shadow-lg rounded-lg overflow-hidden">
          <div className="px-6 py-4 border-b border-gray-200">
            <h2 className="text-2xl font-bold text-gray-900">Checkout</h2>
            <p className="mt-1 text-sm text-gray-600">
              Review your items and proceed to payment
            </p>
          </div>

          <div className="p-6">
            {/* Cart Items */}
            <div className="space-y-4 mb-8">
              <h3 className="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">
                Order Summary ({cart.item_count} items)
              </h3>

              {cart.items.map((item) => (
                <div key={item.id} className="flex items-center space-x-4 py-4 border-b border-gray-100">
                  <div className="flex-1">
                    <h4 className="text-sm font-medium text-gray-900">
                      {item.productVariant.product.name}
                    </h4>
                    <p className="text-sm text-gray-500">
                      SKU: {item.productVariant.sku}
                    </p>
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

            {/* Order Total */}
            <div className="border-t border-gray-200 pt-4">
              <div className="flex justify-between items-center text-lg font-medium">
                <span>Subtotal:</span>
                <span>${cart.subtotal.toFixed(2)}</span>
              </div>
              <div className="flex justify-between items-center text-sm text-gray-600 mt-1">
                <span>Tax:</span>
                <span>$0.00</span>
              </div>
              <div className="flex justify-between items-center text-sm text-gray-600">
                <span>Shipping:</span>
                <span>$0.00</span>
              </div>
              <div className="flex justify-between items-center text-xl font-bold mt-4 pt-4 border-t border-gray-200">
                <span>Total:</span>
                <span>${cart.subtotal.toFixed(2)}</span>
              </div>
            </div>

            {/* Error Message */}
            {error && (
              <div className="mt-6 bg-red-50 border border-red-200 rounded-md p-4">
                <div className="text-sm text-red-700">{error}</div>
              </div>
            )}

            {/* Continue to Payment Button */}
            <div className="mt-8">
              <button
                onClick={handleInitiatePayment}
                disabled={loading}
                className="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {loading ? (
                  <div className="flex items-center">
                    <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    Processing...
                  </div>
                ) : (
                  `Proceed to Payment - $${cart.subtotal.toFixed(2)}`
                )}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default CheckoutPage