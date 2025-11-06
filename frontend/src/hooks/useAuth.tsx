import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from 'react';
import api, { getCsrfCookie } from '../services/api';

interface User {
  id: number;
  name: string;
  email: string;
}

interface AuthContextValue {
  user: User | null;
  loading: boolean;
  login: (credentials: { email: string; password: string }) => Promise<boolean>;
  register: (
    payload: { name: string; email: string; password: string; password_confirmation: string }
  ) => Promise<boolean>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export const useAuth = (): AuthContextValue => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

export const AuthProvider = ({ children }: { children: React.ReactNode }) => {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  const refreshUser = useCallback(async () => {
    try {
      const token = localStorage.getItem('auth_token');
      if (!token) {
        setUser(null);
        return;
      }
      const response = await api.get('/api/user');
      setUser(response.data.user ?? response.data.data ?? response.data);
    } catch {
      localStorage.removeItem('auth_token');
      setUser(null);
    }
  }, []);

  useEffect(() => {
    const init = async () => {
      await refreshUser();
      setLoading(false);
    };
    init();
  }, [refreshUser]);

  const login = useCallback(
    async ({ email, password }: { email: string; password: string }) => {
      try {
        await getCsrfCookie();
        const response = await api.post('/api/auth/login', { email, password });
        const { token, user: payloadUser } = response.data;
        if (token) {
          localStorage.setItem('auth_token', token);
        }
        setUser(payloadUser ?? null);
        return true;
      } catch (error) {
        console.error('Login failed', error);
        return false;
      }
    },
    []
  );

  const register = useCallback(
    async (payload: {
      name: string;
      email: string;
      password: string;
      password_confirmation: string;
    }) => {
      try {
        await getCsrfCookie();
        const response = await api.post('/api/auth/register', payload);
        if (response.data?.token && response.data?.user) {
          localStorage.setItem('auth_token', response.data.token);
          setUser(response.data.user);
        }
        return true;
      } catch (error) {
        console.error('Registration failed', error);
        return false;
      }
    },
    []
  );

  const logout = useCallback(async () => {
    try {
      await api.post('/api/auth/logout');
    } catch (error) {
      console.error('Logout failed', error);
    } finally {
      localStorage.removeItem('auth_token');
      setUser(null);
    }
  }, []);

  const value = useMemo(
    () => ({
      user,
      loading,
      login,
      register,
      logout,
      refreshUser,
    }),
    [user, loading, login, register, logout, refreshUser]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};
