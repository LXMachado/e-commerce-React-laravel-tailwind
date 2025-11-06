import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import NavBar from '../components/NavBar';
import Footer from '../components/Footer';
import { useAuth } from '../hooks/useAuth';
import api from '../services/api';
import type { ApiProduct } from '../types/product';
import { assetPath } from '../utils/assetPath';

interface OrderItem {
  id: number;
  quantity: number;
  line_total: number;
  product_variant?: {
    id: number;
    sku: string;
    product: {
      id: number;
      name: string;
      slug: string;
    };
  };
}

interface Order {
  id: number;
  order_number: string;
  status: string;
  total_amount: number;
  currency: string;
  created_at: string;
  items: OrderItem[];
}

interface OrderStats {
  total_orders: number;
  total_spent: number;
  pending_orders: number;
  completed_orders: number;
  cancelled_orders: number;
}

const Dashboard = () => {
  const { user, loading: authLoading } = useAuth();
  const [orders, setOrders] = useState<Order[]>([]);
  const [stats, setStats] = useState<OrderStats | null>(null);
  const [recommended, setRecommended] = useState<ApiProduct[]>([]);
  const [ordersLoading, setOrdersLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const loadRecommended = async () => {
      try {
        const response = await api.get('/api/catalog/products', { params: { per_page: 6 } });
        if (response.data?.success) {
          setRecommended(response.data.data?.data ?? []);
        }
      } catch (err) {
        console.error('Failed to load recommended products', err);
      }
    };

    loadRecommended();
  }, []);

  useEffect(() => {
    if (!user) {
      setOrders([]);
      setStats(null);
      return;
    }

    const loadOrders = async () => {
      setOrdersLoading(true);
      setError(null);
      try {
        const [ordersResponse, statsResponse] = await Promise.all([
          api.get('/api/orders', { params: { per_page: 5 } }),
          api.get('/api/orders/stats'),
        ]);

        if (ordersResponse.data?.success) {
          setOrders(ordersResponse.data.data?.data ?? []);
        } else {
          setError(ordersResponse.data?.message ?? 'Unable to load orders.');
        }

        if (statsResponse.data?.success) {
          setStats(statsResponse.data.data);
        }
      } catch (err) {
        console.error('Failed to load account data', err);
        setError('We could not reach the account services. Please try again later.');
      } finally {
        setOrdersLoading(false);
      }
    };

    loadOrders();
  }, [user]);

  const metricCards = useMemo(
    () => [
      {
        label: 'Total Orders',
        value: stats ? stats.total_orders : orders.length,
        suffix: '',
      },
      {
        label: 'Total Spent',
        value: stats ? `$${Number(stats.total_spent ?? 0).toFixed(2)}` : '$0.00',
        suffix: '',
      },
      {
        label: 'Pending Orders',
        value: stats ? stats.pending_orders : 0,
        suffix: '',
      },
      {
        label: 'Completed Orders',
        value: stats ? stats.completed_orders : 0,
        suffix: '',
      },
    ],
    [orders.length, stats]
  );

  const recentOrders = orders.slice(0, 3);

  return (
    <div className="min-h-screen bg-[#031410] text-emerald-100">
      <NavBar />
      <main className="pt-28 pb-16">
        <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
          <header className="space-y-4">
            <p className="inline-flex items-center rounded-full border border-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.35em] text-emerald-100/70">
              Account Overview
            </p>
            <h1 className="text-4xl md:text-5xl font-semibold">
              {user ? `Welcome back, ${user.name}` : 'Sign in to track your adventures.'}
            </h1>
            <p className="max-w-2xl text-emerald-100/70">
              Monitor your regenerative impact, track recent orders, and explore the latest eco-tech that keeps your basecamp powered.
            </p>
          </header>

          <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
            {metricCards.map((stat) => (
              <div
                key={stat.label}
                className="rounded-3xl border border-white/10 bg-white/5 px-6 py-6 backdrop-blur-2xl shadow-[0_25px_70px_rgba(0,0,0,0.35)]"
              >
                <p className="text-xs uppercase tracking-[0.35em] text-emerald-200/70">
                  {stat.label}
                </p>
                <p className="mt-3 text-3xl font-semibold text-emerald-100">
                  {stat.value}
                  {stat.suffix}
                </p>
              </div>
            ))}
          </div>

          <div className="grid gap-8 lg:grid-cols-2">
            <div className="rounded-[32px] border border-white/10 bg-white/5 p-8 backdrop-blur-2xl shadow-[0_25px_70px_rgba(0,0,0,0.35)]">
              <h2 className="text-2xl font-semibold">Recent Orders</h2>
              <p className="mt-2 text-sm text-emerald-100/70">
                Tracking your latest gear requests and their journey to your trailhead.
              </p>
              <div className="mt-6 space-y-4">
                {authLoading || ordersLoading ? (
                  <div className="rounded-2xl border border-white/10 bg-white/5 px-4 py-6 text-center text-sm text-emerald-100/70">
                    Loading recent orders…
                  </div>
                ) : !user ? (
                  <div className="rounded-2xl border border-white/10 bg-white/5 px-4 py-6 text-center text-sm text-emerald-100/70">
                    <Link to="/login" className="text-emerald-200 underline">
                      Sign in
                    </Link>{' '}
                    to view your orders.
                  </div>
                ) : error ? (
                  <div className="rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-6 text-center text-sm text-red-200">
                    {error}
                  </div>
                ) : recentOrders.length > 0 ? (
                  recentOrders.map((order) => (
                    <div
                      key={order.id}
                      className="flex items-center justify-between rounded-2xl border border-white/10 bg-white/5 px-4 py-3"
                    >
                      <div>
                        <p className="text-sm font-semibold uppercase tracking-[0.25em]">
                          {order.order_number}
                        </p>
                        <p className="text-sm text-emerald-100/70">
                          {order.items[0]?.product_variant?.product.name ?? 'Weekender Gear'}
                        </p>
                      </div>
                      <div className="text-right">
                        <p className="text-xs uppercase tracking-[0.3em] text-emerald-200/80">
                          {order.status}
                        </p>
                        <p className="text-xs text-emerald-100/60">
                          {new Date(order.created_at).toLocaleDateString()}
                        </p>
                      </div>
                    </div>
                  ))
                ) : (
                  <div className="rounded-2xl border border-white/10 bg-white/5 px-4 py-6 text-center text-sm text-emerald-100/70">
                    No orders yet—your trail kit is waiting.
                  </div>
                )}
              </div>
            </div>

            <div className="rounded-[32px] border border-white/10 bg-white/5 p-8 backdrop-blur-2xl shadow-[0_25px_70px_rgba(0,0,0,0.35)]">
              <h2 className="text-2xl font-semibold">Your Eco Impact</h2>
              <p className="mt-2 text-sm text-emerald-100/70">
                Snapshot of the positive footprint your purchases have delivered to protected trails.
              </p>
              <div className="mt-6 space-y-4">
                <ImpactRow label="Expeditions Powered" value={`${stats?.completed_orders ?? 0} journeys`} />
                <ImpactRow label="Pending Deployments" value={`${stats?.pending_orders ?? 0} in motion`} />
                <ImpactRow label="Carbon Neutral Orders" value={`${stats?.total_orders ?? 0}`} />
                <ImpactRow label="Lifetime Spend" value={`$${Number(stats?.total_spent ?? 0).toFixed(2)}`} />
              </div>
            </div>
          </div>

          <div>
            <h2 className="text-2xl font-semibold mb-6">Gear You Might Like</h2>
            <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
              {recommended.slice(0, 3).map((product) => {
                const price = product.primary_variant?.price ?? product.price;
                const image =
                  product.image_url ??
                  product.primary_variant?.image_url ??
                  assetPath('images/aurora-solar-backpack.jpg');
                const category =
                  product.categories && product.categories.length > 0
                    ? product.categories[0].name
                    : 'Gear';

                return (
                  <div
                    key={product.id}
                    className="rounded-[28px] border border-white/10 bg-white/5 p-6 backdrop-blur-2xl shadow-[0_25px_70px_rgba(0,0,0,0.35)]"
                  >
                    <div className="relative h-40 overflow-hidden rounded-2xl mb-5">
                      <img src={image} alt={product.name} className="h-full w-full object-cover" />
                      <div className="absolute inset-0 bg-gradient-to-t from-[#031410] via-transparent to-transparent" />
                    </div>
                    <p className="text-xs uppercase tracking-[0.3em] text-emerald-200/70">
                      {category}
                    </p>
                    <h3 className="mt-2 text-xl font-semibold">{product.name}</h3>
                    {product.short_description && (
                      <p className="mt-2 text-sm text-emerald-100/75">{product.short_description}</p>
                    )}
                    <p className="mt-3 text-sm font-semibold uppercase tracking-[0.35em] text-emerald-100">
                      ${Number(price ?? 0).toFixed(2)}
                    </p>
                  </div>
                );
              })}
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

interface ImpactRowProps {
  label: string;
  value: string;
}

const ImpactRow = ({ label, value }: ImpactRowProps) => (
  <div className="flex items-center justify-between rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
    <p className="text-xs uppercase tracking-[0.3em] text-emerald-200/80">{label}</p>
    <p className="text-sm font-semibold text-emerald-100">{value}</p>
  </div>
);

export default Dashboard;
