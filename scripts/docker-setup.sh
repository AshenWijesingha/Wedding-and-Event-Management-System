#!/bin/bash

# EventPro Docker Setup Script
# This script performs the initial setup for the application

echo "🔧 Setting up EventPro application..."

# Copy environment file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file from .env.example..."
    cp .env.example .env
else
    echo "✅ .env file already exists"
fi

# Install composer dependencies
echo "📦 Installing PHP dependencies..."
docker compose exec -T app composer install --no-interaction

# Generate application key
echo "🔑 Generating application key..."
docker compose exec -T app php artisan key:generate

# Run migrations
echo "🗄️  Running database migrations..."
docker compose exec -T app php artisan migrate --force

# Seed database (optional)
read -p "Do you want to seed the database? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "🌱 Seeding database..."
    docker compose exec -T app php artisan db:seed
fi

# Install npm dependencies
echo "📦 Installing Node.js dependencies..."
docker compose exec -T app npm install

# Build assets
echo "🏗️  Building frontend assets..."
docker compose exec -T app npm run build

# Clear and cache config
echo "🧹 Clearing and caching configuration..."
docker compose exec -T app php artisan config:clear
docker compose exec -T app php artisan cache:clear
docker compose exec -T app php artisan view:clear

# Set permissions
echo "🔒 Setting permissions..."
docker compose exec -T app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo ""
echo "✅ Setup completed successfully!"
echo ""
echo "📌 Application is ready at: http://localhost:8000"
echo "📧 Mailhog UI available at: http://localhost:8025"
echo ""
echo "💡 Useful commands:"
echo "   - View logs: ./scripts/docker-logs.sh"
echo "   - Stop containers: ./scripts/docker-stop.sh"
echo "   - Restart containers: ./scripts/docker-restart.sh"
