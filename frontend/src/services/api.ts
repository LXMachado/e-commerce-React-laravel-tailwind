import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE ?? 'http://localhost:8000',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  const csrfToken = getCookie('XSRF-TOKEN');
  if (csrfToken && config.method && config.method.toUpperCase() !== 'GET') {
    config.headers['X-XSRF-TOKEN'] = decodeURIComponent(csrfToken);
  }

  return config;
});

const getCookie = (name: string): string | null => {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) {
    return parts.pop()?.split(';').shift() ?? null;
  }
  return null;
};

export const getCsrfCookie = async (): Promise<void> => {
  try {
    const base = import.meta.env.VITE_API_BASE ?? 'http://localhost:8000';
    await axios.get(`${base}/sanctum/csrf-cookie`, {
      withCredentials: true,
    });
  } catch (error) {
    console.error('Failed to fetch CSRF cookie', error);
  }
};

export default api;
