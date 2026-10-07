# syntax=docker/dockerfile:1
# FrankenPHP 1.13 in classic mode (no worker), PHP 8.4; targets `prod` and `dev` (ADR-0001, ADR-0002).

FROM dunglas/frankenphp:1.13-php8.4@sha256:2fd4a848c05bf4902a3d9ff1a4991c2f4d4665df0567aee21be4eee120ded038 AS base
WORKDIR /app
RUN install-php-extensions pdo_pgsql zip
COPY --from=composer:2@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac /usr/bin/composer /usr/local/bin/composer
COPY --chmod=0755 docker/entrypoint.sh /usr/local/bin/app-entrypoint
# The image's default Caddyfile serves plain HTTP when the site address is a bare port; the extra directive
# keeps browsers from sniffing a response into another type (ASVS V3.2.1, docs/architecture/asvs-l1.md).
ENV SERVER_NAME=:80 \
    CADDY_SERVER_EXTRA_DIRECTIVES="header X-Content-Type-Options nosniff"
ENTRYPOINT ["app-entrypoint"]
CMD ["--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]

FROM base AS prod
# Constants of this target (ADR-0002); every other setting comes from the environment.
ENV APP_ENV=prod APP_DEBUG=0
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress --no-interaction
COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative --no-interaction

FROM base AS dev
ENV APP_ENV=dev
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist --no-progress --no-interaction
COPY . .
RUN composer dump-autoload --no-interaction
