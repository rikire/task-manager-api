# Design: status-catalog

## Context

Motivation and scope: `proposal.md`. Behaviour: `specs/statuses/spec.md`. Decided elsewhere and not
repeated here: contract and its validation (ADR-0003), validation, body rules and error format (ADR-0005,
amended 2026-10-07), module structure and the `Shared` layer (ADR-0006, amended 2026-10-07), schema, codes
and response shape (ADR-0007).

Significance checklist (decision record 11): the change adds a data schema, the first API contract and a
cross-cutting class (the error `detail` normalizer). All three were decided by the owner and recorded in
ADR-0005, ADR-0006 and ADR-0007 before this design; the decisions below are local or implement them.

## Known solutions

- **Contract testing** (problem: does the running API match its OpenAPI description?). Known solutions:
  response validation against the spec in functional tests (`league/openapi-psr7-validator`, used through
  `osteel/openapi-httpfoundation-testing`); consumer-driven contracts (Pact) — no consumer exists;
  schema-driven fuzzing (Schemathesis) — a separate tool in another language. Chosen in ADR-0003: the
  validator in a base test case.
- **Error bodies**: RFC 9457 problem details, rendered by Symfony's `ProblemNormalizer` (ADR-0005); its
  documented extension point is decorating the normalizer service.
- **Test database isolation**: one transaction per test rolled back at the end (`dama/doctrine-test-bundle`,
  installed and enabled in `phpunit.xml.dist`); schema by migrations, so the seed exists in tests too.

## Decisions

### D1. Controllers extend `AbstractController`, one invokable class per endpoint

Scope: local. Drivers: `CON-DELIV-explain-without-ai` (the documented Symfony way: `$this->json()`,
`$this->generateUrl()`); `RUL-SEC-boundaries` (JSON through the serializer, URLs through the router);
ADR-0006 (one controller per slice in `Infrastructure/Http/<UseCase>/`). Closes the open point of
`docs/architecture/README.md` §9. Alternative: plain classes with the serializer and router injected —
same behaviour, more constructor code.

### D2. Read models, routes and not-found

Scope: local. ADR: ADR-0006 (query handlers return read models), ADR-0007 (D4, D5); drivers
`REQ-STATUS-read`, `RUL-SEC-response-models`.

- Handlers return `StatusView` (`id`, `name`, `title` as strings); the controller wraps a list in
  `{"items": …}`.
- Routes live under the `/api` prefix with the default `_format: json` (ADR-0005); `{id}` carries
  `requirements: Requirement::UUID`.
- `GetStatusHandler` throws `StatusNotFound`, mapped to 404 in `framework.exceptions`.

### D3. Request DTO for the 422 shape, value objects for invariants

Scope: local. ADR: ADR-0005 (amended), ADR-0006; drivers `REQ-STATUS-create`, `QAS-MAINT-layering`.

- `#[MapRequestPayload(mapWhenEmpty: true, serializationContext: [allow_extra_attributes: false,
  collect_extra_attributes_errors: true])]` (ADR-0005, amended).
- `name`: `NotBlank`, `Length(max: 50)`, `Regex('/^[a-z][a-z0-9_]*\z/')`.
- `title`: `Regex('/^\P{Cc}*\z/u')` on the raw value (no control character anywhere; `\z`, because `$`
  would accept a final `\n`), then `NotBlank` and `Length(max: 255)` with a normalizer that trims
  `/^[\s\p{Z}]+|[\s\p{Z}]+$/u` (ASCII whitespace and Unicode spaces, so an NBSP-only title is blank).
  `Length` counts code points.
- Domain value objects `StatusName` and `StatusTitle` enforce the same rules (`StatusTitle` trims the same
  way) and throw if broken: a second line reached only by a bug.

Alternative: rules only in the domain, exceptions turned into violations — rejected: it needs code to
build violations, which ADR-0005 avoids.

### D4. Duplicate name: check in the handler, unique index as backstop

