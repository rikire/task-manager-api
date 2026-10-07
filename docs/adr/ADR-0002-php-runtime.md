# ADR-0002: PHP runtime and start-up — FrankenPHP (classic mode), migrations as a one-shot service

- **Status:** active, 2026-10-06
- **Kind:** architecture
- **Decided by:** project owner
- **Drafted by:** agent

> Reading notes: "decision record NN" is `docs/pre-init/NN-*.md`. `CON-…` IDs are defined in
> `docs/constraints.md`, `QAS-…` in the `quality` spec (`openspec/changes/architecture-kickoff/specs/quality/spec.md`,
> `openspec/specs/quality/spec.md` after archive), `RUL-…` in `.claude/rules/code.md`. "The skeleton" is
> step 8 of `docs/pre-init/35-init-checklist.md`: the empty Symfony application running in Docker.

## Context and drivers

- `QAS-DEPLOY-clean-clone-start` — a fresh clone starts with at most 2 commands (`git clone …`,
  `docker compose up -d`) and no manual steps on any machine with Docker (amd64 or arm64); the database may not be ready yet; no migrations may exist yet; a failed migration
  stops start-up with a non-zero exit, is not re-run, and the API is not served on an unmigrated schema.
- `QAS-DEPLOY-prod-image` — one production image without development dependencies and with debugging off,
  configured only by environment variables; a missing required variable fails start-up.
- `CON-STACK-php-symfony-pg` — Docker Compose; PHP version from ADR-0001.
- `CON-DELIV-explain-without-ai` — the owner explains and changes the setup alone.
- Owner decisions (2026-10-06): database readiness through a `pg_isready` healthcheck and
  `depends_on: condition: service_healthy`; no retry loop; the setup does not depend on the reviewer's
  machine; database credentials are non-secret dev defaults in `compose.yaml`.
- No performance scenario exists. Deploying to a server is out of scope (`docs/task/assignment.txt:141`);
  the production image is a driver (`QAS-DEPLOY-prod-image`).
- Owner decisions (2026-10-06): FrankenPHP over Apache and the PHP built-in server; image version 1.13.

Facts (checked 2026-10-06):

| Fact | Source |
|---|---|
| FrankenPHP 1.13.0 (2026-10-04), stable; image `dunglas/frankenphp:1.13-php8.5` | https://github.com/php/frankenphp/releases |
| Symfony's Docker page points to `dunglas/symfony-docker` (FrankenPHP, worker mode, automatic HTTPS) | https://symfony.com/doc/current/setup/docker.html |
| Symfony's web server page covers FrankenPHP/Caddy, Nginx + PHP-FPM and Apache 2.4 with `mod_proxy_fcgi` (PHP-FPM); Apache with `mod_php` is not covered; `symfony/apache-pack` (`.htaccess`) is "for quick tests", production moves the rules into the vhost | https://symfony.com/doc/current/setup/web_server_configuration.html |
| FrankenPHP 1.13.0 fixes 5 CVEs (2 high) | https://github.com/php/frankenphp/releases |
| "Since Symfony 7.4, FrankenPHP worker mode is natively supported"; worker mode keeps state between requests; Doctrine connections can go stale after DB idle timeouts | https://frankenphp.dev/docs/symfony/, https://frankenphp.dev/docs/worker/, https://github.com/doctrine/dbal/pull/6351 |
| `dunglas/frankenphp:1.13-php8.4` (2026-10-04) and `1.12-php8.4` (2026-09-25), `php:8.5-fpm`, `php:8.5-apache`, `postgres:18-alpine` are published for amd64 and arm64 | Docker Hub API, `/v2/repositories/<repo>/tags/<tag>` |
| `service_completed_successfully`: "a dependency is expected to run to successful completion before starting a dependent service"; what `up` returns when it does not is not documented there; default restart policy is `no` | https://docs.docker.com/reference/compose-file/services/ |
| Recommended readiness: `healthcheck` with `pg_isready` + `depends_on: condition: service_healthy` | https://docs.docker.com/compose/how-tos/startup-order/ |
| `doctrine:migrations:migrate --no-interaction --allow-no-migration` exits 0 with no migrations or nothing pending; without `--allow-no-migration` and no migrations it exits 1 | doctrine/migrations 3.9 `MigrateCommand` source |

## Considered options

**Web server / PHP:**

