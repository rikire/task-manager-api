# ADR-0005: Input validation and error responses — Symfony Validator on DTOs, framework problem details

- **Status:** active, 2026-10-06
- **Amended:** 2026-10-07 — with debug off the framework writes only the HTTP status text into `detail`
  (`"Conflict"`), so a client could not tell why a domain rule refused the request; a normalizer for
  project exceptions now puts their message into `detail`. Request-body rules added: shape errors are
  reported before value errors, an empty body is validated as `{}`, a JSON body that is not an object gets
  the framework's code. Owner's decisions; found by the `spec-auditor` subagent in the source of Symfony
  8.1.8 (change `status-catalog`).
- **Kind:** architecture
- **Decided by:** project owner (status codes and error format are part of the API contract)
- **Drafted by:** agent

> Reading notes: "decision record NN" is `docs/pre-init/NN-*.md`. `CON-…` IDs are defined in
> `docs/constraints.md`, `QAS-…` in the `quality` spec (`openspec/specs/quality/spec.md`), `RUL-…` in
> `.claude/rules/code.md`. "The skeleton" is
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
| `#[MapRequestPayload]` / `#[MapQueryString]` | available since 6.3; deserialize into a DTO and validate it | <https://symfony.com/blog/new-in-symfony-6-3-mapping-request-data-to-typed-objects> |
| Malformed JSON | 400 | resolver source |
| Unsupported Content-Type | 415 | resolver source |
| Validation failure, body | 422 (`validationFailedStatusCode`, configurable) | <https://symfony.com/doc/current/controller.html> |
| Validation failure, query string | 404 by default (configurable) | same page |
| Type mismatch (string where int expected) | turned into violations, same code as validation (422) | resolver source |
| Error body | `ProblemNormalizer`: `type`, `title`, `status`, `detail`, plus `violations` for validation errors | <https://symfony.com/doc/current/controller/error_pages.html> |
| Error media type | `application/json`, not `application/problem+json`; HTML when the request does not prefer JSON (HTML fallback: reading of `Request`, not verified) | `SerializerErrorRenderer` 8.1 source |
| Exception class → status code | `framework.exceptions` maps it (and the log level) in configuration; `#[WithHttpStatus]` does the same as an attribute on the exception class | <https://symfony.com/doc/current/reference/configuration/framework.html> |
| `respect/validation` 3.1.2 | active; PHP ≥ 8.5; a second validator next to the one the resolver already uses | Packagist |

## Considered options

1. **Symfony Validator on DTOs via `#[MapRequestPayload]` / `#[MapQueryString]`**, errors rendered by the
   framework (`ProblemNormalizer`: RFC 9457-shaped body, `application/json`), API routes default to the
   JSON format.
1b. **As 1, plus an exception listener** that sets `application/problem+json` (the RFC 9457 media type,
   which the RFC does not require — <https://www.rfc-editor.org/rfc/rfc9457.html>).
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

Request bodies (amended 2026-10-07):

- Every `#[MapRequestPayload]` sets `mapWhenEmpty: true` (an empty body is validated as `{}`, so missing
  fields get violations) and the serialization context `allow_extra_attributes: false` with
  `collect_extra_attributes_errors: true` (an unknown field becomes a violation; without the second option
  the serializer throws and the request ends in 500).
- Shape errors (unknown field, wrong type) are reported alone: the framework runs the validator only when
  the body could be mapped, so value rules (`NotBlank`, `Regex`) show up on the next request. Accepted
  rather than replacing the framework's resolver.
- A JSON body that is not an object (`"x"`, `42`, `null`) gets the framework's code if it is 400 or 422;
  a test fixes which one. A 500 would be a defect.

Error `detail` (amended 2026-10-07): a decorator of the framework's `ProblemNormalizer` puts the exception
message into `detail` when the exception class is in the `App\` namespace and the status is below 500 —
the domain exceptions mapped in `framework.exceptions`. Everything else keeps the framework's text, and a
500 never shows the message. The class lives in `src/Shared/Infrastructure/Http/` (ADR-0006, layer
`Shared`). Messages follow `RUL-CODE-exception-messages`, for example `Status "done" already exists.`

JSON for unmatched requests (amended 2026-10-07): the `_format: json` route default applies only once a
route matches, so an unknown `/api/…` path (404) or a wrong method (405) rendered an HTML page for a client
without an `Accept` header (shown by a test). A request listener in `src/Shared/Infrastructure/Http/` sets
the request format to JSON for every `/api/…` path except the Swagger UI (`/api/doc`, ADR-0003) before
routing.

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

- Plus: validation rules sit on the request DTOs as attributes.
- Minus (amended 2026-10-07): one own class, the `detail` normalizer, and its test; a client sees shape
  errors and value errors in two rounds.
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
- Amended 2026-10-07: with debug off, a mapped domain exception answers with its message in `detail`, and
  the 500 test shows the status text only; an empty body gives violations on the required fields; an
  unknown field gives a violation, not 500; a non-object JSON body gives 400 or 422.

## Retires

Nothing.

## Revisit-when

- The owner wants type errors to answer 400 → a custom resolver for type mismatches.
- A client or the owner needs the `application/problem+json` media type → option 1b.
