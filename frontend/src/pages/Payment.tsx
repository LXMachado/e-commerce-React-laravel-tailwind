import NavBar from '../components/NavBar';
import Footer from '../components/Footer';

const Payment = () => {
  return (
    <div className="min-h-screen bg-[#031410] text-emerald-100">
      <NavBar />
      <main className="pt-28 pb-16">
        <section className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
          <header className="space-y-3">
            <p className="inline-flex items-center rounded-full border border-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.35em] text-emerald-100/70">
              Mock Payment
            </p>
            <h1 className="text-4xl md:text-5xl font-semibold">
              Secure a sustainable checkout experience.
            </h1>
            <p className="max-w-2xl text-emerald-100/70">
              This is a mock payment interface for demo purposes only. No real data is captured.
            </p>
          </header>

          <form className="rounded-[32px] border border-white/10 bg-white/5 p-8 backdrop-blur-2xl shadow-[0_25px_70px_rgba(0,0,0,0.35)] space-y-8">
            <section className="space-y-4">
              <h2 className="text-2xl font-semibold">Contact Information</h2>
              <div className="grid gap-5 md:grid-cols-2">
                <Input label="First Name" placeholder="Jamie" />
                <Input label="Last Name" placeholder="Rivera" />
              </div>
              <Input label="Email" type="email" placeholder="jamie@trailmail.com" />
              <Input label="Phone Number" type="tel" placeholder="+1 (555) 010-2025" />
            </section>

            <section className="space-y-4">
              <h2 className="text-2xl font-semibold">Shipping Address</h2>
              <Input label="Street Address" placeholder="456 Forest Lane" />
              <div className="grid gap-5 md:grid-cols-3">
                <Input label="City" placeholder="Cascade" />
                <Input label="State/Province" placeholder="WA" />
                <Input label="ZIP / Postal Code" placeholder="98101" />
              </div>
            </section>

            <section className="space-y-4">
              <h2 className="text-2xl font-semibold">Payment Method</h2>
              <div className="grid gap-4 md:grid-cols-2">
                <PaymentOption label="SolarPay" description="Offset with carbon credits" />
                <PaymentOption label="Eco Card" description="Earn climate rewards" />
              </div>
              <Input label="Card Number" placeholder="4242 4242 4242 4242" />
              <div className="grid gap-5 md:grid-cols-3">
                <Input label="Expiry" placeholder="12/27" />
                <Input label="CVC" placeholder="123" />
                <Input label="Postal Code" placeholder="98101" />
              </div>
            </section>

            <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
              <p className="text-sm text-emerald-100/70">
                By placing this mock order you acknowledge this interface is simulation only.
              </p>
              <button
                type="button"
                className="neon-cta rounded-full px-8 py-3 text-sm font-semibold uppercase tracking-[0.35em]"
              >
                Confirm Mock Payment
              </button>
            </div>
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
}

const Input = ({ label, placeholder, type = 'text' }: InputProps) => (
  <label className="block space-y-2 text-sm">
    <span className="uppercase tracking-[0.3em] text-emerald-200/70">{label}</span>
    <input
      type={type}
      placeholder={placeholder}
      className="w-full rounded-2xl border border-white/10 bg-[#02120D] px-4 py-3 text-emerald-100 focus:border-emerald-300/60 focus:outline-none focus:ring-2 focus:ring-emerald-300/30"
    />
  </label>
);

interface PaymentOptionProps {
  label: string;
  description: string;
}

const PaymentOption = ({ label, description }: PaymentOptionProps) => (
  <button
    type="button"
    className="rounded-2xl border border-white/10 bg-[#02120D] px-6 py-4 text-left transition hover:border-emerald-300/40"
  >
    <p className="text-sm font-semibold uppercase tracking-[0.3em]">{label}</p>
    <p className="mt-2 text-xs text-emerald-100/70">{description}</p>
  </button>
);

export default Payment;
