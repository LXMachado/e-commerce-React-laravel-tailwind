import { useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Bars3Icon, XMarkIcon } from '@heroicons/react/24/outline';
import { useAuth } from '../hooks/useAuth';

const NavBar = () => {
  const [isOpen, setIsOpen] = useState(false);
  const navigate = useNavigate();
  const { user, logout } = useAuth();

  const toggleMenu = () => setIsOpen((previous) => !previous);

  const links = useMemo(() => {
    const base = [
      { label: 'Home', href: '/' },
      { label: 'Products', href: '/products' },
      { label: 'About', href: '/about' },
      { label: 'Contact', href: '/contact' },
    ];

    if (user) {
      return [
        ...base.slice(0, 2),
        { label: 'Dashboard', href: '/dashboard' },
        { label: 'Cart', href: '/cart' },
        ...base.slice(2),
      ];
    }

    return [
      ...base.slice(0, 2),
      { label: 'Login', href: '/login' },
      ...base.slice(2),
    ];
  }, [user]);

  const handleLogout = async () => {
    await logout();
    navigate('/');
  };

  return (
    <nav className="fixed top-0 z-50 w-full border-b border-white/10 backdrop-blur-2xl">
      <div className="absolute inset-0 bg-gradient-to-r from-white/10 via-white/5 to-white/5 opacity-40" />
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div className="flex justify-between items-center h-20">
          <div className="flex items-center space-x-3">
            <Link to="/" className="flex items-center space-x-3 group">
              <span className="relative flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400/80 via-emerald-500/60 to-teal-400/60 text-gray-900 shadow-[0_0_25px_rgba(58,240,124,0.4)] transition-transform duration-300 group-hover:rotate-3 group-hover:scale-105">
                <svg viewBox="0 0 48 48" className="h-8 w-8" fill="none" stroke="currentColor" strokeWidth="2.2">
                  <path d="M6 34h36" strokeLinecap="round" className="stroke-emerald-200/70" />
                  <path d="M12 30l12-18 12 18" strokeLinejoin="round" className="stroke-emerald-950/70" />
                  <path d="M20 26l4-6 4 6" strokeLinejoin="round" className="stroke-emerald-950/70" />
                  <path d="M14 34v4h20v-4" className="stroke-emerald-200/60" strokeLinecap="round" />
                  <circle cx="10" cy="14" r="3" className="stroke-emerald-100/70" />
                </svg>
                <span className="absolute -inset-1 rounded-2xl border border-emerald-200/40 opacity-50 group-hover:opacity-80 transition-opacity" />
              </span>
              <div>
                <span className="block text-xl font-semibold text-emerald-200 tracking-wide uppercase">
                  Weekender
                </span>
                <span className="block text-xs font-medium text-emerald-100/70 tracking-[0.3em]">
                  Eco • Tech • Outdoors
                </span>
              </div>
            </Link>
          </div>
          <div className="hidden md:block">
            <div className="ml-10 flex items-center space-x-6">
              {links.map((link) => (
                <Link
                  key={link.href}
                  to={link.href}
                  className="group relative text-sm font-medium uppercase tracking-widest text-emerald-100/80 transition-colors duration-200 hover:text-emerald-100"
                >
                  <span className="absolute -bottom-2 left-1/2 h-px w-0 bg-gradient-to-r from-transparent via-emerald-300 to-transparent transition-all duration-300 group-hover:w-full group-hover:left-0" />
                  {link.label}
                </Link>
              ))}
              <Link
                to="/products"
                className="neon-cta rounded-full px-5 py-2 text-sm font-semibold uppercase tracking-widest"
              >
                Shop Now
              </Link>
              {user && (
                <button
                  type="button"
                  onClick={handleLogout}
                  className="rounded-full border border-emerald-200/40 px-5 py-2 text-xs font-semibold uppercase tracking-[0.35em] text-emerald-100 transition hover:border-emerald-200/70 hover:text-emerald-100"
                >
                  Sign Out
                </button>
              )}
            </div>
          </div>
          <div className="md:hidden">
            <button
              onClick={toggleMenu}
              className="flex h-11 w-11 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-emerald-100 hover:bg-white/10 transition"
              aria-label="Toggle menu"
            >
              {isOpen ? <XMarkIcon className="h-6 w-6" /> : <Bars3Icon className="h-6 w-6" />}
            </button>
          </div>
        </div>
      </div>
      {isOpen && (
        <div className="md:hidden px-4">
          <div className="mt-2 rounded-2xl border border-white/10 bg-gradient-to-br from-white/10 via-white/5 to-transparent backdrop-blur-xl shadow-[0_20px_60px_rgba(0,0,0,0.35)]">
            <div className="px-4 pt-4 pb-6 space-y-2">
              {links.map((link) => (
                <Link
                  key={link.href}
                  to={link.href}
                  onClick={toggleMenu}
                  className="block rounded-xl px-3 py-2 text-sm font-medium uppercase tracking-[0.25em] text-emerald-100/80 transition hover:bg-white/10 hover:text-emerald-100"
                >
                  {link.label}
                </Link>
              ))}
              <Link
                to="/products"
                onClick={toggleMenu}
                className="block text-center rounded-xl px-3 py-3 text-sm font-semibold uppercase tracking-[0.3em] neon-cta"
              >
                Shop Now
              </Link>
              {user && (
                <button
                  type="button"
                  onClick={() => {
                    toggleMenu();
                    handleLogout();
                  }}
                  className="block w-full rounded-xl px-3 py-2 text-sm font-semibold uppercase tracking-[0.3em] text-emerald-100/80 transition hover:bg-white/10 hover:text-emerald-100"
                >
                  Sign Out
                </button>
              )}
            </div>
          </div>
        </div>
      )}
    </nav>
  );
};

export default NavBar;
