import React, { useState, useEffect, useRef } from 'react'
import { usePayment } from '../hooks/usePayment'

interface PaymentStatusProps {
  paymentIntentId: string
  onPaymentSuccess: () => void
  onPaymentError: (error: string) => void
  maxPollingAttempts?: number
  pollingInterval?: number
}

const PaymentStatus: React.FC<PaymentStatusProps> = ({
  paymentIntentId,
  onPaymentSuccess,
  onPaymentError,
  maxPollingAttempts = 30, // 30 attempts = 30 seconds at 1 second intervals
  pollingInterval = 1000, // 1 second
}) => {
  const [pollingAttempts, setPollingAttempts] = useState(0)
  const [paymentStatus, setPaymentStatus] = useState<string>('pending')
  const [error, setError] = useState<string>('')

  const { checkPaymentStatus } = usePayment()
  const timeoutRef = useRef<number | null>(null)
  const pollingAttemptsRef = useRef(0)

  useEffect(() => {
    // Reset polling when paymentIntentId changes
    setPollingAttempts(0)
    setPaymentStatus('pending')
    setError('')
    pollingAttemptsRef.current = 0

    const pollPaymentStatus = async () => {
      if (pollingAttemptsRef.current >= maxPollingAttempts) {
        setError('Payment confirmation timeout. Please contact support.')
        onPaymentError('Payment confirmation timeout')
        return
      }

      try {
        const status = await checkPaymentStatus(paymentIntentId)

        if (status) {
          setPaymentStatus(status.status)

          switch (status.status) {
            case 'succeeded':
              onPaymentSuccess()
              break

            case 'processing':
              // Continue polling
              pollingAttemptsRef.current += 1
              timeoutRef.current = setTimeout(pollPaymentStatus, pollingInterval)
              break

            case 'requires_payment_method':
              setError('Payment requires a payment method. Please try again.')
              onPaymentError('Payment requires payment method')
              break

            case 'requires_confirmation':
              setError('Payment requires confirmation. Please try again.')
              onPaymentError('Payment requires confirmation')
              break

            case 'canceled':
              setError('Payment was canceled.')
              onPaymentError('Payment was canceled')
              break

            default:
              if (status.last_payment_error) {
                setError(status.last_payment_error.message || 'Payment failed')
                onPaymentError(status.last_payment_error.message || 'Payment failed')
              } else {
                // Continue polling for unknown status
                pollingAttemptsRef.current += 1
                timeoutRef.current = setTimeout(pollPaymentStatus, pollingInterval)
              }
          }
        } else {
          // Continue polling if no status received
          pollingAttemptsRef.current += 1
          timeoutRef.current = setTimeout(pollPaymentStatus, pollingInterval)
        }
      } catch (err: any) {
        setError('Failed to check payment status')
        onPaymentError('Failed to check payment status')

        // Continue polling on error
        pollingAttemptsRef.current += 1
        timeoutRef.current = setTimeout(pollPaymentStatus, pollingInterval)
      }
    }

    // Start polling after initial delay
    timeoutRef.current = setTimeout(pollPaymentStatus, 1000)

    return () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current)
        timeoutRef.current = null
      }
    }
  }, [paymentIntentId, maxPollingAttempts, pollingInterval, checkPaymentStatus, onPaymentSuccess, onPaymentError])

  // Update polling attempts display
  useEffect(() => {
    setPollingAttempts(pollingAttemptsRef.current)
  }, [pollingAttemptsRef.current])

  const getStatusMessage = () => {
    switch (paymentStatus) {
      case 'pending':
        return 'Waiting for payment confirmation...'
      case 'processing':
        return 'Processing your payment...'
      case 'succeeded':
        return 'Payment successful!'
      case 'requires_payment_method':
        return 'Payment requires a payment method'
      case 'requires_confirmation':
        return 'Payment requires confirmation'
      case 'canceled':
        return 'Payment was canceled'
      default:
        return 'Checking payment status...'
    }
  }

  const getStatusIcon = () => {
    switch (paymentStatus) {
      case 'succeeded':
        return (
          <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
            <svg className="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
            </svg>
          </div>
        )

      case 'processing':
      case 'pending':
        return (
          <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
            <svg className="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
          </div>
        )

      default:
        return (
          <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
            <svg className="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
        )
    }
  }

  return (
    <div className="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-md w-full space-y-8">
        <div className="text-center">
          {getStatusIcon()}

          <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Payment Status
          </h2>

          <p className="mt-2 text-sm text-gray-600">
            {getStatusMessage()}
          </p>

          {/* Progress Bar */}
          <div className="mt-6">
            <div className="bg-gray-200 rounded-full h-2">
              <div
                className="bg-green-600 h-2 rounded-full transition-all duration-300 ease-out"
                style={{
                  width: `${Math.min((pollingAttempts / maxPollingAttempts) * 100, 100)}%`
                }}
              />
            </div>
            <p className="mt-2 text-xs text-gray-500">
              Checking payment status... ({pollingAttempts}/{maxPollingAttempts})
            </p>
          </div>

          {/* Error Message */}
          {error && (
            <div className="mt-6 bg-red-50 border border-red-200 rounded-md p-4">
              <div className="text-sm text-red-700">{error}</div>
              <button
                onClick={() => window.location.reload()}
                className="mt-3 w-full flex justify-center py-2 px-4 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
              >
                Retry
              </button>
            </div>
          )}

          {/* Timeout Warning */}
          {pollingAttempts > maxPollingAttempts * 0.8 && !error && (
            <div className="mt-6 bg-yellow-50 border border-yellow-200 rounded-md p-4">
              <div className="text-sm text-yellow-700">
                Taking longer than expected. If this continues, please contact support.
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

export default PaymentStatus