import { useState } from 'react';
import NavBar from '../components/NavBar';
import Footer from '../components/Footer';
import { footerText } from '../mockData';

const Contact = () => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    message: '',
  });
  const [isSubmitted, setIsSubmitted] = useState(false);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (formData.name && formData.email && formData.message) {
      setIsSubmitted(true);
      setFormData({ name: '', email: '', message: '' });
      setTimeout(() => setIsSubmitted(false), 3000);
    }
  };

  return (
    <div className="min-h-screen bg-transparent text-emerald-100">
      <NavBar />
      <main className="pt-24">
        <section className="relative py-24 overflow-hidden">
          <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(58,240,124,0.1),_transparent_55%)]" />
          <div className="absolute inset-x-0 bottom-0 h-64 bg-gradient-to-t from-[#030d0a] via-transparent to-transparent" />
          <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="text-center mb-20 space-y-6">
              <span className="inline-flex items-center justify-center rounded-full border border-emerald-200/30 bg-white/5 px-5 py-2 text-xs font-semibold uppercase tracking-[0.4em] text-emerald-100">
                Contact Weekender
              </span>
              <h1 className="text-4xl md:text-5xl font-semibold text-emerald-100">
                Let’s Map Your Next Eco-Tech Escape
              </h1>
              <p className="text-lg md:text-xl text-emerald-100/80 max-w-3xl mx-auto">
                Have questions about our kits or need support in the wild? Reach out and our trail team will help you craft a sustainable, tech-enabled weekend.
              </p>
            </div>
            <div className="grid grid-cols-1 lg:grid-cols-[1.1fr_0.9fr] gap-12">
              <div className="rounded-[32px] border border-emerald-200/30 bg-white/5 p-10 backdrop-blur-2xl shadow-[0_25px_70px_rgba(0,0,0,0.35)]">
                <h2 className="text-2xl font-semibold text-emerald-100 mb-6">Send a Message</h2>
                <form onSubmit={handleSubmit} className="space-y-6">
                  <div>
                    <label htmlFor="name" className="mb-2 block text-xs font-semibold uppercase tracking-[0.35em] text-emerald-200/80">
                      Name
                    </label>
                    <input
                      type="text"
                      id="name"
                      name="name"
                      value={formData.name}
                      onChange={handleChange}
                      className="w-full rounded-2xl border border-white/15 bg-transparent px-4 py-3 text-emerald-100 placeholder:text-emerald-100/40 focus:border-emerald-300/60 focus:outline-none focus:ring-0 transition"
                      required
                    />
                  </div>
                  <div>
                    <label htmlFor="email" className="mb-2 block text-xs font-semibold uppercase tracking-[0.35em] text-emerald-200/80">
                      Email
                    </label>
                    <input
                      type="email"
                      id="email"
                      name="email"
                      value={formData.email}
                      onChange={handleChange}
                      className="w-full rounded-2xl border border-white/15 bg-transparent px-4 py-3 text-emerald-100 placeholder:text-emerald-100/40 focus:border-emerald-300/60 focus:outline-none focus:ring-0 transition"
                      required
                    />
                  </div>
                  <div>
                    <label htmlFor="message" className="mb-2 block text-xs font-semibold uppercase tracking-[0.35em] text-emerald-200/80">
                      Message
                    </label>
                    <textarea
                      id="message"
                      name="message"
                      value={formData.message}
                      onChange={handleChange}
                      rows={5}
                      className="w-full rounded-2xl border border-white/15 bg-transparent px-4 py-3 text-emerald-100 placeholder:text-emerald-100/40 focus:border-emerald-300/60 focus:outline-none focus:ring-0 transition"
                      required
                    />
                  </div>
                  <button
                    type="submit"
                    className="neon-cta w-full rounded-2xl px-6 py-3 text-sm font-semibold uppercase tracking-[0.4em]"
                  >
                    Send Message
                  </button>
                </form>
                {isSubmitted && (
                  <p className="mt-6 text-center text-xs uppercase tracking-[0.4em] text-emerald-200 animate-pulse">
                    Thank you for your message! We'll get back to you soon.
                  </p>
                )}
              </div>
              <div className="space-y-10">
                <div className="rounded-[32px] border border-white/10 bg-white/5 p-10 backdrop-blur-2xl shadow-[0_25px_70px_rgba(0,0,0,0.35)]">
                  <h2 className="text-2xl font-semibold text-emerald-100 mb-6">Contact Information</h2>
                  <div className="space-y-6 text-emerald-100/80">
                    <div className="flex items-center gap-4">
                      <svg className="h-6 w-6 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      <span>{footerText.contact.address}</span>
                    </div>
                    <div className="flex items-center gap-4">
                      <svg className="h-6 w-6 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                      </svg>
                      <a href={`mailto:${footerText.contact.email}`} className="hover:text-emerald-100">
                        {footerText.contact.email}
                      </a>
                    </div>
                    <div className="flex items-center gap-4">
                      <svg className="h-6 w-6 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                      </svg>
                      <a href={`tel:${footerText.contact.phone}`} className="hover:text-emerald-100">
                        {footerText.contact.phone}
                      </a>
                    </div>
                  </div>
                </div>
                <div className="rounded-[32px] border border-white/10 bg-white/5 p-10 backdrop-blur-2xl shadow-[0_25px_70px_rgba(0,0,0,0.35)]">
                  <h3 className="text-xl font-semibold text-emerald-100 mb-6">Follow Our Trails</h3>
                  <div className="flex gap-4">
                    {['twitter', 'dribbble', 'instagram'].map((network) => (
                      <a
                        key={network}
                        href="#"
                        className="flex h-12 w-12 items-center justify-center rounded-xl border border-emerald-200/30 bg-white/5 text-emerald-100 transition hover:border-emerald-200/70 hover:text-emerald-100"
                        aria-label={network}
                      >
                        {network === 'twitter' && (
                          <svg className="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z" />
                          </svg>
                        )}
                        {network === 'dribbble' && (
                          <svg className="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.748-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24.009 12.017 24.009c6.624 0 11.99-5.367 11.99-11.987C24.007 5.367 18.641.001 12.017.001z" />
                          </svg>
                        )}
                        {network === 'instagram' && (
                          <svg className="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                          </svg>
                        )}
                      </a>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default Contact;
