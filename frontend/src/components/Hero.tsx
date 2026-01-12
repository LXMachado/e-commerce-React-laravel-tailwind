import { Link } from 'react-router-dom';
import { heroText } from '../mockData';
import { assetPath } from '../utils/assetPath';

const Hero = () => {
  return (
    <section className="relative overflow-hidden pt-32 pb-28">
      <div className="absolute inset-0">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(58,240,124,0.12),_transparent_60%)]" />
        <div className="absolute -top-48 right-0 h-[420px] w-[420px] rounded-full bg-emerald-500/20 blur-3xl animate-pulse" />
        <div className="absolute bottom-0 left-0 h-72 w-full bg-gradient-to-t from-[#030d0a] via-transparent to-transparent" />
      </div>
      <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-[1.1fr_0.9fr] gap-16 items-center">
        <div className="space-y-9">
          <span className="inline-flex items-center gap-2 rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.4em] text-emerald-100">
            {heroText.eyebrow}
          </span>
          <h1 className="text-4xl sm:text-6xl lg:text-7xl font-bold text-emerald-100 leading-tight">
            <span className="block text-white/90">{heroText.title}</span>
            <span className="block text-emerald-300 drop-shadow-[0_0_25px_rgba(58,240,124,0.35)]">
              {heroText.highlight}
            </span>
          </h1>
          <p className="max-w-2xl text-lg sm:text-xl text-emerald-100/80">
            {heroText.subtitle}
          </p>
          <div className="flex flex-col sm:flex-row items-center gap-4 sm:gap-6">
            <Link
              to="/products"
              className="neon-cta rounded-full px-10 py-3 text-sm font-semibold uppercase tracking-[0.4em]"
            >
              {heroText.button1}
            </Link>
            <Link
              to="/about"
              className="group rounded-full border border-white/15 bg-white/5 px-9 py-3 text-sm font-semibold uppercase tracking-[0.4em] text-emerald-100 transition hover:border-emerald-300/60 hover:bg-emerald-300/10"
            >
              <span className="flex items-center gap-3">
                {heroText.button2}
                <span className="h-px w-6 bg-gradient-to-r from-transparent via-emerald-200 to-emerald-200 transition-all group-hover:w-10" />
              </span>
            </Link>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4">
            {[
              { label: 'Solar-Powered Gear', value: '92% Less Waste' },
              { label: 'Smart Camp Tech', value: 'AI-Optimized' },
              { label: 'Trees Replanted', value: '25,000+' },
            ].map((item) => (
              <div key={item.label} className="glass-panel border-white/10 px-6 py-5 rounded-2xl">
                <p className="text-xs uppercase tracking-[0.35em] text-emerald-200/70 mb-2">
                  {item.label}
                </p>
                <p className="text-lg font-semibold text-emerald-100">{item.value}</p>
              </div>
            ))}
          </div>
        </div>
        <div className="relative">
          <div className="absolute -inset-6 rounded-[32px] bg-gradient-to-br from-emerald-400/20 via-transparent to-transparent blur-3xl" />
          <div className="relative frosted-card rounded-[32px] overflow-hidden">
            <video
              autoPlay
              muted
              loop
              className="h-[460px] w-full object-cover"
            >
              <source src={assetPath('images/hero-video.mp4')} type="video/mp4" />
              <img
                src={assetPath('images/aurora-solar-backpack.jpg')}
                alt="Aurora Solar Backpack staged at a woodland trailhead"
                className="h-[460px] w-full object-cover"
              />
            </video>
            <div className="absolute inset-0 bg-gradient-to-t from-[#061411] via-transparent to-transparent" />
            <div className="absolute bottom-0 left-0 right-0 p-8">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs uppercase tracking-[0.35em] text-emerald-200/70">
                    Flagship Gear
                  </p>
                  <p className="text-xl font-semibold text-emerald-100">Aurora Solar Backpack</p>
                </div>
                <div className="rounded-full border border-emerald-200/30 bg-emerald-300/20 px-4 py-1 text-xs uppercase tracking-[0.35em] text-emerald-100">
                  Carbon +ve
                </div>
              </div>
            </div>
          </div>
          <div className="absolute -bottom-10 right-4 w-56 rounded-3xl border border-emerald-200/40 bg-emerald-400/10 px-4 py-3 text-xs uppercase tracking-[0.35em] text-emerald-100 shadow-[0_20px_45px_rgba(58,240,124,0.25)] animate-[float_6s_ease-in-out_infinite]">
            Trail-Tested • Net Positive
          </div>
        </div>
      </div>
    </section>
  );
};

export default Hero;
