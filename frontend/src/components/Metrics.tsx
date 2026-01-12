import { useState, useEffect } from 'react';
import { metrics } from '../mockData';

const Metrics = () => {
  const [counters, setCounters] = useState(metrics.map(() => 0));

  useEffect(() => {
    const timers = metrics.map((metric, index) => {
      const increment = metric.value / 100;
      let current = 0;
      return setInterval(() => {
        current += increment;
        if (current >= metric.value) {
          current = metric.value;
          clearInterval(timers[index]);
        }
        setCounters(prev => {
          const newCounters = [...prev];
          newCounters[index] = Math.floor(current);
          return newCounters;
        });
      }, 20);
    });

    return () => timers.forEach(clearInterval);
  }, []);

  return (
    <section className="relative py-24">
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_center,_rgba(0,255,200,0.08),_transparent_55%)]" />
      <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-16">
          <span className="inline-flex items-center justify-center rounded-full border border-emerald-200/40 bg-white/5 px-5 py-2 text-xs font-semibold uppercase tracking-[0.35em] text-emerald-100">
            Our Impact
          </span>
          <h2 className="text-4xl md:text-5xl font-bold text-emerald-100 mt-4">
            Powering Sustainability
          </h2>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
          {metrics.map((metric, index) => (
            <div key={metric.label} className="text-center">
              <div className="metrics-counter">
                {counters[index]}{metric.suffix}
              </div>
              <p className="text-emerald-100/70 mt-2 uppercase tracking-[0.35em] text-sm">
                {metric.label}
              </p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

export default Metrics;