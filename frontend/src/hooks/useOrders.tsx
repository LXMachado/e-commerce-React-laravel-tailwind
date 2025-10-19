import { useState, useCallback } from 'react'
import api from '../services/api'

export interface OrderItem {
  id: number
  quantity: number
  price_at_time: number
  line_total: number
  product: {
    name: string
    slug: string
  }
}

export interface Order {
  id: number
  order_number: string
  status: 'pending' | 'paid' | 'processing' | 'shipped' | 'delivered' | 'cancelled'
  subtotal: number
  tax_amount: number
  shipping_amount: number
  total_amount: number
  currency: string
  payment_status: string
  shipping_status: string
  created_at: string
  items: OrderItem[]
}

export interface OrderStats {
  total_orders: number
  total_spent: number
  pending_orders: number
  completed_orders: number
  cancelled_orders: number
}

export const useOrders = () => {
  const [orders, setOrders] = useState<Order[]>([])
  const [stats, setStats] = useState<OrderStats | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const fetchOrders = useCallback(async (filters?: {
    status?: string
    payment_status?: string
    shipping_status?: string
    sort_by?: string
    sort_direction?: string
    per_page?: number
  }) => {
    setLoading(true)
    setError(null)

    try {
      const response = await api.get('/orders', { params: filters })

      if (response.data.success) {
        setOrders(response.data.data.data)
        return response.data.data
      } else {
        throw new Error(response.data.message || 'Failed to fetch orders')
      }
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || err.message || 'Failed to fetch orders'
      setError(errorMessage)
      return null
    } finally {
      setLoading(false)
    }
  }, [])

  const fetchOrder = useCallback(async (orderId: number): Promise<Order | null> => {
    setLoading(true)
    setError(null)

    try {
      const response = await api.get(`/orders/${orderId}`)

      if (response.data.success) {
        return response.data.data
      } else {
        throw new Error(response.data.message || 'Failed to fetch order')
      }
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || err.message || 'Failed to fetch order'
      setError(errorMessage)
      return null
    } finally {
      setLoading(false)
    }
  }, [])

  const fetchOrderStats = useCallback(async (): Promise<OrderStats | null> => {
    try {
      const response = await api.get('/orders/stats')

      if (response.data.success) {
        setStats(response.data.data)
        return response.data.data
      } else {
        throw new Error(response.data.message || 'Failed to fetch order stats')
      }
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || err.message || 'Failed to fetch order stats'
      setError(errorMessage)
      return null
    }
  }, [])

  const cancelOrder = useCallback(async (orderId: number): Promise<boolean> => {
    setLoading(true)
    setError(null)

    try {
      const response = await api.post(`/orders/${orderId}/cancel`)

      if (response.data.success) {
        // Update local state
        setOrders(prevOrders =>
          prevOrders.map(order =>
            order.id === orderId
              ? { ...order, status: 'cancelled' as const }
              : order
          )
        )
        return true
      } else {
        throw new Error(response.data.message || 'Failed to cancel order')
      }
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || err.message || 'Failed to cancel order'
      setError(errorMessage)
      return false
    } finally {
      setLoading(false)
    }
  }, [])

  const clearError = useCallback(() => {
    setError(null)
  }, [])

  return {
    orders,
    stats,
    loading,
    error,
    fetchOrders,
    fetchOrder,
    fetchOrderStats,
    cancelOrder,
    clearError,
  }
}