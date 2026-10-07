# ADR-0004: Persistence — Doctrine ORM with Doctrine Migrations

- **Status:** active, 2026-10-06
- **Kind:** architecture
- **Decided by:** project owner
- **Drafted by:** agent

> Reading notes: "decision record NN" is `docs/pre-init/NN-*.md`. `CON-…` IDs are defined in
> `docs/constraints.md`, `QAS-…` in the `quality` spec (`openspec/specs/quality/spec.md`), `RUL-…` in `.claude/rules/code.md`. "The skeleton" is
> step 8 of `docs/pre-init/35-init-checklist.md`: the empty Symfony application running in Docker.

## Context and drivers

- `QAS-MAINT-layering` — HTTP entry points do not depend on the persistence layer or the database
  connection; no dependency cycles between layers.
- `QAS-DEPLOY-clean-clone-start` — migrations run non-interactively on start, including when none exist.
- `CON-STACK-php-symfony-pg` — PostgreSQL; Symfony 8.1 and PHP 8.4 (ADR-0001).
- `CON-DELIV-explain-without-ai` — the owner works from the documentation alone.
- Assignment line 13: "a stably working CRUD and clean migrations" is the minimum if time runs out
  (priority in the roadmap).

Facts (checked 2026-10-06):

| Fact | Source |
|---|---|
| Symfony docs: Doctrine ORM is "the recommended way to work with relational databases" (`symfony/orm-pack`); DBAL is a separate article for low-level access | https://symfony.com/doc/current/doctrine.html |
| doctrine/orm 3.7.4 (PHP ^8.1, Symfony up to ^8.0); doctrine/doctrine-bundle 3.3.2 (PHP ^8.4, Symfony ^6.4–^8.0, DBAL ^4); doctrine/doctrine-migrations-bundle 4.0.1 (PHP ^8.4, Symfony ^6.4–^8.0) | Packagist `p2/doctrine/*.json` |
| doctrine/migrations works on top of DBAL or ORM | https://www.doctrine-project.org/projects/doctrine-migrations/en/stable/reference/introduction.html |
| cycle/orm 2.18.1 is active, but its Symfony bundles (`slince/cycle-bundle` 0.0.3, `slince_2/cycle-bundle` 0.0.1) allow at most Symfony ^7.0 | Packagist |
| DAMA DoctrineTestBundle (runs each test in a rolled-back transaction) and Zenstruck Foundry (test data factories), chosen in decision record 32, require Doctrine | Packagist |

## Considered options

1. **Doctrine ORM 3 + DoctrineBundle 3 + DoctrineMigrationsBundle 4** — domain classes mapped (XML mapping,
   ADR-0006),
   repositories, migrations generated from mapping diffs and reviewed.
2. **Doctrine DBAL + hand-written SQL + doctrine/migrations** — repository classes with SQL queries; no
   entity mapping.
3. **Cycle ORM** — rejected before comparison: no Symfony bundle allows Symfony 8.

## Trade-offs

| Attribute | 1. Doctrine ORM | 2. DBAL + SQL |
|---|---|---|
| Matches the documentation (`CON-DELIV-explain-without-ai`) | + the documented default | ± documented, but as the low-level path |
| Persistence behind one layer (`QAS-MAINT-layering`) | + repository adapters in each module's `Infrastructure/Persistence` | + the same |
| Migrations on start (`QAS-DEPLOY`) | + generated from mapping, run with `--no-interaction --allow-no-migration` | ± written by hand, same command |
| Test tooling from decision record 32 (DAMA, Foundry) | + built for it | − Foundry needs ORM entities |
| Query control, N+1 risk | − lazy loading can hide N+1 (decision record 32 counts queries in tests) | + every query explicit |
| Code volume for CRUD of two entities | + mapping + repository methods | − SQL and hydration by hand |

Sensitivity point: the ORM decides how repositories and entities look, which ADR-0006 builds on.
Trade-off point: the ORM removes hand-written SQL and fits the test tools at the cost of hidden queries,
which the query-count tests of decision record 32 catch.

## Decision and rationale

Use **Doctrine ORM 3** with **DoctrineBundle 3** and **DoctrineMigrationsBundle 4**:

- domain classes (`src/<Module>/Domain`) are mapped with XML files in `src/<Module>/Infrastructure/Persistence` and carry no
  Doctrine attributes (ADR-0006); repository adapters there implement the domain's ports and are the only
  classes that build queries;
- the schema changes only through migrations, generated from the mapping and reviewed before commit;
- start-up runs `doctrine:migrations:migrate --no-interaction --allow-no-migration` (ADR-0002);
- list endpoints get query-count tests (decision record 32).

Rationale: the documented default serves `CON-DELIV-explain-without-ai`; repositories give the single
persistence layer `QAS-MAINT-layering` needs; DAMA and Foundry, already chosen for tests, require it. The
N+1 risk is covered by tests, not by avoiding the ORM.

## Consequences

- Plus: little persistence code; migrations from mapping; test tooling fits.
- Minus: implicit queries (lazy loading) must be watched; Doctrine's unit of work (changes are collected
  and written on `flush()`) is one more concept to explain.
- Dependencies to approve at skeleton time: `doctrine/orm`, `doctrine/doctrine-bundle`,
  `doctrine/doctrine-migrations-bundle` (via `symfony/orm-pack`).

## Confirmation

- Dependency rules of ADR-0006: only the `Persistence` layer depends on Doctrine; `Domain`,
  `Application` and `Http` do not; checked by the dependency-rule check in CI.
- `QAS-DEPLOY-clean-clone-start.no-migrations-yet` holds on the skeleton (ADR-0002 Confirmation).
- Every list endpoint has a test in group `query-count` (decision record 32; one group across ADRs, so it
  is named by what it checks, not by this ADR).

## Retires

Nothing.

## Revisit-when

- A query needs a PostgreSQL feature DQL lacks (for example `ON CONFLICT` or window functions) → DBAL for
  that query inside the repository.
- A `query-count` test fails and a fetch join does not fix it.
