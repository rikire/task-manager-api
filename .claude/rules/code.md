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
  implementation or a fired `IMP` trigger, except the ports of `ADR-0006-module-structure`; no wrappers
  around the framework. Source: `docs/pre-init/11-architecture-design.md`,
  `docs/pre-init/26-code-quality.md`, `docs/adr/ADR-0006-module-structure.md`.
- **RUL-CODE-no-dead-code.** No dead code, no debug output; temporary debug code only with `DEBUG:` and
  never committed. Source: `docs/pre-init/26-code-quality.md`, `docs/pre-init/30-code-comments.md`.
- **RUL-CODE-verify-api.** Check framework and library APIs against the installed version (code in
  `vendor/`, docs), not memory. Source: `docs/pre-init/03-agent-instructions.md`.
- **RUL-ARCH-thin-controllers.** Controllers map HTTP to application calls: no SQL, no business logic.
  "No database access" is enforced by Deptrac (`deptrac.yaml`, `make deptrac`); "no business logic" is
  checked in review. Source: `QAS-MAINT-layering`, `ADR-0006-module-structure`.
- **RUL-SEC-boundaries.** Validate input at the boundary; parameterized queries only; check access to
  other users' resources; error responses do not leak internals; no secrets or personal data in logs.
  JSON only through the serializer (never string concatenation); URLs only through the router; no secrets
  in URLs or query strings; no CORS — if it is ever needed, an explicit allow-list of origins, never a
  reflected `Origin` or `*`. Source: `docs/pre-init/14-code-security.md`, `docs/architecture/asvs-l1.md`
  (V1.2.1–V1.2.4, V3.4.2, V14.2.1).
- **RUL-SEC-response-models.** Never serialize an entity into a response: a response is built from a read
  model or response DTO that lists exactly the fields the contract promises. Why: entities grow fields
  that must not leak. Source: `docs/architecture/asvs-l1.md` (V15.3.1), `ADR-0006-module-structure`.
- **Comments.** Explain why, not what; `REQ-…` at behaviour entry points and in business logic, `ADR-…`
  where a decision is implemented; `TODO`/`FIXME`/`HACK` only with a `DEBT-`/`IMP-` ID. Source:
  `docs/pre-init/30-code-comments.md`.
