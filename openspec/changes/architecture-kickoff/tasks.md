# Tasks

## 1. Drivers and stack ADRs (before checklist steps 8–11)

No code in this group, so no TDD phases: its products are a spec and decision records, checked by
`openspec validate`, `architecture-reviewer` and the owner (exception to the "tests first" rule, stated
here per `openspec/config.yaml`).

- [x] 1.1 Write `specs/quality/spec.md` with the five quality scenarios; verify `openspec validate architecture-kickoff --strict` passes and the owner accepted the spec
- [x] 1.2 Rewrite `ADR-0001-platform-versions` with drivers `CON-…`/`QAS-…` and the first review's accepted findings; verify every driver ID exists in `docs/constraints.md` or the spec
- [x] 1.3 Rewrite `ADR-0002-php-runtime`, comparing where migrations run, restart policy, host port and multi-arch images; verify its Confirmation covers `QAS-DEPLOY-clean-clone-start` scenarios, including no migrations yet
- [x] 1.4 Rewrite `ADR-0003-api-contract`; verify its Confirmation names the contract-drift check and the response-validation tests
- [x] 1.5 Write `ADR-0004-orm`; verify at least two real options with sources
- [x] 1.6 Write `ADR-0005-validation` including the error response format and status codes for malformed JSON, validation failure and type mismatch; verify the owner decides the codes (`docs/pre-init/13-authority.md`)
- [x] 1.7 Write `ADR-0006-module-structure` (rewritten 2026-10-07: slices on a hexagonal core, modules Task and Status); verify the layers it defines satisfy `QAS-MAINT-layering` and can be expressed as dependency rules
- [x] 1.8 Run `architecture-reviewer` on ADR-0001…0006 and put its findings into the review brief; verify every finding is either applied after the owner's decision or recorded as rejected with a reason
- [x] 1.9 Update `Affects:` in `docs/constraints.md` and the coverage rows in `docs/task/README.md` to the final ADR IDs; verify no row points to a removed or draft ID
- [x] 1.10 Commit with "this commit accepts ADR-0001…0006" after the owner's confirmation, open the group-1 PR and merge it; verify `main` contains the accepted ADRs

## 2. Architecture rules and description (after checklist steps 8–11, before any product change)

- [x] 2.1 Phase `tests`: write fixture tests where an `Http` class depends on Doctrine and a `Status` class depends on `Task`, and assert the dependency-rule check reports a violation for each; verify they fail for the right reason (no rules yet)
- [x] 2.2 Phase `impl`: write the Deptrac layer and module rules from ADR-0006 and the `RUL-ARCH-…` rules; verify the fixture test passes and the check reports 0 violations on the skeleton
- [x] 2.3 Move `Source:` of `RUL-ARCH-thin-controllers` to `QAS-MAINT-layering` (instruction commit); verify `grep` finds no reference to `assignment.txt:137` in `.claude/rules/`
- [x] 2.4 Select the OWASP ASVS level 1 requirements that apply and record each as a `RUL-SEC-…` rule or a future `REQ-` with its reason; verify every selected item has a target and every skipped item a reason
- [x] 2.5 Fill `docs/architecture/README.md` (decision record 11 §1: quality goals, constraints link, strategy with the stack table, building blocks, deployment view from ADR-0002 — containers, image targets, ports, volumes, environment configuration — and cross-cutting concepts); verify the `reader-tester` subagent passes it

## 3. Polish

- [x] 3.1 Names, error handling wording, boundary cases, docs updated, no out-of-scope changes in the diff; verify with the review brief and the `verifier` subagent
- [ ] 3.2 Archive the change; verify `openspec/specs/quality/spec.md` exists and every `design.md` decision carries `Scope: local` or `ADR:`
