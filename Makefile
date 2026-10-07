# Single entry point for commands (decision record 29). PHP tools run in the development container
# (ADR-0002); process tools come from mise. `make help` lists the targets.
.DEFAULT_GOAL := help

DC     := docker compose
DC_DEV := docker compose -f compose.yaml -f compose.dev.yaml
PHP    := $(DC_DEV) exec -T app php
MISE   := mise exec --
PHASE  ?= off
FILE   ?=

.PHONY: help setup up dev down dev-running test stan cs cs-fix deptrac audit spec roadmap-check forms-check md md-fix complexity taint mutation hooks-test check \
        fix-file lint-file stop-check pre-commit

help: ## List targets
	@grep -E '^[a-z-]+:.*## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*## "} {printf "  %-12s %s\n", $$1, $$2}'

setup: ## Prepare this machine: tools from mise.lock, git hooks (safe to re-run)
	mise install
	git config core.hooksPath .githooks
	@# bwrap and socat return with the sandbox (DEBT-001-sandbox-disabled).
	@for tool in docker git gh claude; do \
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

# The dead-code rule reads the compiled container to see services as used (phpstan.neon, containerXmlPath).
container-xml: dev-running
	@$(PHP) bin/console cache:warmup --env=dev --quiet

stan: container-xml ## PHPStan at the strictest level
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

openapi: dev-running ## Regenerate the committed contract docs/api/openapi.yaml from code (ADR-0003)
	$(PHP) bin/console nelmio:apidoc:dump --format=yaml > docs/api/openapi.yaml

openapi-check: dev-running ## Contract drift: docs/api/openapi.yaml differs from a fresh dump (ADR-0003)
	$(PHP) bin/console nelmio:apidoc:dump --format=yaml | diff -u docs/api/openapi.yaml - \
		|| { echo "docs/api/openapi.yaml is stale: run 'make openapi' and review the contract change"; exit 1; }

roadmap-check: ## Roadmap statuses match the OpenSpec changes; change order (decision record 24, FAIL-005)
	python3 scripts/roadmap.py --check

forms-check: ## Form of OpenSpec changes, review briefs and ADRs (decision records 07, 12; FAIL-006)
	python3 scripts/forms.py --check

complexity: container-xml ## Cognitive complexity report; does not fail until IMP-001 sets the threshold
	$(PHP) vendor/bin/phpstan analyse -c phpstan-complexity.neon --no-progress --memory-limit=512M

taint: dev-running ## Psalm taint analysis: request input reaching SQL, HTML or shell (decision record 14)
	$(PHP) vendor/bin/psalm --taint-analysis --no-progress

BASE ?= origin/main
mutation: dev-running ## Infection on lines changed against BASE; report only until a threshold is set (IMP-008)
	$(PHP) vendor/bin/infection --threads=max --no-progress --git-diff-lines --git-diff-base=$(BASE) $(INFECTION_ARGS)

LYCHEE_EXCLUDES := --exclude-path vendor --exclude-path node_modules --exclude-path var

md: ## Markdown style and local links (decision records 19, 23); external links are checked in CI
	$(MISE) markdownlint-cli2 "**/*.md"
	$(MISE) lychee --offline --no-progress $(LYCHEE_EXCLUDES) './**/*.md'

# Auto-fix never rewrites ask-protected instruction files: they change only through Edit (FAIL-007).
md-fix: ## Fix markdown style automatically, except agent instructions
	$(MISE) markdownlint-cli2 --fix "**/*.md" "#AGENTS.md" "#CLAUDE.md" "#.claude/**"

hooks-test: ## Tests of the Claude Code hooks and of the git-hook scripts
	python3 -m unittest discover -s .claude/hooks/tests -q
	python3 -m unittest discover -s scripts/tests -q

check: cs stan deptrac test audit openapi-check spec roadmap-check forms-check md hooks-test ## Everything the pre-commit hook runs (decision record 04)

fix-file: dev-running ## Format one file (FILE=path); called after each edit by the Claude Code hook
	$(PHP) vendor/bin/php-cs-fixer fix --quiet $(FILE)

lint-file: container-xml ## Static analysis of one file (FILE=path); called by the Claude Code hook
	$(PHP) vendor/bin/phpstan analyse --no-progress --memory-limit=512M --error-format=raw $(FILE)

# In phase `tests` only new or changed tests may fail (decision record 04); otherwise everything is green.
stop-check: ## End-of-turn check of the Claude Code Stop hook (PHASE=off|tests|impl|refactor)
ifeq ($(PHASE),tests)
	$(MAKE) --no-print-directory cs stan deptrac spec roadmap-check forms-check md
	python3 scripts/stop_check.py unittest .claude/hooks/tests scripts/tests
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
