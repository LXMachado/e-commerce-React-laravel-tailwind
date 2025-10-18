import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE || 'http://localhost:8000',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Request interceptor to add auth token and CSRF token
api.interceptors.request.use((config) => {
  console.log('API Request:', config.method?.toUpperCase(), config.url)
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
    console.log('Adding auth token to request')
  }

  // Add CSRF token for stateful requests
  const csrfToken = getCookie('XSRF-TOKEN')
  if (csrfToken && config.url?.includes('/api/auth/')) {
    config.headers['X-XSRF-TOKEN'] = decodeURIComponent(csrfToken)
    console.log('Adding CSRF token to request')
  }

  return config
})

// Helper function to get cookie value
const getCookie = (name: string): string | null => {
  const value = `; ${document.cookie}`
  const parts = value.split(`; ${name}=`)
  if (parts.length === 2) {
    return parts.pop()?.split(';').shift() || null
  }
  return null
}

// Function to get CSRF cookie
export const getCsrfCookie = async (): Promise<void> => {
  try {
    await axios.get('http://localhost:8000/sanctum/csrf-cookie', {
      withCredentials: true,
    })
    console.log('CSRF cookie obtained')
  } catch (error) {
    console.error('Failed to get CSRF cookie:', error)
  }
}

// Response interceptor to handle token refresh
api.interceptors.response.use(
  (response) => {
    console.log('API Response:', response.status, response.config.url)
    return response
  },
  async (error) => {
    console.error('API Error:', error.response?.status, error.response?.data, error.config?.url)
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api