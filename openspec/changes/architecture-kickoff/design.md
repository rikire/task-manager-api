# Design: architecture-kickoff

## Context

See `proposal.md` (Why, What Changes). Drivers: `docs/constraints.md` (`CON-STACK-php-symfony-pg`,
`CON-STACK-rest`, `CON-PLAN-deadline`, `CON-DELIV-explain-without-ai`, `CON-DELIV-readme-sections`) and
the quality scenarios in `specs/quality/spec.md`. Facts for the options were checked on 2026-10-06 by the
`researcher` subagent against Packagist, symfony.com, doctrine-project.org and the Docker docs; each ADR
cites its sources.

## Goals / Non-Goals

- Goal: every stack choice the skeleton depends on is an accepted ADR before checklist steps 8–11.
- Goal: every ADR names its drivers by ID and a Confirmation that a later check can run.
- Non-goal: installing anything. Dependencies named in the ADRs are approved by the owner at skeleton
  time (`docs/pre-init/13-authority.md`).

## Decisions

All six pass the significance checklist (stack, hard-to-replace dependency, module boundaries,
`QAS-` impact), so each is an ADR; this section only indexes them.

| Decision | Drivers | ADR |
|---|---|---|
| Symfony, PHP, PostgreSQL versions | `CON-STACK-php-symfony-pg`, `CON-DELIV-explain-without-ai`, `QAS-MAINT-typing` | `ADR-0001-platform-versions` |
| How PHP is served; image targets and compose files; configuration; database readiness; where migrations run; restart policy; host port; multi-arch images | `QAS-DEPLOY-clean-clone-start`, `QAS-DEPLOY-prod-image`, `CON-STACK-php-symfony-pg` | `ADR-0002-php-runtime` |
| OpenAPI contract: source of truth and how it is checked against the code | `CON-STACK-rest`, `QAS-MAINT-readme-matches-code`, assignment line 116 (bonus) | `ADR-0003-api-contract` |
| Persistence: ORM and migrations | `QAS-MAINT-layering`, `QAS-DEPLOY-clean-clone-start`, `CON-DELIV-explain-without-ai` | `ADR-0004-orm` |
| Input validation and error responses | `CON-STACK-rest`, assignment lines 115, 117, 136 (future `REQ-` scenarios) | `ADR-0005-validation` |
| Architectural style, modules and layers (slices on a hexagonal core, modules Task and Status) | `QAS-MAINT-layering`, `CON-DELIV-readme-sections`, `CON-DELIV-explain-without-ai` | `ADR-0006-module-structure` |

Local decisions:

- **ADR review flow** (Scope: local): each ADR goes to `architecture-reviewer`; its findings go to the
  owner in the review brief, accepted ones are applied; the owner accepts by the commit ("this commit
  accepts ADR-…"). Source: `docs/pre-init/12-adr.md` §3.
- **Dependency-rule test in group 2** (Scope: local): the Deptrac configuration is tested by a fixture —
  an `Http` class that uses Doctrine, and a `Status` class that uses `Task`, must each produce a violation — written before the rules
  (phase `tests`), so `QAS-MAINT-layering.controller-queries-database` has a failing-first test.
  Source: `docs/pre-init/05-tdd.md`.

## Risks / Trade-offs

- [Tools are known to allow PHP 8.4 by their Composer constraints; running is verified only in the skeleton] → the skeleton runs every
  tool once on the chosen image (ADR-0001 Confirmation).
- [Symfony renders errors as `application/json`; an HTML fallback without `Accept` is not verified] →
  ADR-0005 decides the error format.
- [The OpenAPI validator supports 3.0 only] → the contract stays OpenAPI 3.0 (ADR-0003).
- [arm64 hosts] → ADR-0002 picks images published for amd64 and arm64.
- [The change stays open across the skeleton work] → group 1 is merged by its own PR; group 2 starts only
  after the skeleton and before any product change (proposal, Confirmed).

## Migration Plan

Not applicable: no running system.
