# Weekender Frontend

A modern, eco-friendly e-commerce frontend for outdoor gear and sustainable technology products. Built with React, TypeScript, and Tailwind CSS, this application showcases solar-native gear designed for micro-expeditions and climate-positive adventures.

## 🚀 Features

- **Responsive Design**: Mobile-first approach with Tailwind CSS
- **TypeScript**: Full type safety throughout the application
- **React Router**: Client-side routing for seamless navigation
- **Component-Based Architecture**: Modular, reusable components
- **Eco-Friendly Theme**: Sustainable technology focus with solar-powered products
- **Mock Data Integration**: Comprehensive product catalog with detailed specifications

## 🛠️ Tech Stack

- **Frontend Framework**: React 19.1.1 with TypeScript
- **Build Tool**: Vite 7.1.7
- **Styling**: Tailwind CSS 3.4.17
- **Routing**: React Router DOM 7.9.4
- **Icons**: Heroicons React 2.2.0
- **Linting**: ESLint with TypeScript and Prettier integration
- **Development Tools**: Hot Module Replacement (HMR), TypeScript compilation

## 📁 Project Structure

```
weekender-frontend/
├── public/
│   ├── images/          # Product and testimonial images
│   └── vite.svg         # Vite logo
├── src/
│   ├── components/      # Reusable UI components
│   │   ├── ChatWidget.tsx
│   │   ├── FAQ.tsx
│   │   ├── Features.tsx
│   │   ├── Footer.tsx
│   │   ├── Hero.tsx
│   │   ├── Metrics.tsx
│   │   ├── Mission.tsx
│   │   ├── NavBar.tsx
│   │   ├── Newsletter.tsx
│   │   ├── ProductCard.tsx
│   │   ├── ProductList.tsx
│   │   └── Testimonials.tsx
│   ├── pages/           # Route-based page components
│   │   ├── About.tsx
│   │   ├── Contact.tsx
│   │   ├── Home.tsx
│   │   └── Products.tsx
│   ├── utils/           # Utility functions
│   │   └── assetPath.ts
│   ├── App.tsx          # Main application component
│   ├── index.css        # Global styles
│   ├── main.tsx         # Application entry point
│   └── mockData.ts      # Mock data for products and content
├── .gitignore           # Git ignore rules
├── eslint.config.js     # ESLint configuration
├── index.html           # HTML template
├── package.json         # Dependencies and scripts
├── postcss.config.js    # PostCSS configuration
├── tailwind.config.cjs  # Tailwind CSS configuration
├── tsconfig.app.json    # TypeScript app configuration
├── tsconfig.json        # TypeScript configuration
├── tsconfig.node.json   # TypeScript node configuration
└── vite.config.ts       # Vite configuration
```

## 🏃‍♂️ Getting Started

### Prerequisites

- Node.js (version 18 or higher)
- npm or yarn package manager

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/LXMachado/weekender-frontend-demo.git
   cd weekender-frontend
   ```

2. Install dependencies:
   ```bash
   npm install
   ```

3. Start the development server:
   ```bash
   npm run dev
   ```

4. Open your browser and navigate to `http://localhost:5173`

### Available Scripts

- `npm run dev` - Start development server with hot reload
- `npm run build` - Build for production
- `npm run preview` - Preview production build locally
- `npm run lint` - Run ESLint for code quality checks

## 🎨 Design System

### Color Palette
- Primary: Earth tones and sustainable greens
- Accent: Solar yellows and blues
- Neutral: Clean whites and grays

### Typography
- Font Family: System fonts with fallbacks
- Headings: Bold, hierarchical sizing
- Body: Readable, accessible text

### Components
- **Hero**: Full-width banner with call-to-action
- **ProductCard**: Grid-based product display
- **Features**: Icon-based feature highlights
- **Testimonials**: Customer review carousel
- **Newsletter**: Email subscription form
- **Footer**: Site navigation and contact info

## 📊 Data Models

### Product Interface
```typescript
interface Product {
  id: number;
  name: string;
  tagline: string;
  price: number;
  description: string;
  imageUrl: string;
  category: string;
  badges: string[];
}
```

### Feature Interface
```typescript
interface Feature {
  id: number;
  title: string;
  description: string;
  icon: string;
}
```

## 🔗 Routing Structure

The application uses React Router with the following routes:

- `/` - Home page (Hero, Features, Products, Mission, Testimonials, Newsletter)
- `/products` - Product catalog page
- `/about` - About page
- `/contact` - Contact page

## 🔧 Backend Integration Recommendations

For full-stack development, the following backend routing structure is recommended:

### API Endpoints

#### Products
- `GET /api/products` - Retrieve all products
- `GET /api/products/:id` - Retrieve specific product
- `POST /api/products` - Create new product (admin)
- `PUT /api/products/:id` - Update product (admin)
- `DELETE /api/products/:id` - Delete product (admin)

#### Categories
- `GET /api/categories` - Retrieve product categories
- `GET /api/categories/:id/products` - Get products by category

#### Users
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User authentication
- `GET /api/users/profile` - Get user profile
- `PUT /api/users/profile` - Update user profile

#### Orders
- `POST /api/orders` - Create new order
- `GET /api/orders/:id` - Retrieve order details
- `GET /api/users/:id/orders` - Get user's order history

#### Reviews
- `GET /api/products/:id/reviews` - Get product reviews
- `POST /api/products/:id/reviews` - Add product review

### Authentication
- JWT-based authentication recommended
- Include Bearer token in Authorization header for protected routes

### File Upload
- `POST /api/upload/images` - Upload product images
- Support for multiple formats (JPEG, PNG, WebP)

### Search and Filtering
- `GET /api/products/search?q=searchTerm` - Search products
- `GET /api/products?category=categoryName&minPrice=100&maxPrice=500` - Filter products

### Recommended Backend Tech Stack
- **Framework**: Laravel (PHP) or Node.js with Express
- **Database**: MySQL or PostgreSQL
- **Authentication**: JWT or Laravel Sanctum
- **File Storage**: AWS S3 or local storage
- **API Documentation**: Swagger/OpenAPI

## 🚀 Deployment

### Build for Production
```bash
npm run build
```

The build artifacts will be stored in the `dist/` directory.

### Deployment Options
- **Vercel**: Connect GitHub repo for automatic deployments
- **Netlify**: Drag-and-drop dist folder or connect repo
- **AWS S3 + CloudFront**: Static hosting with CDN
- **Docker**: Containerize with Nginx for serving static files

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is private and proprietary to Weekender.

## 📞 Contact

- **Company**: Weekender
- **Email**: info@weekender.com
- **Phone**: +1 (555) 123-4567
- **Address**: 123 Eco Street, Green City

## 🌱 Environmental Impact

Weekender is committed to sustainability:
- 2% of every purchase goes to reforestation
- Circular build program keeps gear in use
- Solar-native technology reduces carbon footprint
- Recycled and bamboo materials throughout

---

Built with ❤️ for the planet and outdoor enthusiasts.
