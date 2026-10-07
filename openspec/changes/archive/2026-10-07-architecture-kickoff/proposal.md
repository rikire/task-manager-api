# Proposal: architecture-kickoff

## Why

The stack decisions (versions, how PHP runs, API contract approach, ORM, validation) determine what the
application skeleton looks like, so they have to be made before checklist steps 8–11 (`docs/pre-init/35-init-checklist.md`). Their drivers — quality scenarios with measures — come first. The rest of the architecture start
(security baseline, dependency rules, architecture description) needs the skeleton to exist and follows
it (checklist step 12, order changed by the owner on 2026-10-06).

## What Changes

**Task group 1 — drivers and stack decisions, before the skeleton**

- Delta spec of the new capability `quality` with five quality scenarios, each with its source line in
  `docs/task/assignment.txt` or the owner's decision, stimulus, artifact, environment, response and measure:
  `QAS-DEPLOY-clean-clone-start`, `QAS-DEPLOY-prod-image`,
  `QAS-MAINT-layering`, `QAS-MAINT-typing`, `QAS-MAINT-readability`, `QAS-MAINT-readme-matches-code`.
  They are the drivers of the ADRs below, so they come first (decision record 11 §2 updated for the
  changed order).

- Six ADRs drafted with the `architecture` skill, drivers cited as `CON-…` (`docs/constraints.md`) and
  `QAS-…`:
  - `ADR-0001-platform-versions` — Symfony, PHP and PostgreSQL versions;
  - `ADR-0002-php-runtime` — how PHP is served (FrankenPHP / PHP-FPM + nginx / Apache), database
    readiness and migrations on start;
  - `ADR-0003-api-contract` — how the OpenAPI contract is produced and checked against the code;
  - `ADR-0004-orm` — Doctrine ORM and migrations or an alternative;
  - `ADR-0005-validation` — Symfony Validator or an alternative;
  - `ADR-0006-module-structure` — architectural style and module/layer structure (the Deptrac rules in
    group 2 follow it; README must explain the structure, `CON-DELIV-readme-sections`).
- The existing drafts of ADR-0001…0003 are rewritten: drivers `D1…D8` replaced with real IDs
  (FAIL-002); the 13 findings of the first `architecture-reviewer` review are presented to the owner in
  the review brief and the accepted ones applied; project support horizon is not a driver.
- Each ADR is reviewed by `architecture-reviewer` before the owner decides; accepted by a commit.

**Task group 2 — checklist step 12, after steps 8–11**

- Selection of OWASP ASVS level 1 requirements that apply to this API (`docs/pre-init/14-code-security.md`).
- Deptrac layer rules and `RUL-ARCH-…` rules; `Source:` of `RUL-ARCH-thin-controllers` moved to
  `QAS-MAINT-layering`.
- `docs/architecture/README.md` (reduced arc42, `docs/pre-init/11-architecture-design.md`): quality
  goals, a link to `docs/constraints.md`, stack table with ADR links, building blocks, cross-cutting
  concepts.

## Out of scope

- Product behaviour: requirements for tasks and statuses (`REQ-TASK-…`, `REQ-STATUS-…`) — separate
  changes.
- The skeleton, Makefile, git hooks, CI and the docs skeleton (checklist steps 8–11) — `chore/` work
  between task groups 1 and 2, built on the accepted ADRs.
- Stack-specific style guides and skills (checklist step 13).

## Capabilities

### New Capabilities

- `quality` — quality attribute scenarios (`QAS-<ATTR>-<slug>`) with measurable response measures;
  written in task group 1 as the drivers of the ADRs.

### Modified Capabilities

None.

## Impact

- New: `docs/adr/ADR-0001…0006`, `docs/registers/debt.md` (IMP for the readability threshold), `openspec/specs/quality/` (on archive), `deptrac.yaml`.
- Modified: `docs/architecture/README.md` (skeleton from checklist step 11, filled in here);
  `.claude/rules/code.md` (`RUL-ARCH-…`, separate instruction commit); `docs/constraints.md`
  (`Affects:` gains the ADRs that cite each entry); `docs/task/README.md` (coverage rows point to the
  final IDs); `docs/roadmap.md` (entry for this change).
- Dependencies are proposed in the ADRs and approved by the owner at skeleton time; none are installed
  by this change.

## Roadmap

`docs/roadmap.md` does not exist yet: the entry is added in the checklist step 11 `chore/` work, and the
link is inserted here before task group 2 starts.

## Coverage

| Category | Status | Rationale |
|---|---|---|
| Scope | clear | the owner set both task groups and their order (2026-10-06) |
| Data | clear | no data schema in this change; the ORM choice is ADR-0004, the schema comes with product changes |
| Edge cases and failures | partial | startup details are Open question 2;  startup with the database not ready: healthcheck + `depends_on: service_healthy`; a failed migration stops the container with the error in the logs (owner, 2026-10-06) |
| Constraints | clear | `docs/constraints.md`, all entries confirmed by the owner |
| Terminology | clear | `docs/pre-init/36-constraints-and-ids.md` (REQ / QAS / CON / RUL) |
| Non-functional | clear | measures confirmed; the readability threshold is deferred to `IMP-001-readability-threshold` |
| Done criteria | partial | group 1: `quality` delta spec with five scenarios and six ADRs accepted by commit, merged by its own PR; group 2: Deptrac rules per ADR-0006 with 0 violations, `RUL-ARCH-thin-controllers` `Source:` = `QAS-MAINT-layering`, `docs/architecture/README.md` sections per decision record 11 §1, ASVS selection (content: Open question 2) |

