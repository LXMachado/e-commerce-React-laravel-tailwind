import NavBar from '../components/NavBar';
import Hero from '../components/Hero';
import Metrics from '../components/Metrics';
import Features from '../components/Features';
import Testimonials from '../components/Testimonials';
import FAQ from '../components/FAQ';
import Newsletter from '../components/Newsletter';
import Footer from '../components/Footer';
import ChatWidget from '../components/ChatWidget';

const Home = () => {
  return (
    <div className="min-h-screen bg-transparent text-emerald-100">
      <NavBar />
      <main>
        <Hero />
        <Metrics />
        <Features />
        <Testimonials />
        <FAQ />
        <Newsletter />
      </main>
      <Footer />
      <ChatWidget />
    </div>
  );
};

export default Home;
