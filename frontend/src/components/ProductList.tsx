import { useEffect, useMemo, useState } from 'react';
import ProductCard from './ProductCard';
import api from '../services/api';
import type { ApiProduct } from '../types/product';

const ProductList = () => {
  const [products, setProducts] = useState<ApiProduct[]>([]);
  const [activeFilter, setActiveFilter] = useState<string>('All');
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const loadProducts = async () => {
      setLoading(true);
      try {
        const response = await api.get('/api/catalog/products', {
          params: { per_page: 50 },
        });

        if (response.data?.success) {
          const records = response.data.data?.data ?? [];
          setProducts(records);
        } else {
          setError(response.data?.message ?? 'Unable to load products.');
        }
      } catch (err) {
        console.error('Failed to fetch products', err);
        setError('Unable to reach the product catalog. Please try again later.');
      } finally {
        setLoading(false);
      }
    };

    loadProducts();
  }, []);

  const categories = useMemo(
    () => {
      const allCategories = new Set<string>();
      products.forEach((product) => {
        product.categories?.forEach((category) => allCategories.add(category.name));
      });
      return ['All', ...Array.from(allCategories)];
    },
    [products]
  );

  const filteredProducts = useMemo(
    () =>
      activeFilter === 'All'
        ? products
        : products.filter((product) =>
            product.categories?.some((category) => category.name === activeFilter)
          ),
    [activeFilter, products]
  );

  const stats = [
    { label: 'Carbon Offset', value: '120t' },
    { label: 'Trees Replanted', value: '25k' },
    { label: 'Adventure Lab Field Tests', value: '312' },
  ];

  return (
    <section className="relative py-24">
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(58,240,124,0.1),_transparent_50%)]" />
      <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-16 space-y-6">
          <span className="inline-flex items-center justify-center rounded-full border border-emerald-200/30 bg-white/5 px-5 py-2 text-xs font-semibold uppercase tracking-[0.4em] text-emerald-100">
            Featured Collection
          </span>
          <h2 className="text-4xl md:text-5xl font-semibold text-emerald-100">Featured Products</h2>
          <p className="text-lg md:text-xl text-emerald-100/80 max-w-3xl mx-auto">
            Explore eco-forward tech essentials engineered for lightweight micro-camping, off-grid workstations, and regenerative adventures.
          </p>
        </div>
        <div className="flex flex-wrap justify-center gap-3 mb-12">
          {categories.map((category) => {
            const isActive = category === activeFilter;
            return (
              <button
                key={category}
                onClick={() => setActiveFilter(category)}
                className={`rounded-full border px-5 py-2 text-xs font-semibold uppercase tracking-[0.35em] transition ${
                  isActive
                    ? 'border-emerald-200/60 bg-emerald-300/20 text-emerald-100'
                    : 'border-white/10 bg-white/5 text-emerald-100/70 hover:border-emerald-200/40 hover:text-emerald-100'
                }`}
                type="button"
              >
                {category}
              </button>
            );
          })}
        </div>
        {loading ? (
          <div className="flex items-center justify-center py-24">
            <span className="text-sm uppercase tracking-[0.4em] text-emerald-200/70">
              Loading catalog…
            </span>
          </div>
        ) : error ? (
          <div className="rounded-[28px] border border-red-500/30 bg-red-500/10 p-6 text-center text-sm text-red-200">
            {error}
          </div>
        ) : filteredProducts.length > 0 ? (
          <div className="grid grid-cols-1 gap-8 md:grid-cols-2 xl:grid-cols-3">
            {filteredProducts.map((product) => (
              <ProductCard key={product.id} product={product} />
            ))}
          </div>
        ) : (
          <div className="rounded-[28px] border border-white/10 bg-white/5 p-10 text-center">
            <p className="text-sm uppercase tracking-[0.35em] text-emerald-200/70">
              No products available for this filter just yet.
            </p>
          </div>
        )}
        <div className="mt-16 grid grid-cols-1 gap-6 rounded-[28px] border border-white/10 bg-white/5 p-8 backdrop-blur-2xl shadow-[0_25px_70px_rgba(0,0,0,0.35)] md:grid-cols-3">
          {stats.map((stat) => (
            <div key={stat.label} className="text-center space-y-2">
              <div className="text-3xl font-semibold text-emerald-100">{stat.value}</div>
              <div className="text-xs uppercase tracking-[0.35em] text-emerald-200/70">
                {stat.label}
              </div>
            </div>
          ))}
        </div>
        <div className="text-center mt-14">
          <a
            href="#"
            className="inline-flex items-center gap-3 neon-cta rounded-full px-10 py-3 text-sm font-semibold uppercase tracking-[0.4em]"
          >
            View All Products →
          </a>
        </div>
      </div>
    </section>
  );
};

export default ProductList;
