import NavBar from '../components/NavBar';
import Footer from '../components/Footer';
import { features } from '../mockData';
import { SparklesIcon, BoltIcon, ShieldCheckIcon } from '@heroicons/react/24/outline';
import { assetPath } from '../utils/assetPath';

const iconMap = {
  leaf: SparklesIcon,
  bolt: BoltIcon,
  shield: ShieldCheckIcon,
};

const About = () => {
  return (
    <div className="min-h-screen bg-transparent text-emerald-100">
      <NavBar />
      <main className="pt-24">
        <section className="relative py-24">
          <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(0,255,200,0.08),_transparent_55%)]" />
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div className="text-center mb-20 space-y-6">
              <span className="inline-flex items-center justify-center gap-2 rounded-full border border-emerald-200/30 bg-white/5 px-5 py-2 text-xs font-semibold uppercase tracking-[0.4em] text-emerald-100">
                <SparklesIcon className="h-4 w-4" />
                About Weekender
              </span>
              <h1 className="text-4xl md:text-5xl font-semibold text-emerald-100">
                Designing Regenerative Tech for Nomadic Weekends
              </h1>
              <p className="text-lg md:text-xl text-emerald-100/80 max-w-3xl mx-auto">
                Weekender delivers high-quality sustainable tech gear for micro-camping and outdoor studios, blending modern innovation with deep respect for the wilderness.
              </p>
            </div>
            <div className="grid grid-cols-1 lg:grid-cols-[1.05fr_0.95fr] gap-14 items-center mb-24">
              <div className="space-y-6">
                <h2 className="text-3xl md:text-4xl font-semibold text-emerald-100">Our Story</h2>
                <p className="text-emerald-100/80 leading-relaxed">
                  Founded by outdoor enthusiasts and tech innovators, Weekender was born from a desire to amplify the camping experience while minimizing environmental impact. We prototype with recycled composites, solar fabrics, and biodegradable polymers so our products journey lightly.
                </p>
                <p className="text-emerald-100/80 leading-relaxed">
                  We believe technology and nature can coexist harmoniously. Each release is field-tested in alpine, desert, and coastal climates to ensure gear is durable, energy-efficient, and regenerative.
                </p>
                <div className="rounded-[32px] border border-emerald-200/30 bg-white/5 backdrop-blur-2xl p-8 shadow-[0_25px_70px_rgba(0,0,0,0.35)]">
                  <p className="text-sm uppercase tracking-[0.35em] text-emerald-200/70">
                    Crafted for explorers
                  </p>
                  <p className="mt-3 text-lg text-emerald-100">
                    Every kit is built with modular repairability and lifetime take-back programs to close the loop.
                  </p>
                </div>
              </div>
              <div className="relative">
                <div className="absolute -inset-10 rounded-[40px] bg-gradient-to-br from-emerald-300/20 via-transparent to-transparent blur-3xl" />
                <div className="relative frosted-card rounded-[40px] overflow-hidden">
                  <img
                    src={assetPath('images/alta-ridge-tent.jpg')}
                    alt="Alta Ridge tent capturing solar rays at golden hour"
                    className="w-full h-[420px] object-cover"
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-[#031410] via-transparent to-transparent" />
                  <div className="absolute inset-x-0 bottom-0 p-8">
                    <div className="rounded-3xl border border-emerald-200/30 bg-white/10 px-6 py-5 backdrop-blur-xl">
                      <p className="text-xs uppercase tracking-[0.4em] text-emerald-200/70">
                        Expedition Tested
                      </p>
                      <p className="mt-3 text-emerald-100">
                        Beta testers include overland photographers, climate scientists, and off-grid makers documenting regenerative journeys.
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div className="mb-24">
              <h2 className="text-3xl md:text-4xl font-semibold text-center text-emerald-100 mb-12">
                Our Impact
              </h2>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                {[
                  { stat: '10K+', label: 'Products Sold' },
                  { stat: '5K', label: 'Happy Explorers' },
                  { stat: '1T', label: 'Plastic Saved' },
                ].map((item) => (
                  <div
                    key={item.label}
                    className="rounded-[28px] border border-emerald-200/30 bg-white/5 px-10 py-12 text-center shadow-[0_25px_70px_rgba(0,0,0,0.35)]"
                  >
                    <div className="text-4xl md:text-5xl font-semibold text-emerald-100 mb-2">
                      {item.stat}
                    </div>
                    <p className="text-sm uppercase tracking-[0.35em] text-emerald-200/70">
                      {item.label}
                    </p>
                  </div>
                ))}
              </div>
            </div>
            <div>
              <h2 className="text-3xl md:text-4xl font-semibold text-center text-emerald-100 mb-12">
                Why Choose Us
              </h2>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                {features.map((feature) => {
                  const IconComponent = iconMap[feature.icon as keyof typeof iconMap];
                  return (
                    <div
                      key={feature.id}
                      className="group rounded-[28px] border border-white/10 bg-white/5 p-10 text-center backdrop-blur-2xl shadow-[0_25px_70px_rgba(0,0,0,0.35)] transition-transform duration-500 hover:-translate-y-2 hover:shadow-[0_35px_80px_rgba(58,240,124,0.25)]"
                    >
                      <div className="mb-5 flex justify-center">
                        <div className="relative flex h-16 w-16 items-center justify-center rounded-3xl bg-gradient-to-br from-emerald-400/35 to-teal-300/35 text-emerald-100">
                          <IconComponent className="h-8 w-8" />
                          <span className="absolute -inset-1 rounded-3xl border border-emerald-200/40 opacity-40" />
                        </div>
                      </div>
                      <h3 className="text-xl font-semibold text-emerald-100 mb-3">
                        {feature.title}
                      </h3>
                      <p className="text-emerald-100/75 leading-relaxed">
                        {feature.description}
                      </p>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default About;
