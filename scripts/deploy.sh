#!/usr/bin/env bash
set -euo pipefail

# EventPro Production Deployment Script
echo "==> EventPro Deployment: $(date)"

# 1. Pull latest code
echo "==> Pulling latest code..."
git pull origin main

# 2. Install/update PHP dependencies (no dev)
echo "==> Installing PHP dependencies..."
composer install --no-dev --no-interaction --optimize-autoloader

# 3. Build frontend assets
echo "==> Building frontend assets..."
npm ci --omit=dev
npm run build

# 4. Maintenance mode
echo "==> Enabling maintenance mode..."
php artisan down --secret="deploy-$(date +%s)"

# 5. Run database migrations
echo "==> Running migrations..."
php artisan migrate --force

# 6. Cache application
echo "==> Caching configuration..."
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan view:clear
php artisan view:cache

# 7. Restart queue workers
echo "==> Restarting queue workers..."
php artisan queue:restart

# 8. Clear storage caches
php artisan cache:clear

# 9. Take out of maintenance
echo "==> Disabling maintenance mode..."
php artisan up

echo "==> Deployment complete: $(date)"
