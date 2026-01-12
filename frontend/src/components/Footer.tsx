import { Link } from 'react-router-dom';
import { footerText } from '../mockData';
import { SparklesIcon } from '@heroicons/react/24/outline';

const Footer = () => {
  return (
    <footer className="relative mt-24 border-t border-white/10 bg-gradient-to-t from-black/60 via-[#040d0a] to-transparent py-20">
      <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-emerald-300/40 to-transparent" />
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 lg:grid-cols-[1.2fr_0.8fr_0.8fr] gap-12">
          <div className="space-y-6">
            <div className="flex items-center gap-4">
              <div className="relative flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400/60 to-teal-300/60 text-emerald-950 shadow-[0_0_30px_rgba(58,240,124,0.35)]">
                <SparklesIcon className="h-7 w-7" />
                <span className="absolute -inset-1 rounded-2xl border border-emerald-200/40 opacity-40" />
              </div>
              <div>
                <span className="block text-xl font-semibold uppercase tracking-[0.4em] text-emerald-100">
                  {footerText.companyName}
                </span>
                <span className="block text-xs uppercase tracking-[0.35em] text-emerald-200/70">
                  Eco • Friendly • Tech • Outdoor
                </span>
              </div>
            </div>
            <p className="text-emerald-100/70 leading-relaxed">
              {footerText.tagline}
            </p>
            <div className="flex items-center gap-4 text-emerald-100/60">
              <span className="h-px flex-1 bg-gradient-to-r from-transparent via-emerald-300/40 to-transparent" />
              <span className="text-xs uppercase tracking-[0.35em]">Connect</span>
              <span className="h-px flex-1 bg-gradient-to-r from-transparent via-emerald-300/40 to-transparent" />
            </div>
            <div className="flex space-x-4">
              <a href="#" className="flex h-11 w-11 items-center justify-center rounded-xl border border-emerald-200/30 bg-white/5 text-emerald-100 transition hover:border-emerald-200/70 hover:text-emerald-100">
                <svg className="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                </svg>
              </a>
              <a href="#" className="flex h-11 w-11 items-center justify-center rounded-xl border border-emerald-200/30 bg-white/5 text-emerald-100 transition hover:border-emerald-200/70 hover:text-emerald-100">
                <svg className="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.748-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24.009 12.017 24.009c6.624 0 11.99-5.367 11.99-11.987C24.007 5.367 18.641.001 12.017.001z"/>
                </svg>
              </a>
              <a href="#" className="flex h-11 w-11 items-center justify-center rounded-xl border border-emerald-200/30 bg-white/5 text-emerald-100 transition hover:border-emerald-200/70 hover:text-emerald-100">
                <svg className="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                </svg>
              </a>
            </div>
          </div>
          <div>
            <h3 className="text-sm font-semibold uppercase tracking-[0.35em] text-emerald-200 mb-6">
              Quick Links
            </h3>
            <ul className="space-y-3">
              {footerText.quickLinks.map((link) => {
                const href = link.toLowerCase() === 'home' ? '/' : `/${link.toLowerCase()}`;
                return (
                  <li key={link}>
                    <Link
                      to={href}
                      className="group inline-flex items-center gap-3 text-emerald-100/70 transition hover:text-emerald-100"
                    >
                      <span className="h-px w-6 bg-emerald-300/20 transition-all group-hover:w-10 group-hover:bg-emerald-300/60" />
                      {link}
                    </Link>
                  </li>
                );
              })}
            </ul>
          </div>
          <div>
            <h3 className="text-sm font-semibold uppercase tracking-[0.35em] text-emerald-200 mb-6">
              Contact
            </h3>
            <ul className="space-y-3 text-emerald-100/70">
              <li className="leading-relaxed">{footerText.contact.address}</li>
              <li>
                <a href={`mailto:${footerText.contact.email}`} className="hover:text-emerald-100">
                  {footerText.contact.email}
                </a>
              </li>
              <li>
                <a href={`tel:${footerText.contact.phone}`} className="hover:text-emerald-100">
                  {footerText.contact.phone}
                </a>
              </li>
            </ul>
          </div>
        </div>
        <div className="mt-16 flex flex-col items-center gap-4 text-center text-xs uppercase tracking-[0.35em] text-emerald-200/60">
          <div className="h-px w-full bg-gradient-to-r from-transparent via-emerald-300/30 to-transparent" />
          <p>{footerText.copyright}</p>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
