import { useState } from 'react';
import { newsletterText } from '../mockData';

const Newsletter = () => {
  const [email, setEmail] = useState('');
  const [isSubmitted, setIsSubmitted] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (email) {
      setIsSubmitted(true);
      setEmail('');
      setTimeout(() => setIsSubmitted(false), 3000);
    }
  };

  return (
    <section className="relative py-24">
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_center,_rgba(0,255,200,0.08),_transparent_55%)]" />
      <div className="absolute inset-x-0 bottom-0 h-64 bg-gradient-to-t from-[#030d0a] via-transparent to-transparent" />
      <div className="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div className="mx-auto max-w-3xl space-y-6">
          <span className="inline-flex items-center justify-center rounded-full border border-emerald-200/30 bg-white/5 px-5 py-2 text-xs font-semibold uppercase tracking-[0.4em] text-emerald-100">
            Stay in the loop
          </span>
          <h2 className="text-4xl font-semibold text-emerald-100">{newsletterText.title}</h2>
          <p className="text-lg text-emerald-100/80">
            {newsletterText.description}
          </p>
        </div>
        <form onSubmit={handleSubmit} className="max-w-2xl mx-auto mt-12">
          <div className="glass-panel border-white/10 rounded-3xl p-4 sm:p-5 flex flex-col sm:flex-row gap-3 sm:gap-4">
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder={newsletterText.placeholder}
              className="flex-1 rounded-2xl border border-white/15 bg-transparent px-4 py-3 text-base text-emerald-100 placeholder:text-emerald-100/40 focus:border-emerald-300/60 focus:outline-none focus:ring-0 transition"
              required
            />
            <button
              type="submit"
              className="neon-cta rounded-2xl px-8 py-3 text-sm font-semibold uppercase tracking-[0.35em]"
            >
              {newsletterText.button}
            </button>
          </div>
        </form>
        {isSubmitted && (
          <p className="mt-6 text-sm uppercase tracking-[0.4em] text-emerald-200 animate-pulse">
            Thank you for subscribing!
          </p>
        )}
      </div>
    </section>
  );
};

export default Newsletter;
