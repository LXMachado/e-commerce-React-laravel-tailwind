import { Link } from 'react-router-dom';
import { missionText } from '../mockData';
import { assetPath } from '../utils/assetPath';

const Mission = () => {
  return (
    <section className="relative py-24 overflow-hidden">
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_bottom_left,_rgba(58,240,124,0.1),_transparent_55%)]" />
      <div className="absolute inset-0 bg-gradient-to-t from-[#030d0a] via-transparent to-transparent" />
      <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 lg:grid-cols-[1.1fr_0.9fr] gap-14 items-center">
          <div className="space-y-8">
            <span className="inline-flex items-center gap-2 rounded-full border border-emerald-200/40 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.35em] text-emerald-100/80">
              Our Mission
            </span>
            <h2 className="text-4xl md:text-5xl font-semibold text-emerald-100 leading-tight">
              {missionText.title}
            </h2>
            <p className="text-lg md:text-xl text-emerald-100/80 leading-relaxed">
              {missionText.description}
            </p>
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm uppercase tracking-[0.35em] text-emerald-200/70">
              {['Circular Design', 'Climate Positive', 'Future Ready'].map((item) => (
                <div key={item} className="rounded-2xl border border-emerald-200/30 bg-white/5 px-6 py-3 text-center">
                  {item}
                </div>
              ))}
            </div>
            <Link
              to="/about"
              className="inline-flex items-center gap-3 neon-cta rounded-full px-10 py-3 text-sm font-semibold uppercase tracking-[0.4em]"
            >
              {missionText.button}
            </Link>
          </div>
          <div className="relative">
            <div className="absolute -inset-10 rounded-[40px] bg-gradient-to-br from-emerald-300/20 via-transparent to-transparent blur-3xl" />
            <div className="relative frosted-card rounded-[40px] overflow-hidden">
              <img
                src={assetPath('images/sequoia-lantern.jpg')}
                alt="Weekender lantern illuminating a nighttime basecamp"
                className="w-full h-[420px] object-cover"
              />
              <div className="absolute inset-0 bg-gradient-to-tr from-[#031410] via-transparent to-transparent" />
              <div className="absolute inset-x-0 bottom-0 p-8">
                <div className="rounded-3xl border border-emerald-200/30 bg-white/10 px-6 py-5 backdrop-blur-xl">
                  <p className="text-xs uppercase tracking-[0.4em] text-emerald-200/70">
                    2025 Impact Trajectory
                  </p>
                  <div className="mt-3 text-emerald-100">
                    <p className="text-lg font-semibold">Net Positive Explorer Network</p>
                    <p className="text-sm text-emerald-100/80">
                      1 kit sold funds 3 hours of wilderness restoration with our climate partners.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default Mission;