Scope: local. ADR: ADR-0007 (D3); driver `REQ-STATUS-create.duplicate-name`. `CreateStatusHandler` asks
`StatusRepository::existsByName()` and throws `StatusNameTaken` (`Status "done" already exists.`); the
Doctrine adapter catches the unique-constraint violation on flush and throws the same exception.
`framework.exceptions` maps `StatusNameTaken` to 409.

### D5. Schema and seed in one migration with fixed ids

Scope: local. ADR: ADR-0007 (D1); driver `REQ-STATUS-initial`. Table `status`: `id uuid` primary key,
`name varchar(50)` with a unique index, `title varchar(255)`. The migration inserts the three statuses with
UUID v7 literals whose timestamp is 2026-01-01T00:00:00Z, in ascending order. Generated with
`doctrine:migrations:diff`, then the inserts added by hand. `down()` drops the table.

### D6. Error `detail` normalizer in `Shared`

Scope: local. ADR: ADR-0005 (amended), ADR-0006 (amended); drivers `REQ-STATUS-create.duplicate-name`,
`docs/task/assignment.txt:136`. `App\Shared\Infrastructure\Http\DomainProblemNormalizer` decorates the
`ProblemNormalizer` service: for a `FlattenException` whose class starts with `App\` and whose status is
below 500 it replaces `detail` with the exception message; otherwise it returns the inner result
unchanged. Deptrac gets the `Shared` layer with fixture tests (ADR-0006 Confirmation, amended).

### D7. Contract harness and test environment

Scope: local. ADR: ADR-0003, ADR-0005; driver `QAS-MAINT-readme-matches-code` (the contract is a document
that must match the code).

- `tests/Functional/ApiTestCase` (extends `WebTestCase`) builds one validator from `docs/api/openapi.yaml`
  and offers `request()`: it validates the request (unless the test marks it invalid on purpose) and the
  response against the operation.
- The contract has a shared component schema `Problem` (problem details with optional `violations`), and
  every operation annotates its error responses with it (ADR-0003: error responses annotated explicitly).
- Responses with no operation in the contract (unknown `/api/…` path, the test-only 500 route) are
  validated against the `Problem` component schema directly.
- Functional tests run with `APP_DEBUG=1` from `phpunit.xml.dist`; tests of `detail` text and of the 500
  boot the kernel with debug off, so they see what production sends.
- `tests/bootstrap.php` creates the test database if missing and runs the migrations once per run; DAMA
  rolls back every test.
- `make openapi` dumps the spec with `nelmio:apidoc:dump --format=yaml`; `make openapi-check` dumps to a
  temporary file and compares; it is part of `make check`, so pre-commit and CI run it (ADR-0003,
  amended).

### D8. Order of task groups

Scope: local. The harness group (1) holds only what needs no status endpoint: base test case, `Problem`
schema, `detail` normalizer and its Deptrac layer, unknown-path 404 and 500 tests, dump and drift job.
The wiring test ("a response that breaks the contract fails") needs a real operation, so it moves to
group 2 with `GET /api/statuses`. Codes that need a body (400, 415, 422, 405) come with the create
endpoint in group 3. `REQ-STATUS-read.list` needs a non-seed status before the create endpoint exists:
group 2 inserts it through the repository port.

## Risks / Trade-offs

- [The framework's code for a non-object JSON body is not verified] → a test in group 3 fixes it; 400 or
  422 is accepted (owner), 500 is a defect.
- [An unmatched path may render HTML, not JSON (ADR-0005, ADR-0007 risk): unknown `/api/…` path,
  `/api/statuses/abc`, wrong method] → those tests send no `Accept` header and assert a JSON body; if one
  fails, the fix is the owner's decision.
- [Success-body validation is partly circular: the contract is generated from the same code (ADR-0003)]
  → the scenario tests also assert the body values.
- [`REQ-STATUS-initial.restart` cannot run inside PHPUnit] → checked in CI `clean-clone`, which already
  runs a second `up` on the same volume (ADR-0002).

## Migration Plan

The migration runs on start-up (`migrate` service, ADR-0002). Rollback: `down()` drops `status`; no data
other than the seed exists yet.
