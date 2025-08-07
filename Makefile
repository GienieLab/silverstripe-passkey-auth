# Passkey Auth Module Makefile
# Integrates with main project build system

ARGS = $(filter-out $@,$(MAKECMDGOALS))
MODULE_DIR = passkey-auth

# Color codes for output
GREEN = \033[0;32m
YELLOW = \033[1;33m
RED = \033[0;31m
NC = \033[0m # No Color

.DEFAULT_GOAL := help

help: ## Show this help message
	@echo "$(GREEN)Passkey Auth Module Commands:$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(YELLOW)%-15s$(NC) %s\n", $$1, $$2}'

install: ## Install dependencies
	@echo "$(GREEN)Installing Yarn dependencies...$(NC)"
	yarn install
	@echo "$(GREEN)Dependencies installed successfully!$(NC)"

upgrade: ## Upgrade all dependencies to latest versions
	@echo "$(YELLOW)Upgrading dependencies...$(NC)"
	yarn upgrade --latest
	@echo "$(GREEN)Dependencies upgraded successfully!$(NC)"

build: ## Build production assets
	@echo "$(GREEN)Building production assets...$(NC)"
	yarn build
	@echo "$(GREEN)Build completed successfully!$(NC)"

build-dev: ## Build development assets
	@echo "$(GREEN)Building development assets...$(NC)"
	yarn build:dev
	@echo "$(GREEN)Development build completed!$(NC)"

watch: ## Watch for changes and rebuild automatically
	@echo "$(GREEN)Starting watch mode...$(NC)"
	yarn watch

dev: ## Start development server with hot reload
	@echo "$(GREEN)Starting development server...$(NC)"
	yarn dev

clean: ## Clean build directory
	@echo "$(YELLOW)Cleaning build directory...$(NC)"
	yarn clean
	@echo "$(GREEN)Build directory cleaned!$(NC)"

lint: ## Run code linting (JS + SCSS)
	@echo "$(GREEN)Running linters...$(NC)"
	yarn lint

lint-fix: ## Run linting with auto-fix
	@echo "$(GREEN)Running linters with auto-fix...$(NC)"
	yarn lint:js --fix || true
	yarn lint:scss --fix || true

test: ## Run unit tests
	@echo "$(GREEN)Running tests...$(NC)"
	yarn test

test-watch: ## Run tests in watch mode
	@echo "$(GREEN)Running tests in watch mode...$(NC)"
	yarn test --watch

coverage: ## Generate test coverage report
	@echo "$(GREEN)Generating coverage report...$(NC)"
	yarn test --coverage

analyze: ## Analyze bundle size
	@echo "$(GREEN)Analyzing bundle size...$(NC)"
	yarn analyze

setup: install build ## Complete setup (install + build)

reset: clean install build ## Reset module (clean + install + build)

ci: install lint test build ## CI pipeline (install + lint + test + build)

production-ready: clean install lint test build ## Prepare for production
	@echo "$(GREEN)✅ Module is production ready!$(NC)"

# Integration with main project
sync-from-main: ## Sync dependencies from main project
	@echo "$(YELLOW)Syncing from main project...$(NC)"
	@if [ -f "../package.json" ]; then \
		echo "Main project package.json found"; \
	else \
		echo "$(RED)Main project package.json not found$(NC)"; \
	fi

.PHONY: help install upgrade build build-dev watch dev clean lint lint-fix test test-watch coverage analyze setup reset ci production-ready sync-from-main

%:
	@:
