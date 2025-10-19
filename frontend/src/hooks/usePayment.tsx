import { useState, useCallback } from 'react'
import api from '../services/api'

export interface PaymentIntent {
  payment_intent_id: string
  client_secret: string
  amount: number
  currency: string
  cart_id: number
  item_count: number
  subtotal: number
}

export interface PaymentStatus {
  payment_intent_id: string
  status: string
  amount: number
  currency: string
  last_payment_error?: any
}

export interface CheckoutError {
  message: string
  type: 'validation' | 'payment' | 'network' | 'server'
  details?: any
}

export const usePayment = () => {
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<CheckoutError | null>(null)

  const initiatePayment = useCallback(async (cartId?: number): Promise<PaymentIntent | null> => {
    setLoading(true)
    setError(null)

    try {
      const response = await api.post('/checkout/initiate', {
        cart_id: cartId,
      })

      if (response.data.success) {
        return response.data.data
      } else {
        throw new Error(response.data.message || 'Failed to initiate payment')
      }
    } catch (err: any) {
      const errorType = err.response?.status === 422 ? 'validation' : 'server'
      setError({
        message: err.response?.data?.message || err.message || 'Payment initiation failed',
        type: errorType,
        details: err.response?.data?.errors
      })
      return null
    } finally {
      setLoading(false)
    }
  }, [])

  const confirmPayment = useCallback(async (paymentIntentId: string): Promise<boolean> => {
    setLoading(true)
    setError(null)

    try {
      const response = await api.post('/checkout/process', {
        payment_intent_id: paymentIntentId,
      })

      if (response.data.success) {
        return true
      } else {
        throw new Error(response.data.message || 'Payment confirmation failed')
      }
    } catch (err: any) {
      const errorType = err.response?.status === 402 ? 'payment' : 'server'
      setError({
        message: err.response?.data?.message || err.message || 'Payment confirmation failed',
        type: errorType,
        details: err.response?.data?.error
      })
      return false
    } finally {
      setLoading(false)
    }
  }, [])

  const checkPaymentStatus = useCallback(async (paymentIntentId: string, retryCount = 0): Promise<PaymentStatus | null> => {
    try {
      const response = await api.get(`/checkout/payment-status/${paymentIntentId}`)

      if (response.data.success) {
        return response.data.data
      } else {
        return null
      }
    } catch (err: any) {
      console.error('Failed to check payment status:', err)

      // Retry logic for network failures
      if (retryCount < 3 && (err.code === 'NETWORK_ERROR' || err.response?.status >= 500)) {
        await new Promise(resolve => setTimeout(resolve, Math.pow(2, retryCount) * 1000)) // Exponential backoff
        return checkPaymentStatus(paymentIntentId, retryCount + 1)
      }

      return null
    }
  }, [])

  const clearError = useCallback(() => {
    setError(null)
  }, [])

  return {
    loading,
    error,
    initiatePayment,
    confirmPayment,
    checkPaymentStatus,
    clearError,
  }
}