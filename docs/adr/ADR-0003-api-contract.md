# ADR-0003: API contract — code-first OpenAPI 3.0 with NelmioApiDocBundle, committed and validated

- **Status:** active, 2026-10-06
- **Kind:** architecture
- **Decided by:** project owner
- **Drafted by:** agent

> Reading notes: "decision record NN" is `docs/pre-init/NN-*.md`. `CON-…` IDs are defined in
> `docs/constraints.md`, `QAS-…` in the `quality` spec (`openspec/specs/quality/spec.md`), `RUL-…` in `.claude/rules/code.md`. "The skeleton" is
> step 8 of `docs/pre-init/35-init-checklist.md`: the empty Symfony application running in Docker.

## Context and drivers

- `CON-STACK-rest` — the interface is a REST API over HTTP.
- `QAS-MAINT-readme-matches-code` — the documented decisions must stay true to the code; the contract is
  the most precise description of the API.
- Assignment line 116: OpenAPI/Swagger is a bonus item.
- Decision record 23 §5: the contract is mandatory and CI checks it against the code; the API contract is
  decided by the owner (`docs/pre-init/13-authority.md`), so contract changes must be visible in review.
- `CON-PLAN-deadline` — submission by 2026-10-09; context: the assignment estimates 6–10 hours
  (`docs/task/assignment.txt:13`).

Facts (checked 2026-10-06):

| Fact | Source |
|---|---|
| NelmioApiDocBundle 5.13.1 supports Symfony `^6.4 \|\| ^7.2 \|\| ^8.0`; generates OpenAPI from routes, attributes and DTO types (`MapRequestPayload` included: class `SymfonyMapRequestPayloadDescriber` in the 5.x source); serves it at `/api/doc` | Packagist `p2/nelmio/api-doc-bundle.json` |
| Nelmio emits OpenAPI 3.0.0 unless a version is set (swagger-php default) | swagger-php `src/Annotations/OpenApi.php`, Nelmio `src/ApiDocGenerator.php` |
| `league/openapi-psr7-validator` 0.24 (2026-05) validates OpenAPI 3.0.x only (3.1 issue open since 2022) | https://github.com/thephpleague/openapi-psr7-validator |
| `osteel/openapi-httpfoundation-testing` 0.15 wraps it for HttpFoundation in tests (Symfony 8 allowed) | Packagist |
| No maintained bundle validates requests against OpenAPI at runtime on Symfony 8 (`cydrickn/…` Symfony ^5.1, `gpht/…` Symfony ^6–^7) | Packagist |
| API Platform 5.0.2 supports Symfony `^7.4 \|\| ^8.0` | Packagist |

## Considered options

1. **Code-first:** Nelmio generates OpenAPI 3.0 from controllers and DTOs; the generated spec is committed
   as `docs/api/openapi.yaml`; CI fails when a fresh dump differs from it; functional tests validate every
   request and response against it.
2. **Contract-first:** a hand-written `docs/api/openapi.yaml` (3.0) is the source; functional tests
   validate requests and responses against it; no generator.
3. **Contract-first with runtime validation:** as 2, plus every incoming request is validated against the
   spec in production code. Rejected before comparison: no maintained Symfony 8 bundle; it would mean a
   home-made listener over a PSR-7 bridge (`RUL-CODE-reuse-first`).
4. **API Platform:** resources generate endpoints and the spec. Rejected before comparison: it replaces
   controllers with its own providers and processors — a framework within the framework to explain
   (`CON-DELIV-explain-without-ai`) and to learn within `CON-PLAN-deadline`.

## Trade-offs

| Attribute | 1. Code-first + committed dump | 2. Contract-first |
|---|---|---|
| Contract ↔ code drift (decision record 23 §5) | + spec derived from routes and DTOs; dump check catches undocumented endpoints; response validation catches serializer and status-code drift | ± only tested endpoints are checked; an undocumented, untested endpoint goes unnoticed |
| What response validation proves | ± success bodies are partly circular (spec and DTO from the same source); error codes and bodies are checked against hand-written annotations | + checks code against an independent description |
| Contract change visible in review (authority) | + diff of the committed `openapi.yaml` | + the file itself is the change |
| Contract reviewable before code | − exists after code | + can accompany the proposal |
| Effort (`CON-PLAN-deadline`) | ± schemas derived; error responses and codes annotated by hand | − every schema written twice: YAML and DTO |
| Explain without assistant (`CON-DELIV-explain-without-ai`) | + Nelmio attributes are mainstream | + plain YAML |

Sensitivity point: the source of truth (code or YAML) decides where drift can hide. Trade-off point:
contract-first gives review before code at the cost of a duplicate description; code-first removes the
duplication, and the committed dump restores reviewability.

## Decision and rationale

Use **code-first OpenAPI 3.0 with NelmioApiDocBundle** (option 1):

- the generated spec is committed at `docs/api/openapi.yaml`; CI regenerates it and fails on any diff;
- functional tests validate every response against it, and every request except in tests that send
  invalid input on purpose (ADR-0005), with `league/openapi-psr7-validator` through
  `osteel/openapi-httpfoundation-testing`;
- the running application serves the spec and its UI at `/api/doc` (bonus item, assignment line 116);
- the OpenAPI version stays **3.0.x** while the validator supports only 3.0;
- error responses and non-200 codes are annotated explicitly; their format is ADR-0005.

Rationale: one source of truth removes the duplicate description (`CON-PLAN-deadline`) and drift between
YAML and code; the committed dump keeps every contract change visible for the owner's decision; the
validator turns the contract into an executable check. Applied tactic: single source of truth plus a
generated artifact under a freshness check (decision record 23 prefers generation over hand-written
text).

## Consequences

- Plus: the contract cannot silently diverge from routes and DTOs; contract changes appear in diffs.
- Minus: the contract appears after the code — a proposal describes endpoints in prose and scenarios;
  success-body validation is partly circular; Nelmio attributes add annotation noise.
- Dependencies to approve at skeleton time: `nelmio/api-doc-bundle`, `league/openapi-psr7-validator`,
  `osteel/openapi-httpfoundation-testing` (dev).

## Confirmation

- CI job `contract-drift`: dump the spec with Nelmio's dump console command (`nelmio:apidoc:dump`, name
  confirmed in the skeleton) and compare with `docs/api/openapi.yaml`.
- Every functional test runs its response (and its request, unless the input is invalid on purpose)
  through the validator (a shared base test case);
  a test in group `ADR-0003-api-contract` proves the wiring: a response that breaks the contract fails.

## Retires

Nothing.

## Revisit-when

- OpenAPI 3.1 is needed → a 3.1 validator (for example `studio-design/gesso`, a small project) or league
  adds 3.1.
- More than 3 endpoints need a hand-written request or response schema (an `#[OA\Schema]` with its own
  properties instead of a derived DTO) → re-evaluate option 2.
