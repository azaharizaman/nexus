# Docker Setup Guide for Laravel ERP

This guide walks you through setting up and using the Laravel ERP development environment with Docker.

## Prerequisites

- Docker Desktop (or Docker + Docker Compose)
  - [Install Docker Desktop for macOS](https://docs.docker.com/desktop/install/mac-install/)
  - [Install Docker Desktop for Windows](https://docs.docker.com/desktop/install/windows-install/)
  - [Install Docker Desktop for Linux](https://docs.docker.com/desktop/install/linux-install/)
- Git
- A terminal/command prompt

## Quick Start

### 1. Clone the Repository

```bash
git clone https://github.com/azaharizaman/laravel-erp.git
cd laravel-erp
```

### 2. Start the Development Environment

```bash
# Build and start all services
docker-compose up -d

# Follow logs (optional)
docker-compose logs -f app
```

This command will:
- Build the Docker image for the application
- Start PostgreSQL, Redis, Meilisearch, PgAdmin, and MailHog
- Install PHP dependencies (Composer)
- Install JavaScript dependencies (npm)
- Start the Laravel development server

### 3. Access the Application

Once services are running, access them at:

| Service | URL | Credentials |
|---------|-----|-------------|
| **Laravel App** | http://localhost:8000 | N/A |
| **PostgreSQL** | localhost:5432 | User: `erp_user` / Pass: `erp_password` |
| **Redis** | localhost:6379 | N/A (no auth) |
| **Meilisearch** | http://localhost:7700 | Key: `masterKey` |
| **PgAdmin** | http://localhost:5050 | Email: `admin@laravel-erp.local` / Pass: `admin` |
| **MailHog** | http://localhost:8025 | N/A |
| **Redis Commander** | http://localhost:8081 | N/A |

### 4. Initialize the Database

```bash
# Enter the app container
docker-compose exec app bash

# Run migrations
artisan migrate

# Seed the database (if seeders exist)
artisan db:seed

# Exit container
exit
```

## Common Commands

### Container Management

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# View logs for all services
docker-compose logs -f

# View logs for specific service
docker-compose logs -f app
docker-compose logs -f postgres
docker-compose logs -f redis

# Rebuild services (after Dockerfile changes)
docker-compose up -d --build

# Restart a specific service
docker-compose restart app
```

### Application Commands

```bash
# Enter app container shell
docker-compose exec app bash

# Run Laravel Artisan commands
docker-compose exec app artisan migrate
docker-compose exec app artisan tinker
docker-compose exec app artisan cache:clear

# Run tests with Pest
docker-compose exec app ./vendor/bin/pest

# Format code with Pint
docker-compose exec app ./vendor/bin/pint

# Run npm commands
docker-compose exec app npm install
docker-compose exec app npm run dev
docker-compose exec app npm run build
```

### Database Management

```bash
# Access PostgreSQL directly
docker-compose exec postgres psql -U erp_user -d laravel_erp

# Create a database backup
docker-compose exec postgres pg_dump -U erp_user laravel_erp > backup.sql

# Restore from backup
docker-compose exec -T postgres psql -U erp_user laravel_erp < backup.sql

# Reset database (fresh migrations)
docker-compose exec app artisan migrate:fresh
docker-compose exec app artisan migrate:fresh --seed
```

### Debugging

```bash
# View service status
docker-compose ps

# Check service health
docker-compose ps --services

# View detailed logs
docker-compose logs app --tail 50

# Connect to specific container
docker-compose exec app bash
docker-compose exec postgres bash
docker-compose exec redis redis-cli
```

## Environment Variables

The `docker-compose.yml` file includes pre-configured environment variables:

**Database:**
- `DB_CONNECTION=pgsql`
- `DB_HOST=postgres`
- `DB_PORT=5432`
- `DB_DATABASE=laravel_erp`
- `DB_USERNAME=erp_user`
- `DB_PASSWORD=erp_password`

**Cache & Queue:**
- `CACHE_DRIVER=redis`
- `REDIS_HOST=redis`
- `REDIS_PORT=6379`
- `QUEUE_CONNECTION=redis`

**Search:**
- `SCOUT_DRIVER=meilisearch`
- `MEILISEARCH_HOST=http://meilisearch:7700`

To override any variables, create a `.env.docker` file or modify the `docker-compose.yml` file.

## Project Structure

```
docker/
├── Dockerfile              # PHP 8.3 Alpine image definition
├── php.ini                 # PHP configuration
├── postgres-init.sql       # PostgreSQL initialization script
├── pgadmin-servers.json    # PgAdmin server configuration
└── .bashrc                 # Bash profile for container
.dockerignore               # Files to exclude from image
docker-compose.yml          # Docker Compose configuration
```

## Services Overview

### App (Laravel)

- **Image:** Custom PHP 8.3 Alpine image (built from Dockerfile)
- **Port:** 8000
- **Features:** Xdebug enabled, Composer, Laravel Installer, npm, git
- **Volume:** `.:/workspace` (entire project mounted)
- **Command:** Runs `php artisan serve`

### PostgreSQL

- **Image:** `postgres:16-alpine`
- **Port:** 5432
- **Database:** `laravel_erp`
- **User:** `erp_user` / Password: `erp_password`
- **Extensions:** uuid-ossp, pgcrypto, hstore, ltree, and more
- **Persistence:** Data stored in `postgres_data` volume

### Redis

- **Image:** `redis:7-alpine`
- **Port:** 6379
- **Purpose:** Caching, session storage, queue driver
- **Persistence:** RDB snapshots in `redis_data` volume

### Meilisearch

- **Image:** `getmeili/meilisearch:v1.7`
- **Port:** 7700
- **Purpose:** Full-text search for models
- **Key:** `masterKey`

### PgAdmin

- **Image:** `dpage/pgadmin4:latest`
- **Port:** 5050
- **Purpose:** Web interface for PostgreSQL management
- **Email:** `admin@laravel-erp.local`
- **Password:** `admin`

### MailHog

- **Image:** `mailhog/mailhog:latest`
- **SMTP Port:** 1025
- **UI Port:** 8025
- **Purpose:** Email testing and debugging

### Redis Commander

- **Image:** `rediscommander/redis-commander:latest`
- **Port:** 8081
- **Purpose:** Web interface for Redis management

## Performance Optimization

### Memory and CPU Limits

For production-like setups, configure Docker resource limits in `docker-compose.yml`:

```yaml
services:
  app:
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 2G
        reservations:
          cpus: '1'
          memory: 1G
```

### PostgreSQL Optimization

The `docker/postgres-init.sql` script enables:
- UUID generation extension
- Cryptographic functions
- Automatic `updated_at` timestamp updates
- Query logging for slow queries (> 5s)
- Proper permissions and schemas

### PHP Opcache

Enabled with optimal settings:
- `memory_consumption = 256M`
- `max_accelerated_files = 10000`
- `validate_timestamps = 1` (development, auto-reload on change)

### Xdebug Configuration

Debug configuration is pre-configured:
- `idekey = laravel-erp`
- `client_host = host.docker.internal` (Docker for Mac/Windows)
- `client_port = 9003` (VS Code default)

**For Linux:** Change `host.docker.internal` to your Docker host IP (usually `172.17.0.1`)

## Troubleshooting

### Port Already in Use

If you get "port already in use" errors:

```bash
# Find what's using the port
lsof -i :8000  # Replace with your port

# Stop the service using it, or change the port in docker-compose.yml
```

### Database Connection Failed

```bash
# Check if PostgreSQL is running
docker-compose ps postgres

# Check PostgreSQL logs
docker-compose logs postgres

# Try restarting PostgreSQL
docker-compose restart postgres

# Ensure DATABASE is initialized (wait 10 seconds after startup)
```

### Composer/npm Install Fails

```bash
# Clear cache and reinstall
docker-compose exec app composer install --no-cache
docker-compose exec app npm cache clean --force && npm install
```

### Permission Denied Errors

```bash
# Fix permissions on storage directory
docker-compose exec app chmod -R 755 storage
docker-compose exec app chmod -R 755 bootstrap/cache

# Rebuild and restart
docker-compose down
docker-compose up -d --build
```

### Xdebug Not Working

1. Ensure your IDE is listening for debug connections on port 9003
2. Check container logs: `docker-compose logs app | grep xdebug`
3. For Linux hosts, update `XDEBUG_CLIENT_HOST` in `docker/php.ini`:
   ```ini
   xdebug.client_host = 172.17.0.1
   ```
4. Rebuild: `docker-compose down && docker-compose up -d --build`

### Redis Connection Issues

```bash
# Test Redis connection
docker-compose exec app redis-cli -h redis ping

# View Redis logs
docker-compose logs redis

# Clear Redis cache
docker-compose exec app artisan cache:clear
```

## Advanced Configuration

### Custom Environment Variables

Create a `.env` file in the project root with custom values:

```env
APP_NAME="My ERP System"
APP_DEBUG=true
APP_URL=http://localhost:8000
DB_HOST=postgres
DB_PASSWORD=my_custom_password
```

The `docker-compose.yml` will use these values if they're exported.

### Building for Production

Create a production Dockerfile:

```dockerfile
FROM php:8.3-cli-alpine as builder
# ... build with production optimizations

FROM php:8.3-cli-alpine
# ... copy only necessary files
```

### Multi-Stage Deployments

For Kubernetes or Docker Swarm:

```bash
# Build image with specific tag
docker build -t laravel-erp:v1.0 -f docker/Dockerfile.prod .

# Push to registry
docker tag laravel-erp:v1.0 registry.example.com/laravel-erp:v1.0
docker push registry.example.com/laravel-erp:v1.0
```

## Migration from Traditional Setup

If you have an existing installation:

```bash
# Backup your database
pg_dump -U postgres laravel_erp > backup.sql

# Start new Docker environment
docker-compose up -d

# Restore database
docker-compose exec -T postgres psql -U erp_user laravel_erp < backup.sql

# Run migrations if needed
docker-compose exec app artisan migrate
```

## Documentation

- [Docker Official Documentation](https://docs.docker.com/)
- [Docker Compose Reference](https://docs.docker.com/compose/compose-file/)
- [Laravel Docker Guide](https://laravel.com/docs/deployment#docker)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Redis Documentation](https://redis.io/documentation)

## Support

For issues or questions:

1. Check the [Troubleshooting](#troubleshooting) section
2. Review container logs: `docker-compose logs <service>`
3. Check Laravel documentation: https://laravel.com/docs
4. Open an issue on GitHub

## Additional Resources

- **CODING_GUIDELINES.md** - Development standards
- **NAMESPACE-AND-DATABASE-REFACTORING.md** - Architecture decisions
- **README.md** - Project overview
- **SANCTUM_AUTHENTICATION.md** - Authentication guide

---

**Last Updated:** November 12, 2025  
**Version:** 1.0  
**Compatible with:** PHP 8.3, PostgreSQL 16, Docker 20.10+
