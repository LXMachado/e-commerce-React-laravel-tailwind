import { useState } from 'react';
import { testimonials } from '../mockData';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/react/24/outline';

const Testimonials = () => {
  const [currentIndex, setCurrentIndex] = useState(0);

  const nextTestimonial = () => {
    setCurrentIndex((prev) => (prev + 1) % testimonials.length);
  };

  const prevTestimonial = () => {
    setCurrentIndex((prev) => (prev - 1 + testimonials.length) % testimonials.length);
  };

  return (
    <section className="relative py-24">
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_bottom_right,_rgba(58,240,124,0.1),_transparent_55%)]" />
      <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-16">
          <span className="inline-flex items-center justify-center rounded-full border border-emerald-200/40 bg-white/5 px-5 py-2 text-xs font-semibold uppercase tracking-[0.35em] text-emerald-100">
            Customer Stories
          </span>
          <h2 className="text-4xl md:text-5xl font-bold text-emerald-100 mt-4">
            What Our Community Says
          </h2>
        </div>
        <div className="relative">
          <div className="testimonial-card max-w-4xl mx-auto">
            <div className="flex items-center mb-4">
              <img
                src={testimonials[currentIndex].image}
                alt={testimonials[currentIndex].name}
                className="w-12 h-12 rounded-full mr-4"
              />
              <div>
                <h3 className="text-emerald-100 font-semibold">
                  {testimonials[currentIndex].name}
                </h3>
                <p className="text-emerald-100/70 text-sm">
                  {testimonials[currentIndex].role}
                </p>
              </div>
              <div className="ml-auto flex">
                {[...Array(testimonials[currentIndex].rating)].map((_, i) => (
                  <span key={i} className="text-yellow-400">★</span>
                ))}
              </div>
            </div>
            <p className="text-emerald-100/80 italic">
              "{testimonials[currentIndex].content}"
            </p>
          </div>
          <button
            onClick={prevTestimonial}
            className="absolute left-0 top-1/2 transform -translate-y-1/2 bg-white/10 p-2 rounded-full"
          >
            <ChevronLeftIcon className="w-6 h-6 text-emerald-100" />
          </button>
          <button
            onClick={nextTestimonial}
            className="absolute right-0 top-1/2 transform -translate-y-1/2 bg-white/10 p-2 rounded-full"
          >
            <ChevronRightIcon className="w-6 h-6 text-emerald-100" />
          </button>
        </div>
      </div>
    </section>
  );
};

export default Testimonials;