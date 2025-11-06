import { type FormEvent, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import NavBar from '../components/NavBar';
import Footer from '../components/Footer';
import { useAuth } from '../hooks/useAuth';

const Login = () => {
  const navigate = useNavigate();
  const { login } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [remember, setRemember] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setLoading(true);
    setError(null);

    const success = await login({ email, password });

    if (success) {
      if (!remember) {
        // If the user doesn't want to be remembered, drop the token on reload
        window.addEventListener(
          'beforeunload',
          () => {
            localStorage.removeItem('auth_token');
          },
          { once: true }
        );
      }
      navigate('/dashboard');
    } else {
      setError('We could not sign you in with those credentials.');
    }

    setLoading(false);
  };

  return (
    <div className="min-h-screen bg-[#031410] text-emerald-100">
      <NavBar />
      <main className="pt-28 pb-16">
        <section className="max-w-md mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
          <header className="space-y-3 text-center">
            <p className="inline-flex items-center justify-center rounded-full border border-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.35em] text-emerald-100/70">
              Account Login
            </p>
            <h1 className="text-4xl font-semibold">Trail-ready access.</h1>
            <p className="text-sm text-emerald-100/70">
              Use your Weekender credentials to sync carts, view orders, and manage your regenerative impact.
            </p>
          </header>

          <form
            onSubmit={handleSubmit}
            className="rounded-[32px] border border-white/10 bg-white/5 p-8 backdrop-blur-2xl shadow-[0_25px_70px_rgba(0,0,0,0.35)] space-y-6"
          >
            <Input
              label="Email"
              type="email"
              placeholder="you@weekender.com"
              value={email}
              onChange={setEmail}
              required
            />
            <Input
              label="Password"
              type="password"
              placeholder="••••••••"
              value={password}
              onChange={setPassword}
              required
            />

            <div className="flex items-center justify-between text-xs text-emerald-100/70">
              <label className="inline-flex items-center gap-2">
                <input
                  type="checkbox"
                  checked={remember}
                  onChange={(event) => setRemember(event.target.checked)}
                  className="rounded border-white/20 bg-[#02120D]"
                />
                <span className="uppercase tracking-[0.3em]">Remember me</span>
              </label>
              <span className="uppercase tracking-[0.3em] text-emerald-100/40">
                Reset password coming soon
              </span>
            </div>

            {error && (
              <div className="rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-xs text-red-200">
                {error}
              </div>
            )}

            <button
              type="submit"
              className="w-full neon-cta rounded-full px-6 py-3 text-sm font-semibold uppercase tracking-[0.35em] disabled:opacity-50"
              disabled={loading}
            >
              {loading ? 'Signing In…' : 'Sign In'}
            </button>

            <p className="text-xs text-center text-emerald-100/60">
              New explorer?{' '}
              <Link to="/register" className="text-emerald-200/90 underline">
                Create an account
              </Link>
            </p>
          </form>
        </section>
      </main>
      <Footer />
    </div>
  );
};

interface InputProps {
  label: string;
  placeholder?: string;
  type?: string;
  value: string;
  required?: boolean;
  onChange: (value: string) => void;
}

const Input = ({ label, placeholder, type = 'text', value, onChange, required }: InputProps) => (
  <label className="block space-y-2 text-sm">
    <span className="uppercase tracking-[0.3em] text-emerald-200/70">{label}</span>
    <input
      type={type}
      placeholder={placeholder}
      value={value}
      required={required}
      onChange={(event) => onChange(event.target.value)}
      className="w-full rounded-2xl border border-white/10 bg-[#02120D] px-4 py-3 text-emerald-100 focus:border-emerald-300/60 focus:outline-none focus:ring-2 focus:ring-emerald-300/30"
    />
  </label>
);

export default Login;
