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
- **RUL-CODE-style.** Formatting is PHP-CS-Fixer's job (`@Symfony`, `@Symfony:risky`, `@PHP8x4Migration`,
  `ordered_class_elements`, `declare(strict_types=1)` in every file; `.php-cs-fixer.dist.php`); the rules
  below cover what the config cannot express. Source: Symfony Coding Standards
  (<https://symfony.com/doc/current/contributing/code/standards.html>); owner, 2026-10-07 (change
  `finish-init`, step 13).
- **RUL-CODE-exception-messages.** Build messages with `sprintf()`; start with a capital letter, end with
  a period; quote values with double quotes, never backticks; use `get_debug_type()` for types. Why:
  one format for every error a client or a log reader sees. Source: Symfony Coding Standards.
- **RUL-CODE-naming.** camelCase for variables, methods and arguments; SCREAMING_SNAKE_CASE for
  constants; UpperCamelCase for enum cases; snake_case for route names and config parameters; service id
  = class name. Ports carry no `Interface` suffix (`StatusRepository`, `StatusUsage`), unlike the
  Symfony contributor standard. Source: Symfony Coding Standards; `ADR-0006-module-structure`.
- **RUL-CODE-phpdoc.** PHPDoc only for what native types cannot say (generics, `list<…>`, array shapes
  for PHPStan max); no PHPDoc repeating a native type; `null` last in unions. Source: Symfony Coding
  Standards.
- **RUL-CODE-di.** Autowiring and autoconfiguration; services private; no `$container->get()`;
  `#[Autowire]` only where a value cannot be inferred. Source: Symfony Best Practices
  (<https://symfony.com/doc/current/best_practices.html>).
- **RUL-CODE-config.** Infrastructure settings come from environment variables, secrets from Symfony
  secrets or the environment (never committed), application options as `app.`-prefixed parameters,
  options that never change per environment as class constants. Source: Symfony Best Practices;
  `QAS-DEPLOY-prod-image`.
- **Comments.** Explain why, not what; `REQ-…` at behaviour entry points and in business logic, `ADR-…`
  where a decision is implemented; `TODO`/`FIXME`/`HACK` only with a `DEBT-`/`IMP-` ID. Source:
  `docs/pre-init/30-code-comments.md`.
