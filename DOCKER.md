# Docker Development Environment

This document provides comprehensive instructions for setting up and using the Docker development environment for EventPro.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Quick Start](#quick-start)
3. [Services Overview](#services-overview)
4. [Environment Configuration](#environment-configuration)
5. [Development Scripts](#development-scripts)
6. [Common Tasks](#common-tasks)
7. [Troubleshooting](#troubleshooting)
8. [Container Networking](#container-networking)

---

## Prerequisites

Before starting, ensure you have the following installed:

- **Docker**: Version 20.10 or higher
- **Docker Compose**: Version 2.0 or higher
- **Git**: Version 2.40 or higher

### Installing Docker

**Ubuntu/Debian:**
```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER
```

**macOS:**
```bash
brew install --cask docker
```

**Windows:**
Download and install [Docker Desktop](https://www.docker.com/products/docker-desktop)

---

## Quick Start

### 1. Clone the Repository

```bash
git clone https://github.com/[username]/eventpro.git
cd eventpro
```

### 2. Start Docker Containers

```bash
./scripts/docker-start.sh
```

This will:
- Build the Docker images
- Start all required services (App, MySQL, Redis, Mailhog, Meilisearch)
- Display the status of all containers

### 3. Initial Setup

```bash
./scripts/docker-setup.sh
```

This will:
- Create `.env` file from `.env.example`
- Install PHP dependencies (Composer)
- Generate application key
- Run database migrations
- Install Node.js dependencies
- Build frontend assets
- Clear and cache configuration

### 4. Access the Application

Once setup is complete, access the following services:

- **Application**: http://localhost:8000
- **Mailhog UI**: http://localhost:8025 (Email testing)
- **Meilisearch**: http://localhost:7700 (Search engine)

---

## Services Overview

### Application Container (app)

- **Image**: Custom PHP 8.2-FPM
- **Port**: 8000
- **Purpose**: Runs the Laravel application

**PHP Extensions Installed:**
- `pdo_mysql` - MySQL database driver
- `redis` - Redis client
- `gd` - Image processing
- `zip` - Archive handling
- `bcmath` - Arbitrary precision mathematics
- `mbstring` - Multibyte string support
- `intl` - Internationalization functions

### MySQL Database (mysql)

- **Image**: MySQL 8.0
- **Port**: 3306
- **Database**: eventpro
- **Username**: root
- **Password**: secret
- **Purpose**: Primary database for the application

**Default Credentials:**
```
Root User: root / secret
App User: eventpro / secret
Database: eventpro
```

### Redis Cache (redis)

- **Image**: Redis 7 Alpine
- **Port**: 6379
- **Purpose**: Caching, sessions, and queue management

**Used For:**
- Application cache
- Session storage
- Queue jobs
- Rate limiting

### Mailhog (mailhog)

- **Image**: Mailhog/Mailhog
- **SMTP Port**: 1025
- **Web UI Port**: 8025
- **Purpose**: Email testing and debugging

Access the web interface at http://localhost:8025 to view captured emails.

### Meilisearch (meilisearch)

- **Image**: getmeili/meilisearch:latest
- **Port**: 7700
- **Purpose**: Full-text search engine

Used for fast and relevant search across venues, events, and bookings.

---

## Environment Configuration

### Default Docker Environment Variables

The `.env.example` file is pre-configured for Docker. Key settings:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=eventpro
DB_USERNAME=root
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025

# Meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
```

### Customizing Configuration

To change database credentials, update both:
1. `.env` file
2. `docker-compose.yml` (MySQL environment variables)

---

## Development Scripts

All scripts are located in the `scripts/` directory and should be run from the project root.

### docker-start.sh

Starts all Docker containers.

```bash
./scripts/docker-start.sh
```

**What it does:**
- Builds containers if needed
- Starts all services
- Displays container status

### docker-stop.sh

Stops all Docker containers.

```bash
./scripts/docker-stop.sh
```

**Options:**
```bash
# Stop and remove volumes (WARNING: Deletes all data)
docker-compose down -v
```

### docker-setup.sh

Performs initial application setup.

```bash
./scripts/docker-setup.sh
```

**What it does:**
- Creates `.env` file
- Installs dependencies
- Runs migrations
- Builds assets
- Caches configuration

### docker-restart.sh

Restarts all Docker containers.

```bash
./scripts/docker-restart.sh
```

### docker-logs.sh

Views container logs.

```bash
# View logs for app container
./scripts/docker-logs.sh

# View logs for specific service
./scripts/docker-logs.sh mysql
./scripts/docker-logs.sh redis

# View logs for all services
./scripts/docker-logs.sh all
```

### docker-shell.sh

Opens a shell in a container.

```bash
# Shell in app container
./scripts/docker-shell.sh

# Shell in specific service
./scripts/docker-shell.sh mysql
./scripts/docker-shell.sh redis

# MySQL database shell
./scripts/docker-shell.sh mysql
```

---

## Common Tasks

### Running Artisan Commands

```bash
docker-compose exec app php artisan [command]
```

**Examples:**
```bash
# Run migrations
docker-compose exec app php artisan migrate

# Create a new controller
docker-compose exec app php artisan make:controller VenueController

# Clear cache
docker-compose exec app php artisan cache:clear

# Run queue worker
docker-compose exec app php artisan queue:work
```

### Running Composer Commands

```bash
docker-compose exec app composer [command]
```

**Examples:**
```bash
# Install a package
docker-compose exec app composer require spatie/laravel-backup

# Update dependencies
docker-compose exec app composer update

# Dump autoload
docker-compose exec app composer dump-autoload
```

### Running NPM Commands

```bash
docker-compose exec app npm [command]
```

**Examples:**
```bash
# Install a package
docker-compose exec app npm install -D tailwindcss

# Run development build
docker-compose exec app npm run dev

# Run production build
docker-compose exec app npm run build

# Watch for changes
docker-compose exec app npm run watch
```

### Database Management

**Accessing MySQL:**
```bash
docker-compose exec mysql mysql -u root -psecret eventpro
```

**Running Migrations:**
```bash
docker-compose exec app php artisan migrate
```

**Rolling Back Migrations:**
```bash
docker-compose exec app php artisan migrate:rollback
```

**Seeding Database:**
```bash
docker-compose exec app php artisan db:seed
```

**Resetting Database:**
```bash
docker-compose exec app php artisan migrate:fresh --seed
```

### Testing Email with Mailhog

1. Trigger an email in your application
2. Open http://localhost:8025
3. View the captured email in the Mailhog UI

**Testing Example:**
```bash
docker-compose exec app php artisan tinker
>>> Mail::raw('Test email', function($msg) { $msg->to('test@example.com')->subject('Test'); });
```

### Using Redis Cache

**Clear Redis Cache:**
```bash
docker-compose exec app php artisan cache:clear
```

**Access Redis CLI:**
```bash
docker-compose exec redis redis-cli
```

**Redis Commands:**
```bash
# List all keys
KEYS *

# Get a key value
GET laravel_cache:config

# Flush all data
FLUSHALL
```

### Using Meilisearch

**Import data to Meilisearch:**
```bash
docker-compose exec app php artisan scout:import "App\Models\Venue"
```

**Flush Meilisearch index:**
```bash
docker-compose exec app php artisan scout:flush "App\Models\Venue"
```

---

## Troubleshooting

### Containers Won't Start

**Check Docker is running:**
```bash
docker --version
docker-compose --version
```

**View container logs:**
```bash
./scripts/docker-logs.sh all
```

**Rebuild containers:**
```bash
docker-compose down
docker-compose build --no-cache
./scripts/docker-start.sh
```

### Database Connection Issues

**Verify MySQL is healthy:**
```bash
docker-compose ps mysql
```

**Check MySQL logs:**
```bash
./scripts/docker-logs.sh mysql
```

**Test connection:**
```bash
docker-compose exec mysql mysql -u root -psecret -e "SHOW DATABASES;"
```

**Restart MySQL:**
```bash
docker-compose restart mysql
```

### Redis Connection Issues

**Verify Redis is running:**
```bash
docker-compose exec redis redis-cli ping
```

**Check Redis logs:**
```bash
./scripts/docker-logs.sh redis
```

### Permission Issues

**Fix storage permissions:**
```bash
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chown -R www-data:www-data /var/www/html/bootstrap/cache
```

### Port Already in Use

If you see "port already allocated" errors:

**Find process using the port:**
```bash
# On Linux/Mac
lsof -i :8000
lsof -i :3306

# On Windows
netstat -ano | findstr :8000
```

**Change ports in docker-compose.yml:**
```yaml
services:
  app:
    ports:
      - "8001:8000"  # Use 8001 instead
```

### Clear Everything and Start Fresh

**Remove all containers, volumes, and images:**
```bash
docker-compose down -v
docker system prune -a
./scripts/docker-start.sh
./scripts/docker-setup.sh
```

---

## Container Networking

### Network Architecture

All containers are connected via a custom bridge network (`eventpro-network`), allowing them to communicate using service names as hostnames.

**Network Topology:**
```
┌─────────────────────────────────────────────┐
│         eventpro-network (Bridge)           │
│                                             │
│  ┌─────────┐  ┌─────────┐  ┌──────────┐  │
│  │   app   │  │  mysql  │  │  redis   │  │
│  │  :8000  │  │  :3306  │  │  :6379   │  │
│  └─────────┘  └─────────┘  └──────────┘  │
│                                             │
│  ┌──────────┐  ┌──────────────┐           │
│  │ mailhog  │  │ meilisearch  │           │
│  │:1025:8025│  │    :7700     │           │
│  └──────────┘  └──────────────┘           │
└─────────────────────────────────────────────┘
```

### Service Communication

**From App Container:**
```php
// Database connection
'host' => 'mysql',  // Not 'localhost' or '127.0.0.1'

// Redis connection
'host' => 'redis',

// Mail server
'host' => 'mailhog',

// Search engine
'host' => 'http://meilisearch:7700',
```

### Testing Network Connectivity

**From app container:**
```bash
docker-compose exec app ping mysql
docker-compose exec app ping redis
docker-compose exec app curl http://meilisearch:7700
```

### Health Checks

Services include health checks to ensure they're ready before dependent services start:

- **MySQL**: Checks if database responds to ping
- **Redis**: Checks if Redis CLI ping succeeds

**View health status:**
```bash
docker-compose ps
```

---

## Best Practices

### Development Workflow

1. Start containers: `./scripts/docker-start.sh`
2. Make code changes (auto-synced via volumes)
3. Run tests: `docker-compose exec app php artisan test`
4. View logs: `./scripts/docker-logs.sh`
5. Stop containers: `./scripts/docker-stop.sh`

### Performance Tips

- Use Docker volume caching for vendor and node_modules
- Keep containers running between sessions
- Use `docker-compose restart` instead of down/up
- Regularly prune unused images: `docker system prune`

### Security Notes

- Never commit `.env` file
- Change default passwords in production
- Use environment-specific docker-compose files
- Keep Docker and images updated

---

## Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Laravel Docker Documentation](https://laravel.com/docs/deployment#docker)
- [PHP Docker Images](https://hub.docker.com/_/php)

---

## Support

If you encounter issues not covered in this guide:

1. Check container logs: `./scripts/docker-logs.sh all`
2. Verify all services are healthy: `docker-compose ps`
3. Try rebuilding: `docker-compose build --no-cache`
4. Review [Troubleshooting](#troubleshooting) section

For persistent issues, please create an issue on GitHub with:
- Output of `docker-compose ps`
- Relevant logs from `docker-compose logs`
- Steps to reproduce the issue
