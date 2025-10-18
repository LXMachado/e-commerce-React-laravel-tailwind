# Weekender E-commerce Platform

A modern full-stack e-commerce platform built with Laravel and React, specializing in micro-camping and weekender tech gear.

![Laravel](https://img.shields.io/badge/Laravel-11.x-red?style=flat-square&logo=laravel)
![React](https://img.shields.io/badge/React-18.x-blue?style=flat-square&logo=react)
![TypeScript](https://img.shields.io/badge/TypeScript-5.x-blue?style=flat-square&logo=typescript)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.x-teal?style=flat-square&logo=tailwindcss)
![PHP](https://img.shields.io/badge/PHP-8.2+-purple?style=flat-square&logo=php)

## 🚀 Features

- **Modern Stack**: Laravel 11 + React 18 + TypeScript + Vite
- **Authentication**: Laravel Sanctum SPA authentication
- **Responsive Design**: TailwindCSS for modern, mobile-first UI
- **API-First**: RESTful API architecture
- **Developer Experience**: Hot reloading, type safety, and modern tooling
- **Production Ready**: Optimized builds and deployment automation

## 🏗 Architecture

```text
├── backend/          # Laravel 11 API Backend
│   ├── app/         # Application logic
│   ├── config/      # Configuration files
│   ├── database/    # Migrations, factories, seeders
│   ├── routes/      # API routes
│   └── ...
├── frontend/        # React + TypeScript Frontend
│   ├── src/
│   │   ├── components/  # React components
│   │   ├── hooks/      # Custom hooks
│   │   ├── services/   # API services
│   │   ├── types/      # TypeScript type definitions
│   │   └── utils/      # Utility functions
│   └── ...
└── deploy.sh        # Deployment script
```

## 📋 Prerequisites

- **PHP** 8.2 or higher
- **Node.js** 20 or higher
- **Composer** 2.x
- **MySQL** 8.0+ (or SQLite for development)
- **Git**

## 🛠 Local Development Setup

### 1. Clone the Repository

```bash
git clone https://github.com/LXMachado/e-commerce-React-laravel-tailwind.git
cd e-commerce-React-laravel-tailwind
```

### 2. Backend Setup (Laravel)

```bash
# Navigate to backend directory
cd backend

# Install PHP dependencies
php ../composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env file
# DB_CONNECTION=sqlite (for development)
# or configure MySQL connection

# Run migrations
php artisan migrate

# Seed database (optional)
php artisan db:seed
```

### 3. Frontend Setup (React)

```bash
# Navigate to frontend directory
cd frontend

# Install Node dependencies
npm install
```

### 4. Environment Configuration

Update the backend `.env` file with your configuration:

```env
APP_NAME="Weekender E-commerce"
APP_ENV=local
APP_KEY=base64:your-generated-key
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
# or for MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=weekender_db
# DB_USERNAME=your_username
# DB_PASSWORD=your_password

SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000
SESSION_DOMAIN=localhost
```

## 🚀 Running the Application

### Development Mode

You can run both frontend and backend simultaneously:

```bash
# From project root
npm run dev
```

Or run them separately:

```bash
# Terminal 1 - Backend (Laravel)
npm run dev:backend
# Runs on http://localhost:8000

# Terminal 2 - Frontend (React)
npm run dev:frontend
# Runs on http://localhost:3000
```

### Individual Commands

**Backend (Laravel)**:
```bash
cd backend
php artisan serve --host=0.0.0.0 --port=8000
```

**Frontend (React)**:
```bash
cd frontend
npm run dev
```

## 🏗 Building for Production

### Build Frontend

```bash
npm run build
```

### Build Both (Frontend + Backend optimization)

```bash
npm run build
```

## 🧪 Testing

### Run All Tests

```bash
npm test
```

### Frontend Tests

```bash
cd frontend
npm run test
```

### Backend Tests

```bash
cd backend
php artisan test
```

## 🎨 Code Quality

### Linting

```bash
# Lint all code
npm run lint

# Fix linting issues
npm run lint:fix
```

### Formatting

```bash
# Format all code
npm run format
```

## 📚 API Documentation

The Laravel backend provides a RESTful API. Key endpoints include:

- `POST /api/register` - User registration
- `POST /api/login` - User authentication
- `POST /api/logout` - User logout
- `GET /api/user` - Get authenticated user
- `GET /api/products` - List products
- `POST /api/products` - Create product (admin)
- `GET /api/orders` - List user orders
- `POST /api/orders` - Create order

## 🚀 Deployment

This project is configured for deployment on Hostinger with automated deployment via the included `deploy.sh` script.

### Manual Deployment

1. Build the frontend:
   ```bash
   npm run build
   ```

2. Run the deployment script:

   ```bash
   ./deploy.sh
   ```

### Automated Deployment

The project includes GitHub Actions workflow for automated deployment. See `DEPLOYMENT.md` for detailed deployment instructions.

## 🛠 Tech Stack

### Backend

- **Laravel 11** - PHP framework
- **Laravel Sanctum** - SPA authentication
- **MySQL/SQLite** - Database
- **PHP 8.2+** - Programming language

### Frontend

- **React 18** - UI library
- **TypeScript** - Type safety
- **Vite** - Build tool and dev server
- **TailwindCSS** - Utility-first CSS framework
- **React Router** - Client-side routing

### Development Tools

- **ESLint** - JavaScript/TypeScript linting
- **Prettier** - Code formatting
- **PHP CS Fixer** - PHP code style fixer
- **Concurrently** - Run multiple commands

## 📁 Project Structure

```text
weekender-ecommerce/
├── backend/                 # Laravel Backend
│   ├── app/
│   │   ├── Http/Controllers/
│   │   ├── Models/
│   │   └── Providers/
│   ├── config/
│   ├── database/
│   │   ├── migrations/
│   │   ├── factories/
│   │   └── seeders/
│   ├── routes/
│   │   ├── api.php
│   │   └── web.php
│   └── ...
├── frontend/               # React Frontend
│   ├── src/
│   │   ├── components/
│   │   ├── hooks/
│   │   ├── services/
│   │   ├── types/
│   │   ├── utils/
│   │   ├── App.tsx
│   │   └── main.tsx
│   ├── public/
│   └── ...
├── .github/
│   └── workflows/         # GitHub Actions
├── deploy.sh             # Deployment script
├── DEPLOYMENT.md         # Deployment guide
└── package.json          # Root package.json
```

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

If you encounter any issues or have questions:

1. Check the [DEPLOYMENT.md](DEPLOYMENT.md) for deployment-specific issues
2. Open an issue on GitHub
3. Review the Laravel and React documentation

## 🔗 Links

- [Laravel Documentation](https://laravel.com/docs)
- [React Documentation](https://reactjs.org/docs)
- [TailwindCSS Documentation](https://tailwindcss.com/docs)
- [Vite Documentation](https://vitejs.dev/guide/)

---

Built with ❤️ for the camping and outdoor tech community