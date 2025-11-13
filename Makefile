.PHONY: help install start stop restart build rebuild logs bash test format lint

# Colors for output
BLUE := \033[0;34m
GREEN := \033[0;32m
RED := \033[0;31m
YELLOW := \033[0;33m
NC := \033[0m

help: ## Show this help message
	@echo "$(BLUE)Laravel ERP - Makefile Commands$(NC)"
	@echo ""
	@echo "$(YELLOW)Installation & Setup:$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-20s$(NC) %s\n", $$1, $$2}'
	@echo ""
	@echo "$(YELLOW)Examples:$(NC)"
	@echo "  make install    # Full setup: build + install dependencies"
	@echo "  make start      # Start all services"
	@echo "  make test       # Run all tests"
	@echo "  make bash       # Enter app container shell"
	@echo ""

# ============================================================================
# INSTALLATION & SETUP
# ============================================================================

install: build ## Complete setup: build Docker image and install dependencies
	@echo "$(BLUE)Installing dependencies...$(NC)"
	@docker-compose exec app composer install
	@docker-compose exec app npm install
	@docker-compose exec app artisan migrate:fresh --seed
	@echo "$(GREEN)✓ Installation complete!$(NC)"

build: ## Build Docker image
	@echo "$(BLUE)Building Docker image...$(NC)"
	@docker-compose build
	@echo "$(GREEN)✓ Build complete!$(NC)"

rebuild: stop ## Rebuild Docker image and start services
	@echo "$(BLUE)Rebuilding Docker image...$(NC)"
	@docker-compose down
	@docker-compose up -d --build
	@echo "$(GREEN)✓ Rebuild complete! Services started.$(NC)"
	@echo ""
	@echo "$(YELLOW)Waiting for services to be ready...$(NC)"
	@sleep 5
	@docker-compose logs app | tail -20

# ============================================================================
# SERVICE MANAGEMENT
# ============================================================================

start: ## Start all services
	@echo "$(BLUE)Starting Docker services...$(NC)"
	@docker-compose up -d
	@echo "$(GREEN)✓ Services started!$(NC)"
	@echo ""
	@echo "$(YELLOW)Services:$(NC)"
	@echo "  • App:        http://localhost:8000"
	@echo "  • PgAdmin:    http://localhost:5050"
	@echo "  • MailHog:    http://localhost:8025"
	@echo "  • Redis CLI:  http://localhost:8081"
	@docker-compose ps

stop: ## Stop all services
	@echo "$(BLUE)Stopping Docker services...$(NC)"
	@docker-compose down
	@echo "$(GREEN)✓ Services stopped!$(NC)"

restart: ## Restart all services
	@echo "$(BLUE)Restarting Docker services...$(NC)"
	@docker-compose restart
	@echo "$(GREEN)✓ Services restarted!$(NC)"

ps: ## Show running containers
	@docker-compose ps

# ============================================================================
# LOGS & MONITORING
# ============================================================================

logs: ## View logs for all services
	@docker-compose logs -f

logs-app: ## View app container logs
	@docker-compose logs -f app

logs-db: ## View PostgreSQL logs
	@docker-compose logs -f postgres

logs-redis: ## View Redis logs
	@docker-compose logs -f redis

# ============================================================================
# APPLICATION COMMANDS
# ============================================================================

bash: ## Enter app container shell
	@docker-compose exec app bash

sh: bash ## Alias for bash

artisan: ## Run Laravel Artisan command (usage: make artisan CMD="migrate")
	@docker-compose exec app artisan $(CMD)

composer: ## Run Composer command (usage: make composer CMD="install")
	@docker-compose exec app composer $(CMD)

npm: ## Run npm command (usage: make npm CMD="install")
	@docker-compose exec app npm $(CMD)

npm-dev: ## Watch for changes and rebuild assets
	@docker-compose exec app npm run dev

npm-build: ## Build assets for production
	@docker-compose exec app npm run build

# ============================================================================
# DATABASE COMMANDS
# ============================================================================

migrate: ## Run database migrations
	@echo "$(BLUE)Running migrations...$(NC)"
	@docker-compose exec app artisan migrate
	@echo "$(GREEN)✓ Migrations complete!$(NC)"

migrate-fresh: ## Reset database and run migrations
	@echo "$(BLUE)Resetting database...$(NC)"
	@docker-compose exec app artisan migrate:fresh
	@echo "$(GREEN)✓ Database reset complete!$(NC)"

migrate-fresh-seed: ## Reset database, run migrations, and seed
	@echo "$(BLUE)Resetting database with seed data...$(NC)"
	@docker-compose exec app artisan migrate:fresh --seed
	@echo "$(GREEN)✓ Database reset with seed data!$(NC)"

db-backup: ## Create database backup
	@echo "$(BLUE)Creating database backup...$(NC)"
	@mkdir -p ./backups
	@docker-compose exec postgres pg_dump -U erp_user laravel_erp > ./backups/backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)✓ Backup created!$(NC)"

db-restore: ## Restore database from backup (usage: make db-restore FILE=./backups/backup.sql)
	@echo "$(BLUE)Restoring database from $(FILE)...$(NC)"
	@docker-compose exec -T postgres psql -U erp_user laravel_erp < $(FILE)
	@echo "$(GREEN)✓ Database restored!$(NC)"

tinker: ## Open Laravel Tinker shell
	@docker-compose exec app artisan tinker

# ============================================================================
# TESTING & QUALITY ASSURANCE
# ============================================================================

test: ## Run all tests
	@echo "$(BLUE)Running tests...$(NC)"
	@docker-compose exec app ./vendor/bin/pest
	@echo "$(GREEN)✓ Tests complete!$(NC)"

test-feature: ## Run feature tests only
	@docker-compose exec app ./vendor/bin/pest tests/Feature

test-unit: ## Run unit tests only
	@docker-compose exec app ./vendor/bin/pest tests/Unit

test-coverage: ## Run tests with code coverage
	@docker-compose exec app ./vendor/bin/pest --coverage

test-watch: ## Run tests in watch mode
	@docker-compose exec app ./vendor/bin/pest --watch

lint: format ## Run code linting (alias for format)

format: ## Format code with Pint
	@echo "$(BLUE)Formatting code...$(NC)"
	@docker-compose exec app ./vendor/bin/pint
	@echo "$(GREEN)✓ Code formatted!$(NC)"

format-check: ## Check code formatting without changes
	@docker-compose exec app ./vendor/bin/pint --test

stan: ## Run PHPStan static analysis
	@docker-compose exec app ./vendor/bin/phpstan analyse app database

# ============================================================================
# CACHING & CLEANUP
# ============================================================================

cache-clear: ## Clear all Laravel caches
	@echo "$(BLUE)Clearing caches...$(NC)"
	@docker-compose exec app artisan cache:clear
	@docker-compose exec app artisan config:clear
	@docker-compose exec app artisan view:clear
	@docker-compose exec app artisan route:clear
	@echo "$(GREEN)✓ Caches cleared!$(NC)"

cache-rebuild: ## Rebuild all caches
	@echo "$(BLUE)Rebuilding caches...$(NC)"
	@docker-compose exec app artisan config:cache
	@docker-compose exec app artisan route:cache
	@docker-compose exec app artisan view:cache
	@echo "$(GREEN)✓ Caches rebuilt!$(NC)"

redis-clear: ## Clear Redis cache
	@echo "$(BLUE)Clearing Redis...$(NC)"
	@docker-compose exec redis redis-cli flushall
	@echo "$(GREEN)✓ Redis cleared!$(NC)"

clean: ## Clean up temporary files and caches
	@echo "$(BLUE)Cleaning up...$(NC)"
	@rm -rf storage/logs/*
	@rm -rf storage/framework/cache/*
	@rm -rf storage/framework/sessions/*
	@rm -rf storage/framework/views/*
	@echo "$(GREEN)✓ Cleanup complete!$(NC)"

# ============================================================================
# DEVELOPMENT UTILITIES
# ============================================================================

install-hooks: ## Install Git hooks for development
	@echo "$(BLUE)Installing Git hooks...$(NC)"
	@cp .githooks/* .git/hooks/ 2>/dev/null || true
	@chmod +x .git/hooks/* 2>/dev/null || true
	@echo "$(GREEN)✓ Git hooks installed!$(NC)"

seed: ## Run database seeders
	@echo "$(BLUE)Seeding database...$(NC)"
	@docker-compose exec app artisan db:seed
	@echo "$(GREEN)✓ Database seeded!$(NC)"

seed-class: ## Run specific seeder (usage: make seed-class NAME=UserSeeder)
	@docker-compose exec app artisan db:seed --class=$(NAME)

queue-work: ## Start queue worker
	@docker-compose exec app artisan queue:work

queue-flush: ## Flush all queued jobs
	@docker-compose exec app artisan queue:flush

# ============================================================================
# DOCKER UTILITIES
# ============================================================================

docker-ps: ps ## Show running containers (alias for ps)

docker-stats: ## Show Docker container stats
	@docker stats --no-stream

docker-prune: ## Remove unused Docker images and volumes
	@echo "$(BLUE)Pruning Docker resources...$(NC)"
	@docker system prune -f
	@echo "$(GREEN)✓ Prune complete!$(NC)"

# ============================================================================
# INFORMATION
# ============================================================================

info: ps ## Show application info
	@echo ""
	@echo "$(BLUE)Laravel ERP - Application Information$(NC)"
	@echo "$(YELLOW)Running Services:$(NC)"
	@docker-compose ps
	@echo ""
	@echo "$(YELLOW)Application URLs:$(NC)"
	@echo "  • App:        http://localhost:8000"
	@echo "  • PostgreSQL: localhost:5432"
	@echo "  • Redis:      localhost:6379"
	@echo "  • PgAdmin:    http://localhost:5050"
	@echo "  • MailHog:    http://localhost:8025"
	@echo "  • Meilisearch: http://localhost:7700"
	@echo "  • Redis CLI:  http://localhost:8081"
	@echo ""
	@echo "$(YELLOW)Quick Commands:$(NC)"
	@echo "  make help               - Show all available commands"
	@echo "  make start              - Start all services"
	@echo "  make stop               - Stop all services"
	@echo "  make bash               - Enter app container"
	@echo "  make test               - Run all tests"
	@echo "  make migrate            - Run migrations"
	@echo "  make format             - Format code"
	@echo ""

# ============================================================================
# DEFAULT TARGET
# ============================================================================

.DEFAULT_GOAL := help
