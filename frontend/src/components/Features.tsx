import { Link } from 'react-router-dom';
import { features } from '../mockData';
import { SparklesIcon, BoltIcon, ShieldCheckIcon } from '@heroicons/react/24/outline';

const iconMap = {
  leaf: SparklesIcon,
  bolt: BoltIcon,
  shield: ShieldCheckIcon,
};

const Features = () => {
  return (
    <section className="relative py-24">
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(0,255,200,0.08),_transparent_45%)] pointer-events-none" />
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div className="text-center mb-16 space-y-6">
          <span className="inline-flex items-center justify-center rounded-full border border-emerald-200/40 bg-white/5 px-5 py-2 text-xs font-semibold uppercase tracking-[0.35em] text-emerald-100">
            Why Weekender
          </span>
          <h2 className="text-4xl md:text-5xl font-bold text-emerald-100">
            Sustainable Tech with Mountain-Grade Durability
          </h2>
          <p className="text-lg md:text-xl text-emerald-100/80 max-w-3xl mx-auto">
            Our eco-engineered gear merges advanced outdoor technology with regenerative materials so every micro-camping retreat leaves nature greener than before.
          </p>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {features.map((feature) => {
            const IconComponent = iconMap[feature.icon as keyof typeof iconMap];
            return (
              <div
                key={feature.id}
                className="group rounded-[28px] border border-white/10 bg-white/5 backdrop-blur-2xl p-10 text-center shadow-[0_25px_70px_rgba(0,0,0,0.35)] transition-transform duration-500 hover:-translate-y-2 hover:shadow-[0_35px_80px_rgba(58,240,124,0.25)]"
              >
                <div className="mb-6 flex items-center justify-center">
                  <div className="relative flex h-20 w-20 items-center justify-center rounded-3xl bg-gradient-to-br from-emerald-400/40 to-teal-300/40 text-emerald-100 transition-shadow group-hover:shadow-[0_0_35px_rgba(58,240,124,0.4)]">
                    <IconComponent className="h-10 w-10" />
                    <div className="absolute inset-0 rounded-3xl border border-emerald-200/40 opacity-40 group-hover:opacity-80 transition-opacity" />
                  </div>
                </div>
                <h3 className="text-2xl font-semibold text-emerald-100 mb-4">
                  {feature.title}
                </h3>
                <p className="text-emerald-100/75 mb-8 leading-relaxed">
                  {feature.description}
                </p>
                <Link
                  to="/about"
                  className="inline-flex items-center gap-3 rounded-full border border-emerald-200/40 bg-white/5 px-6 py-3 text-xs font-semibold uppercase tracking-[0.35em] text-emerald-100 transition hover:border-emerald-200/70 hover:bg-emerald-300/15"
                >
                  Learn More
                  <span className="h-px w-6 bg-gradient-to-r from-transparent via-emerald-200 to-emerald-200 transition-all group-hover:w-10" />
                </Link>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
};

export default Features;
