# ADR-0001: Platform versions — Symfony 8.1, PHP 8.4, PostgreSQL 18

- **Status:** active, 2026-10-06
- **Kind:** architecture
- **Decided by:** project owner (the owner left the Symfony version to the agent's recommendation)
- **Drafted by:** agent

> Reading notes: "decision record NN" is `docs/pre-init/NN-*.md`. `CON-…` IDs are defined in
> `docs/constraints.md`, `QAS-…` in the `quality` spec (`openspec/changes/architecture-kickoff/specs/quality/spec.md`,
> `openspec/specs/quality/spec.md` after archive), `RUL-…` in `.claude/rules/code.md`. "The skeleton" is
> step 8 of `docs/pre-init/35-init-checklist.md`: the empty Symfony application running in Docker.

## Context and drivers

The assignment fixes the stack family, not the versions.

- `CON-STACK-php-symfony-pg` — PHP 8+, Symfony 6+, PostgreSQL, Docker Compose.
- `CON-DELIV-explain-without-ai` — at the next stage the owner changes the code without an AI assistant;
  documentation is allowed (`docs/task/assignment.txt:155`). The code should match the documentation the
  owner will open.
- `QAS-MAINT-typing` — the static analysis tools must run on the chosen versions; the same holds for the
  other tools of decision record 32.
- Not a driver (owner, 2026-10-06): how long a version is supported.

Facts (checked 2026-10-06):

| Item | Fact | Source |
|---|---|---|
| Symfony 8.1 | stable 8.1.8, PHP ≥ 8.4; supported until 2027-01 | https://symfony.com/releases.json, https://symfony.com/releases |
| Symfony 7.4 | LTS 7.4.20, PHP ≥ 8.2 | same |
| Documentation | `symfony.com/doc/current` is 8.1; `/doc/7.4/` is maintained | https://symfony.com/doc/current/index.html |
| 7.4 → 8.1 | 8.0 removed 7.4's deprecated APIs; 8.1 adds `#[Serialize]`, `mapWhenEmpty` and other features the assignment does not require | https://raw.githubusercontent.com/symfony/symfony/8.0/UPGRADE-8.0.md, https://symfony.com/blog/symfony-8-1-curated-new-features |
| PHP 8.5 / 8.4 | 8.5.11 and 8.4.26; both supported; 8.4 released 2024-11, 8.5 released 2025-11 | https://www.php.net/supported-versions.php |
| PostgreSQL | 18.6 latest; 14–18 supported | https://www.postgresql.org/support/versioning/ |
| Tools (decision record 32), DoctrineBundle 3.3, Nelmio 5.13, DAMA, Foundry | Composer constraints allow Symfony `^8.0` and PHP 8.5; Psalm and PHP-CS-Fixer state 8.5 support; that the others **run** on 8.5 is not verified | Packagist `p2/<vendor>/<pkg>.json` |
| DoctrineBundle 3.x, DoctrineMigrationsBundle 4.x | require PHP ≥ 8.4 | Packagist |

## Considered options

- **Symfony:** 7.4 LTS / 8.1.
- **PHP:** 8.4 / 8.5.
- **PostgreSQL:** 17 / 18.

## Trade-offs

| Attribute | Symfony 7.4 | Symfony 8.1 |
|---|---|---|
| Code matches the documentation opened by default (`CON-DELIV-explain-without-ai`) | ± the 7.4 docs exist, but the owner has to switch the version selector | + `doc/current` is 8.1 |
| Deprecated APIs available to slip into new code | − 7.4 still ships them | + removed in 8.0 |
| Tools allow it (`QAS-MAINT-typing`) | + | + (constraints; run not verified for every tool) |
| Features the assignment needs | + all present | + all present; extras unused |
| Cost to change later | 7.4 code without deprecations runs on 8.x | 8.1 code without 8.x-only features runs on 7.4 |

| Attribute | PHP 8.4 | PHP 8.5 |
|---|---|---|
| Tool support | ± constraints allow; Psalm and PHP-CS-Fixer state support; about two years on the market | ± the same evidence; about one year on the market |
| Language features | − | + |

| Attribute | PostgreSQL 17 | PostgreSQL 18 |
|---|---|---|
| Supported, image published for amd64 and arm64 | + | + |
| Anything the project needs only one has | — | — |

Sensitivity point: the PHP version, because every tool must run on it. Trade-off point: Symfony 8.1
matches the default documentation and drops deprecated APIs, at the cost of fewer third-party examples
than 7.4 (not measured).

## Decision and rationale

Use **Symfony 8.1**, **PHP 8.4**, **PostgreSQL 18**. Pin the framework with `extra.symfony.require:
"8.1.*"` and `require.php: "~8.4.0"` in `composer.json`; pin the PHP minor and PostgreSQL major in the
image tags chosen by ADR-0002; no floating `latest` tags.

Rationale: with support horizon excluded, the deciding driver is `CON-DELIV-explain-without-ai`: the
owner will work from the documentation, and the default documentation is 8.1; removing deprecated APIs
removes a class of mistakes. All tools accept both Symfony versions, so `QAS-MAINT-typing` does not
decide there. For PHP the evidence is the same for both (Composer constraints; Psalm and PHP-CS-Fixer
state support), so the choice is a risk call: 8.4 has been out a year longer, which makes an untested
tool incompatibility less likely (interpretation, not verified); 8.5's language features have no driver. Symfony 8.1 requires PHP ≥ 8.4, so 8.4 is the lowest
version that fits. PostgreSQL 18 is the current release and no driver separates it from 17. All three
are cheap to change (reversible decision).

## Consequences

- Plus: code and documentation match; no deprecated APIs; the PHP version with the longer tool history.
- Minus: Symfony 8.1 support ends 2027-01 (not a driver); fewer community examples than for 7.4.
- The skeleton runs every tool of decision record 32 once on PHP 8.4 before product code.

## Confirmation

- `composer.json`: `extra.symfony.require` and `require.php` as above; image tags in the files of
  ADR-0002 — review.
- Skeleton (checklist step 8): each tool of decision record 32 runs on the image without errors — command
  output in the skeleton's review brief.

## Retires

Nothing.

## Revisit-when

- Every tool of decision record 32 states PHP 8.5 support and a driver needs 8.5 → PHP 8.5.
- A required library does not support Symfony 8.1 → Symfony 7.4.
