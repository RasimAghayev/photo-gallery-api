ENV_FILE := var/.env
COMPOSE  := docker compose --env-file $(ENV_FILE)

.PHONY: up down build test lint audit analyse migrate token logs

up: ## Build and start the stack in the background
	$(COMPOSE) build
	$(COMPOSE) up -d

down: ## Stop the stack
	$(COMPOSE) down

build: ## Build every image without starting the stack
	$(COMPOSE) build

test: ## Run the PHPUnit suite (in-memory SQLite, no network needed)
	$(COMPOSE) --profile test run --rm test

lint: ## Run Laravel Pint in check mode
	$(COMPOSE) --profile test run --rm --entrypoint ./vendor/bin/pint test --test

audit: ## Run composer audit against the lockfile
	$(COMPOSE) --profile test run --rm --entrypoint composer test audit --no-interaction --abandoned=report

analyse: ## Run Larastan static analysis
	$(COMPOSE) --profile test run --rm --entrypoint ./vendor/bin/phpstan test analyse --memory-limit=512M

migrate: ## Run database migrations against the running stack
	$(COMPOSE) exec app php artisan migrate

token: ## Issue a Sanctum token for EMAIL=user@example.com
	$(COMPOSE) exec app php artisan api:token $(EMAIL)

logs: ## Follow the app container's logs
	$(COMPOSE) logs -f app