1. **PHP-FPM + nginx** — three containers (php-fpm, nginx, postgres); nginx config written by hand.
2. **Apache.** In the form Symfony documents — Apache with `mod_proxy_fcgi` and PHP-FPM — three
   containers (apache, php-fpm, postgres) plus a vhost and an FPM pool. In the simple form — the official
   `php:<version>-apache` image with `mod_php` — two containers, but this form is not covered by the Symfony
   documentation and needs `.htaccess` rewrite rules (`symfony/apache-pack`) or a vhost.
3. **FrankenPHP, classic mode** — two containers (app with built-in Caddy, postgres); one request = one
   fresh PHP execution, as with FPM; minimal Caddyfile, plain HTTP.
4. **FrankenPHP, worker mode** — as 3, the kernel stays in memory between requests.
5. **PHP built-in server (`php -S`)** — one process, single-threaded, documented by PHP as a development
   server.
6. **`dunglas/symfony-docker` as is** — rejected before comparison: worker mode, automatic HTTPS with a
   local certificate (the reviewer's `curl` would need `-k`).

**Where migrations run:**

- **A. App entrypoint** runs the migration, then starts the server.
- **B. One-shot `migrate` service** from the same image, `restart: "no"`; the app has
  `depends_on: migrate: condition: service_completed_successfully`.

## Trade-offs

| Attribute | 1. FPM + nginx | 2. Apache | 3. FrankenPHP classic | 4. FrankenPHP worker | 5. built-in server |
|---|---|---|---|---|---|
| Start-up simplicity (`QAS-DEPLOY`) | − 3 containers, 2 configs | ± documented form: 3 containers, vhost + FPM pool; `mod_php` form: 2 containers + rewrite rules | + 2 containers, 1 short Caddyfile | + as 3 | + 1 process |
| Covered by the Symfony docs the owner may use (`CON-DELIV-explain-without-ai`) | + | ± only with PHP-FPM | + | + | − |
| Familiar without an assistant | + widespread | + widespread | ± newer, little configuration to learn | − state between requests | + trivial |
| Request semantics | + no shared state | + no shared state | + no shared state | − leaked state, stale connections | + no shared state |
| Suitable beyond local dev | + | + | + | + | − single-threaded, dev only |
| Production image (`QAS-DEPLOY-prod-image`) | ± two images to build and configure | ± one image; documented form needs a second (FPM) | + one image serves HTTP and PHP | + as 3 | − a development server in the production image |
| Extra dependency | — | `symfony/apache-pack` | — | — | — |

| Attribute | A. entrypoint | B. one-shot service |
|---|---|---|
| Failed migration visible to the reviewer | − `up -d` returns 0, the app container exits later | + `up -d` exits 1: `service "migrate" didn't complete successfully` (checked on the skeleton, 2026-10-07) |
| Not restarted automatically after failure | ± depends on the app's restart policy | + `restart: "no"` on its own service |
| API never served on an unmigrated schema | + server starts after migration | + app starts only after success |
| Moving parts | + one service | − one more service (same image) |

Sensitivity points: worker mode is the only option that changes request semantics; migration placement
decides how a failure is reported. Trade-off point: option 5 is the simplest to start but is a
development server only.

## Decision and rationale

- **FrankenPHP in classic mode** (option 3), image `dunglas/frankenphp:1.13-php8.4` pinned by digest
  (PHP from ADR-0001), plain HTTP — the image's default Caddyfile with `SERVER_NAME=:80`, no own Caddyfile (Caddy is the web
  server built into FrankenPHP); no `worker` directive. 1.13 was released two days
  before this decision: decision record 15's 7-day cooldown on new releases is set aside for it because 1.13 fixes CVEs
  that 1.12 has (owner, 2026-10-06).
- **Migrations in a one-shot `migrate` service** (B): same image, command
  `php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration`, `restart: "no"`;
  the app `depends_on` it with `service_completed_successfully`; `migrate` `depends_on` the database with
  `service_healthy`.
- **Database:** `postgres:18-alpine`; healthcheck `pg_isready` with `interval: 10s`, `timeout: 10s`,
  `retries: 5`, `start_period: 30s` (the Docker documentation example) — a database that never becomes
  healthy fails `up` after about 80 s; credentials as dev defaults in
  `compose.yaml` (`${POSTGRES_PASSWORD:-…}`), marked dev-only.
- **Host port:** `${HTTP_PORT:-8080}`, documented in README, so a busy port is changed without editing
  files.
- **Image targets:** one multi-stage Dockerfile with a `prod` target (`composer install --no-dev`;
  `APP_ENV=prod` and `APP_DEBUG=0` fixed in the image as constants of the target, owner 2026-10-07) and a
  `dev` target (development dependencies and the tools of decision record 32).
