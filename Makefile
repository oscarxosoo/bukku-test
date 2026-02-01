.PHONY: help setup install migrate seed fresh test up down clean routes shell build ide-helper

SAIL = ./vendor/bin/sail

# Default target
help:
	@echo "Available commands:"
	@echo "  make setup      - Complete project setup for new developers"
	@echo "  make build      - Build Sail containers"
	@echo "  make up         - Start Sail containers"
	@echo "  make down       - Stop Sail containers"
	@echo "  make install    - Install composer dependencies"
	@echo "  make migrate    - Run database migrations"
	@echo "  make seed       - Run database seeders"
	@echo "  make fresh      - Fresh migration with seeders"
	@echo "  make test       - Run tests"
	@echo "  make clean      - Clear all caches"
	@echo "  make routes     - Show API routes"
	@echo "  make shell      - Open shell in container"
	@echo "  make logs       - View container logs"
	@echo "  make jwt-secret - Generate JWT secret key"
	@echo "  make ide-helper - Regenerate IDE helper annotations"

# Complete setup for new developers
setup: env install-initial build up jwt-secret migrate seed
	@echo ""
	@echo "=========================================="
	@echo "Setup complete!"
	@echo "App running at: http://localhost"
	@echo "=========================================="

# Copy .env file if not exists
env:
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo ".env file created."; \
	else \
		echo ".env file already exists."; \
	fi

# Install dependencies (first time, before Sail container exists)
install-initial:
	@if [ ! -d "vendor" ]; then \
		docker run --rm \
			-u "$$(id -u):$$(id -g)" \
			-v "$$(pwd):/var/www/html" \
			-w /var/www/html \
			laravelsail/php85-composer:latest \
			composer install --ignore-platform-reqs; \
	else \
		echo "vendor directory exists, skipping initial install."; \
	fi

# Install dependencies (using Sail)
install:
	$(SAIL) composer install

# Build Sail containers
build:
	$(SAIL) build

# Start Sail containers
up:
	$(SAIL) up -d

# Stop Sail containers
down:
	$(SAIL) down

# Generate JWT secret
jwt-secret:
	$(SAIL) artisan jwt:secret --force

# Run migrations
migrate:
	$(SAIL) artisan migrate

# Run seeders
seed:
	$(SAIL) artisan db:seed

# Fresh migration with seeders
fresh:
	$(SAIL) artisan migrate:fresh --seed

# Run tests
test:
	$(SAIL) artisan test

# Clear all caches
clean:
	$(SAIL) artisan cache:clear
	$(SAIL) artisan config:clear
	$(SAIL) artisan route:clear
	$(SAIL) artisan view:clear
	@echo "All caches cleared."

# Show routes
routes:
	$(SAIL) artisan route:list --path=api

# Open shell in container
shell:
	$(SAIL) shell

# View logs
logs:
	$(SAIL) logs -f

# Regenerate IDE helper annotations for models
ide-helper:
	$(SAIL) artisan ide-helper:models --write --no-interaction
