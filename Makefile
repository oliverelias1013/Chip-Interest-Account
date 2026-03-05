.PHONY: install test docker-build docker-test clean help

help: ## Show available commands
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

install: ## Install dependencies
	composer install

test: ## Run tests locally
	vendor/bin/phpunit

docker-build: ## Build Docker image
	docker compose build

docker-test: ## Run tests inside Docker container
	docker compose run --rm app vendor/bin/phpunit

clean: ## Remove vendor directory and cache
	rm -rf vendor .phpunit.cache