import NavBar from '../components/NavBar';
import ProductList from '../components/ProductList';
import Footer from '../components/Footer';

const Products = () => {
  return (
    <div className="min-h-screen bg-transparent text-emerald-100">
      <NavBar />
      <main className="pt-24">
        <ProductList />
      </main>
      <Footer />
    </div>
  );
};

export default Products;
