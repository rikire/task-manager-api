#!/bin/sh
# Symfony reads environment variables lazily: without this check the server would start and fail only
# on the first request that needs a missing value. Fail fast instead (ADR-0002, QAS-DEPLOY-prod-image).
set -eu

: "${DATABASE_URL:?DATABASE_URL is required}"
: "${APP_SECRET:?APP_SECRET is required}"

exec docker-php-entrypoint "$@"
