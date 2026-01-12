import { Link } from 'react-router-dom';
import { useEffect, useState } from 'react';
import NavBar from '../components/NavBar';
import Footer from '../components/Footer';
import api from '../services/api';
import { assetPath } from '../utils/assetPath';

interface CartProductVariant {
  id: number;
  sku: string;
  price: number;
  product: {
    id: number;
    name: string;
    slug: string;
  };
}

interface CartItem {
  id: number;
  quantity: number;
  price_at_time: number;
  product_variant?: CartProductVariant;
}

interface CartResponse {
  id: number;
  items: CartItem[];
  subtotal: number;
  item_count: number;
}

interface CartTotals {
  item_count: number;
  subtotal: number;
  tax_amount: number;
  shipping_amount: number;
  total_amount: number;
  currency: string;
}

const Cart = () => {
  const [cart, setCart] = useState<CartResponse | null>(null);
  const [totals, setTotals] = useState<CartTotals | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const loadCart = async () => {
      setLoading(true);
      setError(null);
      try {
        const [cartResponse, totalsResponse] = await Promise.all([
          api.get('/api/cart'),
          api.get('/api/cart/totals'),
        ]);

        if (cartResponse.data?.success) {
          setCart(cartResponse.data.data);
        } else {
          setError(cartResponse.data?.message ?? 'Unable to load cart.');
        }

        if (totalsResponse.data?.success) {
          setTotals(totalsResponse.data.data);
        }
      } catch (err) {
        console.error('Failed to load cart', err);
        setError('We could not reach the cart service. Please try again later.');
      } finally {
        setLoading(false);
      }
    };

    loadCart();
  }, []);

  const subtotal = Number(totals?.subtotal ?? cart?.subtotal ?? 0);
  const shipping = Number(totals?.shipping_amount ?? 0);
  const tax = Number(totals?.tax_amount ?? 0);
  const total = Number(totals?.total_amount ?? subtotal + shipping + tax);

  return (
    <div className="min-h-screen bg-[#031410] text-emerald-100">
      <NavBar />
      <main className="pt-28 pb-16">
        <section className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
          <header className="space-y-3">
            <p className="inline-flex items-center rounded-full border border-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.35em] text-emerald-100/70">
              Your Gear Cart
            </p>
            <h1 className="text-4xl md:text-5xl font-semibold">
              Ready for the next expedition?
            </h1>
            <p className="max-w-2xl text-emerald-100/70">
              Review your eco-engineered essentials before heading to our mock payment portal.
            </p>
          </header>

          <div className="grid gap-8 lg:grid-cols-[2fr_1fr]">
            <div className="space-y-4">
              {loading ? (
                <div className="rounded-[28px] border border-white/10 bg-white/5 p-10 text-center">
                  <p className="text-sm uppercase tracking-[0.35em] text-emerald-200/70">
                    Loading cart…
                  </p>
                </div>
              ) : error ? (
                <div className="rounded-[28px] border border-red-500/30 bg-red-500/10 p-6 text-center text-sm text-red-200">
                  {error}
                </div>
              ) : cart && cart.items.length > 0 ? (
                cart.items.map((item) => (
                  <div
                    key={item.id}
                    className="flex flex-col md:flex-row md:items-center gap-6 rounded-[28px] border border-white/10 bg-white/5 p-6 backdrop-blur-2xl shadow-[0_25px_70px_rgba(0,0,0,0.35)]"
                  >
                    <div className="relative h-32 w-full md:w-32 overflow-hidden rounded-2xl">
                      <img
                        src={assetPath('images/aurora-solar-backpack.jpg')}
                        alt={item.product_variant?.product.name ?? 'Product image'}
                        className="h-full w-full object-cover"
                      />
                    </div>
                    <div className="flex-1 space-y-2">
                      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                        <div>
                          <p className="text-xs uppercase tracking-[0.3em] text-emerald-200/70">
                            SKU: {item.product_variant?.sku ?? 'N/A'}
                          </p>
                          <h2 className="text-xl font-semibold">
                            {item.product_variant?.product.name ?? 'Weekender Gear'}
                          </h2>
                        </div>
                        <p className="text-sm font-semibold uppercase tracking-[0.35em] text-emerald-100">
                          ${Number(item.price_at_time ?? item.product_variant?.price ?? 0).toFixed(2)}
                        </p>
                      </div>
                      <p className="text-xs uppercase tracking-[0.3em] text-emerald-200/80">
                        Quantity: {item.quantity}
                      </p>
                    </div>
                  </div>
                ))
              ) : (
                <div className="rounded-[28px] border border-white/10 bg-white/5 p-10 text-center">
                  <p className="text-sm uppercase tracking-[0.35em] text-emerald-200/70">
                    Your cart is empty. Explore our products to add gear.
                  </p>
                </div>
              )}
            </div>

            <aside className="rounded-[32px] border border-white/10 bg-white/5 p-8 backdrop-blur-2xl shadow-[0_25px_70px_rgba(0,0,0,0.35)] space-y-6">
              <h2 className="text-2xl font-semibold">Order Summary</h2>
              <div className="space-y-3 text-sm text-emerald-100/80">
                <SummaryRow label="Subtotal" value={`$${subtotal.toFixed(2)}`} />
                <SummaryRow
                  label="Shipping"
                  value={shipping === 0 ? 'Complimentary' : `$${shipping.toFixed(2)}`}
                />
                <SummaryRow label="Estimated Tax" value={`$${tax.toFixed(2)}`} />
              </div>
              <div className="flex items-center justify-between border-t border-white/10 pt-4">
                <p className="text-sm uppercase tracking-[0.35em] text-emerald-200/80">Total</p>
                <p className="text-2xl font-semibold text-emerald-100">${total.toFixed(2)}</p>
              </div>
              <Link
                to="/payment"
                className="block text-center neon-cta rounded-full px-6 py-3 text-sm font-semibold uppercase tracking-[0.35em]"
              >
                Proceed to Mock Payment
              </Link>
              <p className="text-xs text-emerald-100/60">
                This checkout is a conceptual demo. No real transactions are processed.
              </p>
            </aside>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

interface SummaryRowProps {
  label: string;
  value: string;
}

const SummaryRow = ({ label, value }: SummaryRowProps) => (
  <div className="flex items-center justify-between">
    <p>{label}</p>
    <p className="font-semibold text-emerald-100">{value}</p>
  </div>
);

export default Cart;
