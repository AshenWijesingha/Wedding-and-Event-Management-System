# EventPro Installation Guide

## Requirements

- PHP 8.2+
- Composer 2.x
- Node.js 18+ and npm
- MySQL 8.0+ or PostgreSQL 14+ (or SQLite for local dev)
- Redis (optional, recommended for caching/queues)

---

## Local Development Setup

### 1. Clone the repository

```bash
git clone https://github.com/your-org/eventpro.git
cd eventpro
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Install JavaScript dependencies

```bash
npm install
```

### 4. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```env
APP_NAME=EventPro
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=eventpro
DB_USERNAME=root
DB_PASSWORD=

TENANCY_MODE=column
```

For SQLite (quick start):
```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
```

### 5. Run migrations and seed

```bash
# Create SQLite file if using SQLite
touch database/database.sqlite

php artisan migrate
php artisan db:seed
```

The seeder creates:
- Plans (starter, professional, enterprise)
- Demo tenant: **Grand Vista Events**
- Demo admin user: `admin@demo.eventpro.test` / `password`
- Demo venues, packages, clients, bookings, staff

### 6. Build frontend assets

```bash
# Development (watch mode)
npm run dev

# Production build
npm run build
```

### 7. Start the development server

```bash
php artisan serve
```

Visit: http://localhost:8000/admin

---

## Docker Setup

A `docker-compose.yml` is included for containerized development:

```bash
docker-compose up -d
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

Services: `app` (PHP-FPM), `nginx`, `mysql`, `redis`

---

## Production Deployment

### Environment

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=your-db-host
...

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=your-redis-host
REDIS_PASSWORD=your-redis-password
```

### Deployment Steps

```bash
# Pull latest code
git pull origin main

# Install dependencies (no dev)
composer install --no-dev --optimize-autoloader

# Build assets
npm ci && npm run build

# Run migrations
php artisan migrate --force

# Clear and cache config/routes/views
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers
php artisan queue:restart
```

### Queue Worker (production)

Configure Supervisor to run the queue worker:

```ini
[program:eventpro-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=2
```

### Scheduler (cron)

Add to server cron (`crontab -e`):
```
* * * * * cd /var/www && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler runs:
- `payments:send-reminders` — daily at 08:00 (payment due date reminders)

---

## Storage

Run `php artisan storage:link` to create the public storage symlink.

Ensure `storage/` and `bootstrap/cache/` are writable by the web server:
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## Running Tests

```bash
# Uses .env.testing (SQLite in-memory)
php artisan test

# With coverage report
php artisan test --coverage --min=70
```

---

## Troubleshooting

**"Class not found" errors:**
```bash
composer dump-autoload
```

**Migrations fail:**
```bash
php artisan migrate:fresh --seed
```

**Assets not loading:**
```bash
npm run build
php artisan view:clear
```

**Permission denied on storage:**
```bash
chmod -R 775 storage bootstrap/cache
```
