import { Link } from 'react-router-dom';
import type { ApiProduct } from '../types/product';
import { assetPath } from '../utils/assetPath';

interface ProductCardProps {
  product: ApiProduct;
}

const ProductCard = ({ product }: ProductCardProps) => {
  const price = product.primary_variant?.price ?? product.price;
  const categoryLabel =
    product.categories && product.categories.length > 0 ? product.categories[0].name : 'Gear';
  const image =
    product.image_url ??
    product.primary_variant?.image_url ??
    assetPath('images/aurora-solar-backpack.jpg');

  return (
    <article className="group relative overflow-hidden rounded-[32px] border border-white/10 bg-white/5 backdrop-blur-2xl shadow-[0_25px_70px_rgba(0,0,0,0.35)] transition-transform duration-500 hover:-translate-y-2 hover:shadow-[0_40px_90px_rgba(58,240,124,0.2)]">
      <div className="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500">
        <div className="absolute -top-16 -right-10 h-40 w-40 rounded-full bg-gradient-to-br from-emerald-400/20 to-teal-300/30 blur-3xl" />
        <div className="absolute -bottom-12 -left-6 h-36 w-36 rounded-full bg-gradient-to-tr from-teal-300/20 to-emerald-400/20 blur-3xl" />
      </div>
      <div className="relative overflow-hidden">
        <div className="relative h-64">
          <img
            src={image}
            alt={product.name}
            className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
          />
          <div className="absolute inset-0 bg-gradient-to-t from-[#031410] via-transparent to-transparent" />
          <div className="absolute top-4 left-4 flex items-center gap-2">
            <span className="rounded-full border border-emerald-200/40 bg-[#031410]/80 px-3 py-1 text-[0.6rem] font-semibold uppercase tracking-[0.4em] text-emerald-100">
              {categoryLabel}
            </span>
            <span className="rounded-full border border-emerald-200/40 bg-emerald-400/10 px-3 py-1 text-[0.6rem] font-semibold uppercase tracking-[0.4em] text-emerald-100">
              ${Number(price ?? 0).toFixed(2)}
            </span>
          </div>
        </div>
      </div>
      <div className="relative p-8 space-y-5 text-emerald-100">
        <div>
          <h3 className="text-2xl font-semibold">{product.name}</h3>
          {product.short_description && (
            <p className="mt-2 text-sm uppercase tracking-[0.4em] text-emerald-200/70">
              {product.short_description}
            </p>
          )}
        </div>
        {product.description && (
          <p className="text-sm leading-relaxed text-emerald-100/75 line-clamp-4">
            {product.description}
          </p>
        )}
        {product.attribute_values && product.attribute_values.length > 0 && (
          <div className="flex flex-wrap gap-2">
            {product.attribute_values.slice(0, 4).map((attribute) => (
              <span
                key={attribute.id}
                className="rounded-full border border-emerald-200/40 bg-white/5 px-3 py-1 text-[0.65rem] uppercase tracking-[0.3em] text-emerald-100/80"
              >
                {attribute.attribute.name}: {attribute.value}
              </span>
            ))}
          </div>
        )}
        <div className="flex items-center justify-between pt-2">
          <span className="text-xs uppercase tracking-[0.35em] text-emerald-200/70">
            Climate Positive
          </span>
          <Link
            to={`/products/${product.id}`}
            className="group inline-flex items-center gap-3 rounded-full border border-emerald-200/40 bg-white/5 px-5 py-2 text-xs font-semibold uppercase tracking-[0.35em] text-emerald-100 transition hover:border-emerald-200/70 hover:bg-emerald-300/15"
          >
            View Details
            <span className="h-px w-6 bg-gradient-to-r from-transparent via-emerald-200 to-emerald-200 transition-all group-hover:w-10" />
          </Link>
        </div>
      </div>
    </article>
  );
};

export default ProductCard;
