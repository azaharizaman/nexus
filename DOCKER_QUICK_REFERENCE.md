# Docker Quick Reference

Complete Docker setup for Laravel ERP with PostgreSQL, Redis, Meilisearch, and development tools.

## 🚀 Quick Start (5 minutes)

```bash
# 1. Clone repository
git clone https://github.com/azaharizaman/laravel-erp.git
cd laravel-erp

# 2. Start all services
docker-compose up -d

# 3. Install dependencies (wait ~30 seconds for services to be ready)
docker-compose exec app composer install
docker-compose exec app npm install

# 4. Run migrations
docker-compose exec app artisan migrate:fresh --seed

# 5. Visit http://localhost:8000
```

## 📋 File Structure

```
docker/
├── Dockerfile                    # PHP 8.3 Alpine image
├── php.ini                       # PHP configuration (2GB memory, opcache, xdebug)
├── postgres-init.sql             # PostgreSQL extensions and init script
├── pgadmin-servers.json          # PgAdmin configuration
└── .bashrc                        # Container bash profile

.dockerignore                      # Files excluded from Docker image
docker-compose.yml                 # Complete service definition
Makefile                           # Convenient command shortcuts
DOCKER_SETUP.md                    # Detailed setup guide
```

## 🎯 Using Make Commands (Recommended)

The `Makefile` provides convenient shortcuts for common tasks:

```bash
# Show all available commands
make help

# Installation
make install              # Full setup: build + install dependencies
make build               # Build Docker image only

# Service Management
make start               # Start all services
make stop                # Stop all services
make restart             # Restart services
make ps                  # Show running containers

# Application
make bash                # Enter app container shell
make artisan CMD="migrate"  # Run Artisan command
make test                # Run tests with Pest
make format              # Format code with Pint

# Database
make migrate             # Run migrations
make migrate-fresh       # Reset database
make migrate-fresh-seed  # Reset + seed
make db-backup           # Create backup
make db-restore FILE=./backups/backup.sql

# Development
make logs                # View all logs
make logs-app            # View app logs
make logs-db             # View PostgreSQL logs
make npm-dev             # Watch and build assets
make cache-clear         # Clear all caches
```

## 🐳 Docker Compose Commands

```bash
# Start services (detached mode)
docker-compose up -d

# Start services (foreground, Ctrl+C to stop)
docker-compose up

# Stop services
docker-compose down

# View logs
docker-compose logs -f         # All services
docker-compose logs -f app     # Specific service

# Execute commands in container
docker-compose exec app bash           # Enter shell
docker-compose exec app artisan migrate
docker-compose exec app composer install

# Rebuild image
docker-compose up -d --build

# Remove unused resources
docker-compose down -v
```

## 🌐 Service URLs

| Service | URL | Access |
|---------|-----|--------|
| **Laravel App** | http://localhost:8000 | Web browser |
| **PostgreSQL** | localhost:5432 | Clients (psql, PgAdmin) |
| **Redis** | localhost:6379 | Clients (redis-cli) |
| **Meilisearch** | http://localhost:7700 | API / Web UI |
| **PgAdmin** | http://localhost:5050 | http://localhost:5050 |
| **MailHog** | http://localhost:8025 | http://localhost:8025 |
| **Redis Commander** | http://localhost:8081 | http://localhost:8081 |

## 🔐 Credentials

| Service | Username | Password |
|---------|----------|----------|
| **PostgreSQL** | erp_user | erp_password |
| **PgAdmin** | admin@laravel-erp.local | admin |
| **Meilisearch** | (API key) | masterKey |
| **Redis** | (no auth) | — |

## 📦 Services Included

### PHP Application (PHP 8.3 Alpine)
- **Base:** `php:8.3-cli-alpine` (slim, ~130MB)
- **Extensions:** pgsql, redis, gd, intl, zip, xml, mbstring, xdebug, etc.
- **Tools:** Composer, Laravel Installer, Node.js, npm, git, Supervisor
- **Port:** 8000
- **Memory:** 2GB (development)
- **Xdebug:** Enabled on port 9003

### PostgreSQL 16 (Alpine)
- **Extensions:** uuid-ossp, pgcrypto, hstore, ltree, intarray, json, pg_trgm, etc.
- **Database:** `laravel_erp`
- **User:** `erp_user` / `erp_password`
- **Port:** 5432
- **Init Script:** Automatic function and trigger setup
- **Health Check:** Every 10 seconds

### Redis 7 (Alpine)
- **Purpose:** Cache, sessions, queue driver
- **Port:** 6379
- **No Auth:** By default
- **Persistence:** RDB snapshots
- **Health Check:** Every 10 seconds

### Meilisearch v1.7
- **Purpose:** Full-text search for models (Scout integration)
- **Port:** 7700
- **API Key:** `masterKey`
- **Health Check:** Every 10 seconds

### PgAdmin 4 (Latest)
- **Purpose:** PostgreSQL web management interface
- **Port:** 5050
- **Admin:** `admin@laravel-erp.local` / `admin`
- **Pre-configured:** Development database connection

