import React, { createContext, useContext, useEffect, useState } from 'react'
import api, { getCsrfCookie } from '../services/api'

interface User {
  id: number
  name: string
  email: string
}

interface AuthContextType {
  user: User | null
  login: (email: string, password: string) => Promise<void>
  register: (name: string, email: string, password: string, passwordConfirmation: string) => Promise<void>
  logout: () => Promise<void>
  loading: boolean
}

const AuthContext = createContext<AuthContextType | undefined>(undefined)

export const useAuth = () => {
  const context = useContext(AuthContext)
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider')
  }
  return context
}

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    checkAuth()
  }, [])

  const checkAuth = async () => {
    try {
      const token = localStorage.getItem('auth_token')
      if (token) {
        const response = await api.get('/api/user')
        setUser(response.data.user)
      }
    } catch (error) {
      localStorage.removeItem('auth_token')
    } finally {
      setLoading(false)
    }
  }

  const login = async (email: string, password: string) => {
    await getCsrfCookie()
    const response = await api.post('/api/auth/login', { email, password })
    const { user, token } = response.data
    localStorage.setItem('auth_token', token)
    setUser(user)
  }

  const register = async (name: string, email: string, password: string, passwordConfirmation: string) => {
    await getCsrfCookie()
    await api.post('/api/auth/register', { name, email, password, password_confirmation: passwordConfirmation })
  }

  const logout = async () => {
    try {
      await api.post('/api/auth/logout')
    } finally {
      localStorage.removeItem('auth_token')
      setUser(null)
    }
  }

  const value = {
    user,
    login,
    register,
    logout,
    loading,
  }

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}