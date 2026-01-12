#!/bin/bash

# Weekender E-commerce Deployment Script
# For Hostinger single app deployment

set -e

echo "🚀 Starting Weekender deployment..."

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="weekender"
BACKEND_DIR="backend"
FRONTEND_DIR="frontend"
BUILD_DIR="backend/public/app"

echo -e "${BLUE}📦 Building React frontend...${NC}"

# Install frontend dependencies and build
cd $FRONTEND_DIR
npm install
npm run build

echo -e "${BLUE}🔄 Copying build to Laravel public directory...${NC}"

# Create build directory if it doesn't exist
mkdir -p ../$BUILD_DIR

# Copy built files to Laravel public directory
cp -r dist/* ../$BUILD_DIR/

echo -e "${BLUE}⚡ Optimizing Laravel...${NC}"

# Navigate to backend and optimize
cd ../$BACKEND_DIR

# Install PHP dependencies
php ../composer install --no-dev --optimize-autoloader

# Generate application key if it doesn't exist
php artisan key:generate --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
php artisan migrate --force

echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo -e "${YELLOW}🌐 Your Weekender app is ready to serve!${NC}"