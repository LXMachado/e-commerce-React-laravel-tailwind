import { Link, useNavigate, useParams } from 'react-router-dom';
import { useEffect, useState } from 'react';
import NavBar from '../components/NavBar';
import Footer from '../components/Footer';
import api, { getCsrfCookie } from '../services/api';
import type { ApiProduct } from '../types/product';
import { assetPath } from '../utils/assetPath';

const ProductDetail = () => {
  const { productId } = useParams();
  const navigate = useNavigate();
  const [product, setProduct] = useState<ApiProduct | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);
  const [requestStatus, setRequestStatus] = useState<'idle' | 'adding' | 'success' | 'error'>(
    'idle'
  );

  useEffect(() => {
    const loadProduct = async () => {
      if (!productId) return;
      setLoading(true);
      setError(null);
      try {
        const response = await api.get(`/api/catalog/products/${productId}`);
        if (response.data?.success) {
          setProduct(response.data.data);
        } else {
          setError(response.data?.message ?? 'Unable to load product details.');
        }
      } catch (err) {
        console.error('Failed to load product', err);
        setError('We could not reach the product service. Please try again later.');
      } finally {
        setLoading(false);
      }
    };

    loadProduct();
  }, [productId]);

  const handleAddToCart = async () => {
    if (!product?.primary_variant?.id) {
      setRequestStatus('error');
      return;
    }

    try {
      setRequestStatus('adding');
      await getCsrfCookie();
      const response = await api.post('/api/cart/items', {
        product_variant_id: product.primary_variant.id,
        quantity: 1,
      });

      if (response.data?.success) {
        setRequestStatus('success');
        navigate('/cart', { replace: false });
      } else {
        setRequestStatus('error');
      }
    } catch (err) {
      console.error('Failed to add to cart', err);
      setRequestStatus('error');
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-[#031410] text-emerald-100">
        <NavBar />
        <main className="pt-28 pb-16">
          <section className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">
            <p className="inline-flex items-center justify-center rounded-full border border-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.35em] text-emerald-100/70">
              Product Lookup
            </p>
            <h1 className="text-4xl font-semibold">Loading your selected gear…</h1>
            <p className="text-sm text-emerald-100/70">
              Fetching live data from the Weekender API.
            </p>
          </section>
        </main>
        <Footer />
      </div>
    );
  }

  if (error || !product) {
    return (
      <div className="min-h-screen bg-[#031410] text-emerald-100">
        <NavBar />
        <main className="pt-28 pb-16">
          <section className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">
            <p className="inline-flex items-center justify-center rounded-full border border-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.35em] text-emerald-100/70">
              Product Lookup
            </p>
            <h1 className="text-4xl font-semibold">We couldn&apos;t locate that gear yet.</h1>
            <p className="text-sm text-emerald-100/70">
              {error ??
                'This item might have been retired from the catalog. Explore the latest drops instead.'}
            </p>
            <Link
              to="/products"
              className="inline-flex items-center justify-center neon-cta rounded-full px-8 py-3 text-sm font-semibold uppercase tracking-[0.35em]"
            >
              Back to Products
            </Link>
          </section>
        </main>
        <Footer />
      </div>
    );
  }

  const price = product.primary_variant?.price ?? product.price;
  const badges =
    product.attribute_values?.map((attribute) => `${attribute.attribute.name}: ${attribute.value}`) ??
    [];
  const heroImage =
    product.image_url ??
    product.primary_variant?.image_url ??
    assetPath('images/aurora-solar-backpack.jpg');
  const categoryLabel =
    product.categories && product.categories.length > 0 ? product.categories[0].name : 'Gear';

  return (
    <div className="min-h-screen bg-[#031410] text-emerald-100">
      <NavBar />
      <main className="pt-28 pb-16">
        <article className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid gap-10 lg:grid-cols-[1.1fr_0.9fr] items-center">
            <div className="relative rounded-[48px] border border-white/10 bg-white/5 p-6 backdrop-blur-2xl shadow-[0_35px_90px_rgba(0,0,0,0.35)] overflow-hidden">
              <img
                src={heroImage}
                alt={product.name}
                className="h-full w-full rounded-[40px] object-cover"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-[#031410] via-transparent to-transparent opacity-60" />
              <div className="absolute top-6 left-6 flex items-center gap-3">
                <span className="rounded-full border border-emerald-200/60 bg-[#031410]/80 px-4 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.35em] text-emerald-100">
                  {categoryLabel}
                </span>
                <span className="rounded-full border border-emerald-200/60 bg-emerald-400/20 px-4 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.35em] text-emerald-100">
                  Climate Positive
                </span>
              </div>
            </div>

            <div className="space-y-6">
              <h1 className="text-4xl md:text-5xl font-semibold">{product.name}</h1>
              {product.short_description && (
                <p className="text-xs uppercase tracking-[0.35em] text-emerald-200/70">
                  {product.short_description}
                </p>
              )}
              {product.description && (
                <p className="text-sm text-emerald-100/75 leading-relaxed">{product.description}</p>
              )}

              <div className="flex flex-wrap gap-2">
                {badges.map((badge) => (
                  <span
                    key={badge}
                    className="rounded-full border border-emerald-200/40 bg-white/5 px-4 py-1 text-[0.65rem] uppercase tracking-[0.3em] text-emerald-100/80"
                  >
                    {badge}
                  </span>
                ))}
              </div>

              <div className="rounded-[28px] border border-white/10 bg-white/5 p-6 backdrop-blur-2xl shadow-[0_25px_70px_rgba(0,0,0,0.35)] space-y-3">
                <p className="text-xs uppercase tracking-[0.35em] text-emerald-200/80">
                  Investment
                </p>
                <p className="text-3xl font-semibold text-emerald-100">
                  ${Number(price ?? 0).toFixed(2)}
                </p>
                <p className="text-xs text-emerald-100/60">
                  Includes lifetime repairs, solar recalibration service, and shared impact reporting.
                </p>
                <div className="flex flex-wrap items-center gap-3 pt-2">
                  <Link
                    to="#"
                    onClick={(event) => {
                      event.preventDefault();
                      if (requestStatus !== 'adding') {
                        void handleAddToCart();
                      }
                    }}
                    className="neon-cta rounded-full px-6 py-3 text-sm font-semibold uppercase tracking-[0.35em]"
                  >
                    {requestStatus === 'adding'
                      ? 'Adding...'
                      : product.primary_variant?.id
                        ? 'Add to Cart'
                        : 'Variant unavailable'}
                  </Link>
                  <Link
                    to="/products"
                    className="rounded-full border border-emerald-200/40 px-6 py-3 text-sm font-semibold uppercase tracking-[0.35em] text-emerald-100/80 hover:border-emerald-200/70 hover:text-emerald-100"
                  >
                    Continue Browsing
                  </Link>
                </div>
                {requestStatus === 'error' && (
                  <p className="text-xs text-red-300">
                    We couldn&apos;t add this item to your cart. Please try again later.
                  </p>
                )}
              </div>
            </div>
          </div>
        </article>
      </main>
      <Footer />
    </div>
  );
};

export default ProductDetail;
