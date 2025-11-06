import { type FormEvent, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import NavBar from '../components/NavBar';
import Footer from '../components/Footer';
import { useAuth } from '../hooks/useAuth';

const Register = () => {
  const navigate = useNavigate();
  const { register, login } = useAuth();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (password !== passwordConfirmation) {
      setError('Passwords need to match.');
      return;
    }

    setLoading(true);
    setError(null);

    const success = await register({
      name,
      email,
      password,
      password_confirmation: passwordConfirmation,
    });

    if (!success) {
      setError('We could not complete your registration.');
      setLoading(false);
      return;
    }

    const loggedIn = await login({ email, password });

    if (loggedIn) {
      navigate('/dashboard');
    } else {
      setError('Your account was created, but we could not sign you in automatically. Please log in manually.');
    }
    setLoading(false);
  };

  return (
    <div className="min-h-screen bg-[#031410] text-emerald-100">
      <NavBar />
      <main className="pt-28 pb-16">
        <section className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
          <header className="space-y-3 text-center">
            <p className="inline-flex items-center justify-center rounded-full border border-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.35em] text-emerald-100/70">
              Create Account
            </p>
            <h1 className="text-4xl font-semibold">Build your regenerative profile.</h1>
            <p className="text-sm text-emerald-100/70">
              Register to unlock cart syncing, order history, and personalized eco-impact reports.
            </p>
          </header>

          <form
            onSubmit={handleSubmit}
            className="rounded-[32px] border border-white/10 bg-white/5 p-8 backdrop-blur-2xl shadow-[0_25px_70px_rgba(0,0,0,0.35)] space-y-6"
          >
            <div className="grid gap-5 md:grid-cols-2">
              <Input label="Full Name" placeholder="Jamie Rivera" value={name} onChange={setName} required />
              <Input
                label="Email"
                type="email"
                placeholder="you@weekender.com"
                value={email}
                onChange={setEmail}
                required
              />
            </div>
            <Input
              label="Password"
              type="password"
              placeholder="Choose a secure passphrase"
              value={password}
              onChange={setPassword}
              required
            />
            <Input
              label="Confirm Password"
              type="password"
              placeholder="Re-enter your passphrase"
              value={passwordConfirmation}
              onChange={setPasswordConfirmation}
              required
            />

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
              {loading ? 'Creating Profile…' : 'Create Account'}
            </button>

            <p className="text-xs text-center text-emerald-100/60">
              Already set up?{' '}
              <Link to="/login" className="text-emerald-200/90 underline">
                Sign in instead
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

export default Register;
