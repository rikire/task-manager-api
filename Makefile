# Single entry point for commands (decision record 29). PHP tools run in the development container
# (ADR-0002); process tools come from mise. `make help` lists the targets.
.DEFAULT_GOAL := help

DC     := docker compose
DC_DEV := docker compose -f compose.yaml -f compose.dev.yaml
PHP    := $(DC_DEV) exec -T app php
MISE   := mise exec --
PHASE  ?= off
FILE   ?=

.PHONY: help setup up dev down dev-running test stan cs cs-fix deptrac audit spec hooks-test check \
        fix-file lint-file stop-check pre-commit

help: ## List targets
	@grep -E '^[a-z-]+:.*## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*## "} {printf "  %-12s %s\n", $$1, $$2}'

setup: ## Prepare this machine: tools from mise.lock, git hooks (safe to re-run)
	mise install
	git config core.hooksPath .githooks
	@for tool in docker git gh claude bwrap socat; do \
		command -v $$tool >/dev/null || echo "hint: '$$tool' not found — see README, section Development"; \
	done
	@echo "Next: run 'claude' once in this folder and accept the workspace trust prompt."

up: ## Start the stack exactly as the reviewer does (production build)
	$(DC) up -d --build

dev: ## Start the development stack (source mounted, development tools)
	$(DC_DEV) up -d --build

down: ## Stop the stack
	$(DC_DEV) down

# Tools need the development image; a running production `app` (after `make up`) is replaced.
dev-running:
	@$(DC_DEV) ps --status running --format '{{.Service}} {{.Image}}' 2>/dev/null \
		| grep -qx 'app task-manager-app:dev' || $(DC_DEV) up -d --build

test: dev-running ## PHPUnit
	$(PHP) vendor/bin/phpunit

stan: dev-running ## PHPStan at the strictest level
	$(PHP) vendor/bin/phpstan analyse --no-progress --memory-limit=512M

cs: dev-running ## Code style check (PHP-CS-Fixer, @Symfony)
	$(PHP) vendor/bin/php-cs-fixer check --diff

cs-fix: dev-running ## Fix code style
	$(PHP) vendor/bin/php-cs-fixer fix

deptrac: dev-running ## Dependency rules between layers and modules (ADR-0006)
	$(PHP) vendor/bin/deptrac analyse --no-progress

audit: dev-running ## Known vulnerabilities in dependencies
	$(DC_DEV) exec -T app composer audit

spec: ## OpenSpec structure
	$(MISE) openspec validate --all

hooks-test: ## Tests of the Claude Code hooks and of the git-hook scripts
	python3 -m unittest discover -s .claude/hooks/tests -q
	python3 -m unittest discover -s scripts/tests -q

check: cs stan deptrac test audit spec hooks-test ## Everything the pre-commit hook runs (decision record 04)

fix-file: dev-running ## Format one file (FILE=path); called after each edit by the Claude Code hook
	$(PHP) vendor/bin/php-cs-fixer fix --quiet $(FILE)

lint-file: dev-running ## Static analysis of one file (FILE=path); called by the Claude Code hook
	$(PHP) vendor/bin/phpstan analyse --no-progress --memory-limit=512M --error-format=raw $(FILE)

# In phase `tests` only new or changed tests may fail (decision record 04); otherwise everything is green.
stop-check: ## End-of-turn check of the Claude Code Stop hook (PHASE=off|tests|impl|refactor)
ifeq ($(PHASE),tests)
	$(MAKE) --no-print-directory cs stan deptrac spec hooks-test
	$(DC_DEV) exec -T app rm -f .phpunit.cache/junit.xml # written by the container's user, not the host's
	-$(PHP) vendor/bin/phpunit --log-junit .phpunit.cache/junit.xml
	python3 scripts/stop_check.py .phpunit.cache/junit.xml
else
	$(MAKE) --no-print-directory check
endif

pre-commit: ## Checks of the git pre-commit hook
	python3 scripts/git_checks.py pre-commit
	$(MISE) gitleaks git --pre-commit --staged --redact --no-banner .
	$(MAKE) --no-print-directory check
