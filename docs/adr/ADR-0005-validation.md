# ADR-0005: Input validation and error responses — Symfony Validator on DTOs, framework problem details

- **Status:** active, 2026-10-06
- **Kind:** architecture
- **Decided by:** project owner (status codes and error format are part of the API contract)
- **Drafted by:** agent

> Reading notes: "decision record NN" is `docs/pre-init/NN-*.md`. `CON-…` IDs are defined in
> `docs/constraints.md`, `QAS-…` in the `quality` spec (`openspec/changes/architecture-kickoff/specs/quality/spec.md`,
> `openspec/specs/quality/spec.md` after archive), `RUL-…` in `.claude/rules/code.md`. "The skeleton" is
> step 8 of `docs/pre-init/35-init-checklist.md`: the empty Symfony application running in Docker.

## Context and drivers

- `CON-STACK-rest` — REST over HTTP: status codes carry meaning.
- Assignment lines 115, 117, 136 (future `REQ-` scenarios of every endpoint): correct HTTP codes, strict
  input validation, broken JSON, nonexistent IDs and statuses.
- Assignment line 119 (bonus): DTOs or other architectural patterns.
- `QAS-MAINT-layering` — HTTP entry points do not depend on the persistence layer or the database
  connection; `RUL-ARCH-thin-controllers` — controllers hold no business logic (checked in review).
- `CON-DELIV-explain-without-ai` — documented framework mechanisms over home-made ones.
- `RUL-CODE-reuse-first`, `RUL-SEC-boundaries` — validate at the boundary, use the framework.

Facts (checked 2026-10-06; resolver behaviour read in the `RequestPayloadValueResolver` source of 7.4
and 8.1 by the `researcher` subagent; the 422/404 defaults checked on the page by the agent):

| Case | Symfony 8.1 default | Source |
|---|---|---|
| `#[MapRequestPayload]` / `#[MapQueryString]` | available since 6.3; deserialize into a DTO and validate it | https://symfony.com/blog/new-in-symfony-6-3-mapping-request-data-to-typed-objects |
| Malformed JSON | 400 | resolver source |
| Unsupported Content-Type | 415 | resolver source |
| Validation failure, body | 422 (`validationFailedStatusCode`, configurable) | https://symfony.com/doc/current/controller.html |
| Validation failure, query string | 404 by default (configurable) | same page |
| Type mismatch (string where int expected) | turned into violations, same code as validation (422) | resolver source |
| Error body | `ProblemNormalizer`: `type`, `title`, `status`, `detail`, plus `violations` for validation errors | https://symfony.com/doc/current/controller/error_pages.html |
| Error media type | `application/json`, not `application/problem+json`; HTML when the request does not prefer JSON (HTML fallback: reading of `Request`, not verified) | `SerializerErrorRenderer` 8.1 source |
| `framework.exceptions` maps an exception class to `status_code` (and log level) in configuration; `#[WithHttpStatus]` does the same as an attribute on the exception class | https://symfony.com/doc/current/reference/configuration/framework.html |
| `respect/validation` 3.1.2 | active; PHP ≥ 8.5; a second validator next to the one the resolver already uses | Packagist |

## Considered options

1. **Symfony Validator on DTOs via `#[MapRequestPayload]` / `#[MapQueryString]`**, errors rendered by the
   framework (`ProblemNormalizer`: RFC 9457-shaped body, `application/json`), API routes default to the
   JSON format.
1b. **As 1, plus an exception listener** that sets `application/problem+json` (the RFC 9457 media type,
   which the RFC does not require — https://www.rfc-editor.org/rfc/rfc9457.html).
2. **Manual validation** in controllers or application services (decode JSON, check fields, build errors).
3. **`respect/validation`** in application services.
4. **Validation against the OpenAPI spec at runtime** — rejected before comparison: no maintained Symfony
   8 bundle (ADR-0003, option 3).

## Trade-offs

| Attribute | 1. Validator + DTO, framework errors | 1b. + listener | 2. Manual | 3. respect/validation |
|---|---|---|---|---|
| Documented mechanism (`CON-DELIV-explain-without-ai`) | + the documented way | ± plus one home-made component | − home-made | ± a library outside the Symfony docs |
| Controllers stay thin (`QAS-MAINT-layering`) | + controller receives a valid DTO | + | − checks pile up in controllers or services | ± in services |
| Consistent codes and body for every error | + one renderer | + | − per endpoint | − per endpoint |
| Error media type | `application/json` | `application/problem+json` | any | any |
| DTOs (assignment line 119) | + | + | ± | ± |
| Extra code or dependency | — | + listener and its test | — | + one dependency |
| Installs on PHP 8.4 (ADR-0001) | + | + | + | − requires PHP ≥ 8.5 |

Sensitivity point: where errors are rendered decides whether every endpoint answers the same way.

## Decision and rationale

Use **Symfony Validator on request DTOs** mapped with `#[MapRequestPayload]` and `#[MapQueryString]`;
errors are rendered by the framework as RFC 9457-shaped problem details with `application/json`; API
routes default to the JSON format (`_format: json` in the defaults of the `/api` route prefix); every
`#[MapQueryString]` sets `validationFailedStatusCode: 422`. No custom listener. Whether 404 and 405 for
unmatched routes also render JSON is not verified; the tests below check it.

Status codes (decided by the owner, 2026-10-06):

| Situation | Code |
|---|---|
| Malformed JSON | 400 |
| Unsupported Content-Type | 415 |
| Body fails validation, or a field has the wrong type | 422, with `violations` (`propertyPath`, `title`) |
| Query string fails validation | 422 (`validationFailedStatusCode` overrides the 404 default) |
| Resource with the given ID does not exist | 404 |
| Method not allowed | 405 |
| Unexpected error | 500, no internals in the body outside debug |

Product-specific codes (for example deleting a status that still has tasks) are decided in the product
changes' requirements.

Rationale: the framework's own mapper, validator and error renderer are the documented path
(`CON-DELIV-explain-without-ai`, `RUL-CODE-reuse-first`), keep controllers to "receive a valid DTO, call
the application layer" (`QAS-MAINT-layering`) and give DTOs (line 119); one renderer makes every error
answer the same way (lines 115, 136). `application/problem+json` (1b) would cost a home-made listener for
a media type no driver requires; the owner chose the framework default.

## Consequences

- Plus: no custom error code; validation rules sit on the request DTOs as attributes.
- Request DTOs live in the HTTP adapter (`src/<Module>/Infrastructure/Http/<UseCase>/`, ADR-0006): they check the
  shape of the input; domain rules (for example a status name that must be unique) are checked in
  `Domain`/`Application`; their exceptions are mapped to codes with `framework.exceptions` in configuration
  (not `#[WithHttpStatus]`, which would make the domain depend on Symfony, ADR-0006).
- Minus: errors use `application/json`, not the RFC 9457 media type; type errors share 422 with validation
  errors.
- The error format is annotated in the OpenAPI contract (ADR-0003).

## Confirmation

- Functional tests in group `ADR-0005-validation`, one per code in the table (malformed JSON → 400,
  unsupported Content-Type → 415, validation and type mismatch → 422 with `violations`, invalid query →
  422, unknown ID → 404, wrong method → 405, unexpected error → 500 without internals), each response
  validated against the contract (ADR-0003). The 500 test boots the kernel with debug off and uses a
  throwing route registered only in the `test` environment (no test code in production routes).

## Retires

Nothing.

## Revisit-when

- The owner wants type errors to answer 400 → a custom resolver for type mismatches.
- A client or the owner needs the `application/problem+json` media type → option 1b.