### MailHog (Latest)
- **Purpose:** Email testing and debugging
- **SMTP Port:** 1025 (configure in Laravel)
- **UI Port:** 8025
- **Storage:** In-memory (resets on restart)

### Redis Commander (Latest)
- **Purpose:** Redis web management interface
- **Port:** 8081
- **Auto-connected:** To the Redis service

## 🔧 Common Tasks

### Database Operations

```bash
# Enter PostgreSQL shell
docker-compose exec postgres psql -U erp_user -d laravel_erp

# List tables
docker-compose exec postgres psql -U erp_user -d laravel_erp -c "\dt"

# Create backup
docker-compose exec postgres pg_dump -U erp_user laravel_erp > backup.sql

# Restore from backup
cat backup.sql | docker-compose exec -T postgres psql -U erp_user -d laravel_erp

# Watch query logs (slow queries)
docker-compose exec postgres tail -f /var/log/postgresql/postgresql.log
```

### Cache & Session Management

```bash
# Clear all caches
make cache-clear

# Clear specific cache driver
docker-compose exec app artisan cache:forget <key>

# Monitor Redis
docker-compose exec redis redis-cli monitor

# Check Redis memory
docker-compose exec redis redis-cli info memory
```

### Testing & Debugging

```bash
# Run all tests
make test

# Run specific test file
docker-compose exec app ./vendor/bin/pest tests/Feature/ExampleTest.php

# Run with code coverage
docker-compose exec app ./vendor/bin/pest --coverage

# Format code with Pint
make format

# PHPStan static analysis
make stan
```

### Development Workflow

```bash
# Watch for asset changes (npm)
make npm-dev

# Build assets for production
make npm-build

# Enter interactive shell
make bash

# Run Artisan commands interactively
docker-compose exec app artisan tinker

# Start queue worker
make queue-work
```

## 🐛 Troubleshooting

### Services Won't Start

```bash
# Check logs
docker-compose logs

# Ensure ports are available
lsof -i :8000
lsof -i :5432

# Stop conflicting service and restart
docker-compose down
docker-compose up -d
```

### Database Connection Error

```bash
# Ensure PostgreSQL is running
docker-compose ps postgres

# Check PostgreSQL health
docker-compose exec postgres pg_isready

# Restart PostgreSQL
docker-compose restart postgres

# Wait 10 seconds for startup
sleep 10
docker-compose exec app artisan migrate
```

### Permission Errors

```bash
# Fix storage permissions
docker-compose exec app chmod -R 755 storage bootstrap/cache

# Or rebuild with proper ownership
docker-compose down
docker-compose up -d --build
```

### Xdebug Not Working

1. **Check VS Code is listening:** Debug tab should show "listening on port 9003"
2. **For Linux:** Update `xdebug.client_host` in `docker/php.ini` to `172.17.0.1`
3. **Rebuild:** `docker-compose up -d --build`
4. **Test:** `docker-compose logs app | grep xdebug`

### Out of Memory

```bash
# Increase Docker memory (Docker Desktop settings)
# Or reduce PHP memory limit in docker/php.ini:
memory_limit = 1G

# Rebuild
docker-compose up -d --build
```

## 📊 Performance Tips

1. **Use named volumes** for better performance on Mac/Windows
2. **Enable Opcache** (already enabled in php.ini)
3. **Index frequently queried columns** in PostgreSQL
4. **Use Redis** for sessions and cache (already configured)
5. **Monitor with:** `docker stats` or `make docker-stats`

## 🚀 Production Considerations

For production deployment:

1. **Use environment variables** instead of hardcoded values
2. **Disable Xdebug** in production (`xdebug.mode = off`)
3. **Increase PHP memory** as needed (set via `memory_limit`)
4. **Configure PostgreSQL** with proper backups and replication
5. **Use Redis** with persistence enabled
6. **Set up** proper logging and monitoring
7. **Use secrets management** for sensitive data

## 📚 Additional Resources

- **DOCKER_SETUP.md** - Complete detailed guide
- **CODING_GUIDELINES.md** - Development standards
- **README.md** - Project overview
- [Docker Documentation](https://docs.docker.com/)
- [Laravel Documentation](https://laravel.com/docs)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)

## ⚡ Performance Notes

- **PHP Image:** Alpine-based (~130MB)
- **Total Services:** ~800MB (with PostgreSQL, Redis, Meilisearch)
- **Startup Time:** ~5 seconds (all services ready for requests)
- **Compilation:** ~1 minute (first build, ~500MB cached layers)
- **Opcache:** Enabled (significant performance boost)
- **Xdebug:** Minimal overhead in development mode

## 💡 Pro Tips

1. **Use Make commands** instead of docker-compose for shorter commands
2. **Pin service versions** in docker-compose.yml for reproducibility
3. **Use `.env` file** for sensitive data (not in git)
4. **Create backups regularly** with `make db-backup`
5. **Monitor logs** with `make logs` while developing
6. **Keep images updated:** `docker-compose pull`

---

**Last Updated:** November 12, 2025  
**For detailed information:** See DOCKER_SETUP.md