- **Compose files:** `compose.yaml` builds the `prod` target — this is what the reviewer's
  `docker compose up -d` runs, and `migrate` uses the same image. `compose.dev.yaml` switches **both** `app`
  and `migrate` to the `dev` target with the source mounted; `vendor/` stays in an anonymous volume filled
  from the dev image (rebuild after changing dependencies). Used for development and for running the tools
  (`docker compose -f compose.yaml -f compose.dev.yaml …`, wrapped by a Makefile target). Not
  `compose.override.yaml`: Compose loads that file automatically, which would give the reviewer the
  development build (owner, 2026-10-07).
- **Configuration:** every environment-specific setting comes from environment variables. Required in the
  `prod` image: `DATABASE_URL`, `APP_SECRET` (the list grows with the code); `compose.yaml` supplies
  non-secret dev defaults for them. The image contains no `.env` values: dotenv loading is disabled in the
  `prod` image (the Symfony Runtime option is confirmed at skeleton time) and `composer dump-env` is not
  used. Symfony reads environment variables lazily, so the **container entrypoint** checks the required
  list before PHP starts (`: "${DATABASE_URL:?DATABASE_URL is required}"` per variable) and exits
  non-zero naming the missing one — for `app` and `migrate` alike (owner, 2026-10-07;
  `RUL-CODE-fail-fast`).
- No restart policy on any service (Compose default `no`).

Rationale: `QAS-DEPLOY-clean-clone-start` favours the fewest moving parts on any machine; among the
options covered by the Symfony documentation (`CON-DELIV-explain-without-ai`), FrankenPHP has the fewest:
two containers and a few lines of Caddyfile, against three containers, a vhost and an FPM pool for the
documented Apache form. The owner chose FrankenPHP over Apache, and the drivers favour it for the reasons above;
the built-in server is rejected by the owner's judgement — a server PHP documents as development-only in the submitted
project — not by a driver. Classic mode keeps one-request-one-process
semantics; worker mode has no driver (YAGNI) and stays a one-directive change with known costs. A
one-shot migration service with `restart: "no"` never re-runs a failed migration and keeps the app from
starting on an unmigrated schema; that `up` itself reports the failure is expected but not documented,
so the Confirmation checks it; `--allow-no-migration` makes a clean start succeed before the
first migration exists.

## Consequences

- Plus: 3 services (app, migrate, db), 2 images, all multi-arch; failures surface at `up`; the reviewer
  runs the same build that would go to production.
- Minus: development needs the extra `-f compose.dev.yaml` (hidden behind a Makefile target).
- Minus: Caddy/FrankenPHP is less familiar than nginx or Apache; one extra service definition.
- The app image must contain the console and the migrations (same image for app and migrate).

## Confirmation

| Scenario | Check |
|---|---|
| `QAS-DEPLOY-clean-clone-start.fresh-clone` | CI job `clean-clone`: fresh clone, `docker compose up -d`, then `GET /api/statuses` answers 200 once the endpoint exists (on the skeleton: any HTTP response from the app, 404 included); a second `up` on the same volume succeeds. Until the job exists — a manual run in the skeleton's review brief |
| `…database-not-ready` | CI job `clean-clone` (the database and the app start together); review: healthcheck and `depends_on` as decided |
| `…no-migrations-yet` | once on the skeleton, before the first migration exists; output in the skeleton's review brief |
| `…migration-fails` | once on the skeleton with a deliberately failing migration: `docker compose up -d` exits non-zero, the app container is not started, nothing restarts automatically; output in the review brief |
| `QAS-DEPLOY-prod-image.env-only` | CI job `clean-clone` runs the `prod` target (the reviewer's path); in the image `bin/console about` shows environment `prod` and debug off, and `vendor/phpunit` does not exist |
| `QAS-DEPLOY-prod-image.no-internals` | once the first database-backed endpoint exists: with the database stopped, `GET /api/statuses` on the `prod` image answers 500 without a stack trace; output in that change's review brief |
| `QAS-DEPLOY-prod-image.missing-variable` | once on the skeleton: start the `prod` image without `DATABASE_URL` — non-zero exit from the entrypoint, the variable named in the log; output in the review brief |

Review: no `worker` directive in the Caddyfile; no `restart:` other than `"no"`; no default for a required
variable inside the image.

## Retires

Nothing.

## Revisit-when

- A `QAS-PERF-…` scenario appears that classic mode cannot meet → worker mode, with a decision on
  resetting services and Doctrine connections.
- Compose does not report a failed `service_completed_successfully` dependency at `up` as expected → move
  migrations into the entrypoint (A).
- A blocking FrankenPHP defect → option 2 or 1.
