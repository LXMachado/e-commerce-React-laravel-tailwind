import React, { useState, useEffect } from 'react'
import {
  PaymentElement,
  useStripe,
  useElements,
  AddressElement,
} from '@stripe/react-stripe-js'
import { usePayment } from '../hooks/usePayment'

interface PaymentFormProps {
  paymentIntentId: string
  clientSecret: string
  onPaymentSuccess: (paymentIntentId: string) => void
  onPaymentError: (error: string) => void
}

const PaymentForm: React.FC<PaymentFormProps> = ({
  paymentIntentId,
  clientSecret,
  onPaymentSuccess,
  onPaymentError,
}) => {
  const stripe = useStripe()
  const elements = useElements()
  const { loading, confirmPayment, error, clearError } = usePayment()

  const [paymentElementReady, setPaymentElementReady] = useState(false)
  const [processing, setProcessing] = useState(false)
  const [formError, setFormError] = useState<string>('')

  useEffect(() => {
    if (error) {
      setFormError(error.message)
      onPaymentError(error.message)
    }
  }, [error, onPaymentError])

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault()

    if (!stripe || !elements) {
      const errorMessage = 'Stripe has not loaded yet'
      setFormError(errorMessage)
      onPaymentError(errorMessage)
      return
    }

    if (!paymentElementReady) {
      const errorMessage = 'Payment form is not ready'
      setFormError(errorMessage)
      onPaymentError(errorMessage)
      return
    }

    setProcessing(true)
    setFormError('')
    clearError()

    try {
      // First confirm payment with backend
      const paymentConfirmed = await confirmPayment(paymentIntentId)

      if (!paymentConfirmed) {
        throw new Error('Payment confirmation failed')
      }

      // If backend confirmation succeeds, the webhook will handle the rest
      onPaymentSuccess(paymentIntentId)

    } catch (err: any) {
      const errorMessage = err.message || 'Payment failed'
      setFormError(errorMessage)
      onPaymentError(errorMessage)
    } finally {
      setProcessing(false)
    }
  }

  const handlePaymentElementChange = (event: any) => {
    setPaymentElementReady(event.complete)
    if (event.error) {
      setFormError(event.error.message)
    } else {
      setFormError('')
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      {/* Address Element for billing address */}
      <div className="space-y-2">
        <label className="block text-sm font-medium text-gray-700">
          Billing Address
        </label>
        <div className="border border-gray-300 rounded-md p-3 bg-gray-50">
          <AddressElement
            options={{
              mode: 'billing',
              allowedCountries: ['US', 'CA', 'AU', 'GB'],
            }}
            onChange={(event) => {
              // AddressElement doesn't provide error in the same way as PaymentElement
              // Errors are handled through form validation
            }}
          />
        </div>
      </div>

      {/* Payment Element */}
      <div className="space-y-2">
        <label className="block text-sm font-medium text-gray-700">
          Payment Information
        </label>
        <div className="border border-gray-300 rounded-md p-3 bg-white min-h-[100px]">
          <PaymentElement
            options={{
              layout: 'tabs',
            }}
            onReady={() => setPaymentElementReady(true)}
            onChange={handlePaymentElementChange}
          />
        </div>
      </div>

      {/* Error Message */}
      {formError && (
        <div className="bg-red-50 border border-red-200 rounded-md p-3">
          <div className="text-sm text-red-700">{formError}</div>
        </div>
      )}

      {/* Submit Button */}
      <button
        type="submit"
        disabled={!stripe || !paymentElementReady || processing || loading}
        className="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        {processing || loading ? (
          <div className="flex items-center">
            <svg
              className="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                className="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                strokeWidth="4"
              />
              <path
                className="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              />
            </svg>
            Processing Payment...
          </div>
        ) : (
          'Complete Payment'
        )}
      </button>

      {/* Security Notice */}
      <div className="text-xs text-gray-500 text-center">
        <div className="flex items-center justify-center space-x-2">
          <svg
            className="w-4 h-4"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path
              fillRule="evenodd"
              d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
              clipRule="evenodd"
            />
          </svg>
          <span>Your payment information is secure and encrypted</span>
        </div>
      </div>
    </form>
  )
}

export default PaymentForm