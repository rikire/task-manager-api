---
paths:
  - "src/**"
  - "migrations/**"
  - "config/**"
---

# Code rules

Rules for application code. Each rule: why, scope, source.

- **RUL-CODE-fail-fast.** Never swallow errors: no empty or broad catch, no catch that returns a default.
  A default value is set once, at the boundary where data enters the system. Why: a plausible default
  hides the failure from tests and users. Source: `docs/pre-init/26-code-quality.md`.
- **RUL-CODE-reuse-first.** Use the framework, the standard library or an approved library before writing
  your own; for a non-trivial problem name it and record "Known solutions" in `design.md`. Why: home-made
  code duplicates solved problems. Source: `docs/pre-init/11-architecture-design.md`.
- **RUL-CODE-yagni.** No functionality nobody asked for; no abstraction without a second real
  implementation or a fired `IMP` trigger; no wrappers around the framework. Source:
  `docs/pre-init/11-architecture-design.md`, `docs/pre-init/26-code-quality.md`.
- **RUL-CODE-no-dead-code.** No dead code, no debug output; temporary debug code only with `DEBUG:` and
  never committed. Source: `docs/pre-init/26-code-quality.md`, `docs/pre-init/30-code-comments.md`.
- **RUL-CODE-verify-api.** Check framework and library APIs against the installed version (code in
  `vendor/`, docs), not memory. Source: `docs/pre-init/03-agent-instructions.md`.
- **RUL-ARCH-thin-controllers.** Controllers map HTTP to application calls: no SQL, no business logic.
  Layer rules are enforced by Deptrac once configured. Source: `docs/task/assignment.txt:137`
  (to become `QAS-MAINT-layering`), `docs/pre-init/11-architecture-design.md`.
- **RUL-SEC-boundaries.** Validate input at the boundary; parameterized queries only; check access to
  other users' resources; error responses do not leak internals; no secrets or personal data in logs.
  Source: `docs/pre-init/14-code-security.md`.
- **Comments.** Explain why, not what; `REQ-…` at behaviour entry points and in business logic, `ADR-…`
  where a decision is implemented; `TODO`/`FIXME`/`HACK` only with a `DEBT-`/`IMP-` ID. Source:
  `docs/pre-init/30-code-comments.md`.