## Corner cases

This change has no request-processing behaviour, so the input × dimension matrix applies only to
system startup:

| Input | Dimension | Expected behaviour |
|---|---|---|
| database at app start | state: not ready | app container waits for the healthcheck |
| migration at app start | state: fails | container exits non-zero, error in logs; no retry loop |
| fresh clone | environment: only Docker on the host | `docker compose up -d` brings the API up (`QAS-DEPLOY-clean-clone-start`) |
| database at app start | state: never becomes healthy | `docker compose up` fails after the healthcheck retries; retries and timeout — ADR-0002 |
| second `up` on an existing volume | state: migrations already applied | the migration step exits 0 when nothing is pending; the API starts |
| fresh start before the first product change | state: no migrations exist yet | the migration step exits 0; the API starts (with Doctrine this needs `--allow-no-migration`: without it the command exits 1 — read in doctrine/migrations 3.9 source by the researcher) |
| host port | state: already in use | Open question 2 |
| database credentials | trust: public repository | non-secret dev defaults in `compose.yaml` (Confirmed) |
| migration on start | where it runs, restart policy | Open question 2 |

## Confirmed

- Stack ADRs come first, inside this change, before checklist steps 8–11; ASVS, Deptrac and the
  architecture description follow the skeleton in the same change.
- Separate ADRs for Doctrine ORM and Symfony Validator among the stack ADRs.
- Project support horizon is not a driver for the version choice.
- Database readiness: `pg_isready` healthcheck + `depends_on: condition: service_healthy`; no retry loop
  in the entrypoint; a failed migration stops the container with the error in the logs.
- Quality scenarios are written in task group 1 as the ADRs' drivers (later owner decision, replacing
  "quality scenarios after the skeleton"). Measures:
  `QAS-DEPLOY-clean-clone-start` — on a clean clone with only Docker, at most 2 commands, no manual
  steps, then `GET /api/statuses` answers 200 (time not limited: image build time is unpredictable);
  `QAS-MAINT-layering` — 0 Deptrac violations in CI; `QAS-MAINT-typing` — PHPStan at max level, 0 errors,
  no baseline; `QAS-MAINT-readability` — cognitive complexity threshold set after measurement on the first
  product change (an `IMP` entry holds it); `QAS-MAINT-readme-matches-code` — every architecture item in README links an active
  ADR, and every ADR's Confirmation passes.
- `QAS-MAINT-layering` is stated without reference to ADR-0006: HTTP entry points do not depend on the
  persistence layer or the database connection, no dependency cycles; "no business logic in controllers"
  stays a `RUL-ARCH` rule checked in review.
- `QAS-MAINT-typing` covers application code and tests; inline suppressions only with a `DEBT-`/`IMP-` ID.
- `QAS-MAINT-readme-matches-code`: each README architecture item links an active ADR, a `REQ-` or a
  `DEBT-`/`IMP-` entry; a link to a deprecated ADR is a failure (decision record 12 §6 amended); only
  machine-checkable Confirmations fail CI.
- A clean start must succeed from the skeleton on, before any migration exists.
- The setup does not depend on the reviewer's machine: images support amd64 and arm64, nothing but
  Docker is needed on the host; Compose v2 follows from the assignment's own `docker compose up`
  (line 135).
- The complexity check runs as a separate CI step from the type check.
- The Symfony version is left to the agent's recommendation in ADR-0001.
- Proposal and the `quality` spec accepted by the owner (2026-10-06); remaining questions are decided in
  the ADRs.
- Module structure (2026-10-07): vertical slices on a hexagonal core, two modules Task and Status; the
  cycle between them is broken by a port in Status implemented by Task; `RUL-CODE-yagni` gets an exception
  for the ports of ADR-0006.
- Deployment is an architecture driver (2026-10-07): a sixth quality scenario `QAS-DEPLOY-prod-image`
  (one production image, configuration only through environment variables, no development dependencies,
  debugging off; a missing required variable fails start-up); the reviewer's `docker compose up -d` runs
  the production build; the deployment view goes into `docs/architecture/README.md`.
- Identifiers: UUID v7, generated by the persistence adapter through a `nextId()` port; `APP_ENV` and
  `APP_DEBUG` are constants of the production build; required environment variables are checked in the
  container entrypoint (2026-10-07).
- All of task group 2 is done before the first product change.
- Task group 1 is merged into `main` by its own PR right after the owner accepts the ADRs; the change
  stays open until group 2 and is archived then.
- `ADR-0006-module-structure` is part of task group 1.
- The recommendations in the old drafts (FrankenPHP classic mode, Nelmio code-first OpenAPI 3.0,
  PostgreSQL 18) are proposals; each ADR is decided by the owner.
- Database credentials for the clean clone: non-secret dev defaults in `compose.yaml`
  (`${POSTGRES_PASSWORD:-…}` form), marked dev-only in README, allowed in gitleaks; `.env` stays
  uncommitted (decision record 15).
- Readability, separation of responsibilities, typing and README-matches-code are quality scenarios
  (maintainability), and "starts in one or two commands" is a deployability scenario — not constraints.

## Assumptions

None open.

## Open questions

1. Which ASVS level 1 requirements apply (no authentication in scope, assignment line 141) and where
   they go (`RUL-SEC-…` or requirements) — answered in task group 2. Blocks: group 2.
2. Resolved in ADR-0002: one-shot `migrate` service with `restart: "no"`, no restart policy anywhere,
   host port `${HTTP_PORT:-8080}`, healthcheck values from the Docker documentation.
