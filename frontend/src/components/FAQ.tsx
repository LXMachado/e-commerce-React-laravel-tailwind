import { useState } from 'react';
import { faqs } from '../mockData';
import { ChevronDownIcon } from '@heroicons/react/24/outline';

const FAQ = () => {
  const [openIndex, setOpenIndex] = useState<number | null>(null);

  const toggleFAQ = (index: number) => {
    setOpenIndex(openIndex === index ? null : index);
  };

  return (
    <section className="relative py-24">
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(0,255,200,0.08),_transparent_45%)]" />
      <div className="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-16">
          <span className="inline-flex items-center justify-center rounded-full border border-emerald-200/40 bg-white/5 px-5 py-2 text-xs font-semibold uppercase tracking-[0.35em] text-emerald-100">
            FAQ
          </span>
          <h2 className="text-4xl md:text-5xl font-bold text-emerald-100 mt-4">
            Frequently Asked Questions
          </h2>
        </div>
        <div>
          {faqs.map((faq, index) => (
            <div
              key={index}
              className={`faq-item ${openIndex === index ? 'open' : ''}`}
            >
              <div
                className="faq-question text-emerald-100"
                onClick={() => toggleFAQ(index)}
              >
                {faq.question}
                <ChevronDownIcon
                  className={`w-5 h-5 transition-transform ${
                    openIndex === index ? 'rotate-180' : ''
                  }`}
                />
              </div>
              <div className="faq-answer text-emerald-100/80">
                {faq.answer}
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

export default FAQ;
