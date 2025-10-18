# Weekender E-commerce Deployment Guide

## Overview

This guide covers deploying the Weekender e-commerce platform to Hostinger using the provided deployment script and GitHub Actions workflow.

## Architecture

- **Laravel 11** backend with Sanctum SPA authentication
- **React + TypeScript** frontend built with Vite
- **TailwindCSS** for styling
- **MySQL** database (SQLite for development)
- **Hostinger** single app hosting

## Local Development Setup

### Prerequisites

- PHP 8.2+
- Node.js 20+
- Composer
- MySQL (or SQLite for development)

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd weekender-ecommerce
   ```

2. **Install PHP dependencies**
   ```bash
   cd backend
   php ../composer install
   ```

3. **Install Node dependencies**
   ```bash
   cd frontend
   npm install
   ```

4. **Environment setup**
   ```bash
   cd backend
   cp .env.example .env
   php artisan key:generate
   ```

   Update `.env` with your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=weekender
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Start development servers**
   ```bash
   # Terminal 1 - Laravel backend
   cd backend
   php artisan serve --host=0.0.0.0 --port=8000

   # Terminal 2 - React frontend
   cd frontend
   npm run dev
   ```

## Production Deployment (Hostinger)

### Option 1: Manual Deployment

1. **SSH into your Hostinger server**
   ```bash
   ssh your-username@your-server.hostinger.com
   ```

2. **Navigate to your application directory**
   ```bash
   cd /home/your-username/your-domain.com
   ```

3. **Upload deployment files**
   ```bash
   # Upload all files from your local project
   # Or use git clone if you have git access
   ```

4. **Run deployment script**
   ```bash
   # Upload the deploy.sh script first, then run:
   chmod +x deploy.sh
   ./deploy.sh
   ```

### Option 2: GitHub Actions (Recommended)

1. **Set up GitHub repository secrets** (in your GitHub repo settings):
   - `FTP_SERVER`: Your Hostinger FTP server (e.g., `ftp.yourdomain.com`)
   - `FTP_USERNAME`: Your Hostinger FTP username
   - `FTP_PASSWORD`: Your Hostinger FTP password

2. **Push to main branch**
   ```bash
   git add .
   git commit -m "Deploy to production"
   git push origin main
   ```

3. **Monitor deployment**
   - Watch the Actions tab in your GitHub repository
   - The workflow will run tests, build the frontend, and deploy to Hostinger

## Environment Configuration

### Production Environment Variables

Update `backend/.env` for production:

```env
APP_NAME=Weekender
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database (MySQL on Hostinger)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Sanctum SPA Configuration
SANCTUM_STATEFUL_DOMAINS=yourdomain.com
FRONTEND_URL=https://yourdomain.com

# Session Configuration
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=none

# Mail Configuration (for order emails)
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host.com
MAIL_PORT=587
MAIL_USERNAME=your-email@yourdomain.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=your-email@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

## Domain Configuration

### Hostinger hPanel Setup

1. **Set document root** to `/backend/public`
2. **Configure SSL/HTTPS** (required for Sanctum cookies)
3. **Set up database** in MySQL Databases section
4. **Configure email** for order notifications

## Post-Deployment Steps

1. **Generate application key**
   ```bash
   php artisan key:generate --force
   ```

2. **Run migrations**
   ```bash
   php artisan migrate --force
   ```

3. **Cache configuration**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

4. **Set up cron jobs** for queue workers (if using queues)
   ```bash
   * * * * * cd /path-to-your-project/backend && php artisan schedule:run >> /dev/null 2>&1
   ```

## Troubleshooting

### Common Issues

1. **CORS errors**: Ensure `SANCTUM_STATEFUL_DOMAINS` includes your domain
2. **CSRF token errors**: Make sure cookies are enabled and HTTPS is working
3. **File permissions**: Ensure `storage/` and `bootstrap/cache/` are writable
4. **Build errors**: Check Node.js and PHP versions match requirements

### Logs

- Laravel logs: `backend/storage/logs/laravel.log`
- PHP errors: Check Hostinger error logs in hPanel
- Web server logs: `/var/log/apache2/` or similar

## Security Checklist

- [ ] Enable HTTPS/SSL
- [ ] Set `APP_DEBUG=false` in production
- [ ] Configure strong database passwords
- [ ] Set up regular backups
- [ ] Monitor for security updates
- [ ] Configure firewall rules
- [ ] Set up fail2ban for SSH protection

## Support

For issues related to:
- Laravel: Check `backend/storage/logs/laravel.log`
- React: Check browser console and network tab
- Deployment: Check GitHub Actions logs
- Hosting: Contact Hostinger support

## Performance Optimization

1. **Enable caching** (already configured in deploy script)
2. **Optimize images** with WebP/AVIF formats
3. **Set up CDN** for static assets
4. **Configure database indexes** for product queries
5. **Enable compression** in `.htaccess`

## Monitoring

- Set up uptime monitoring (e.g., UptimeRobot)
- Monitor error rates and response times
- Set up alerts for failed deployments
- Track performance metrics (Core Web Vitals)