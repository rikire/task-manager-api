# Proposal: status-catalog

## Why

The API has no endpoint yet, so nothing the assignment asks for can be called, and the checks that later
changes rely on — every response validated against the OpenAPI contract (ADR-0003), one test per error
code (ADR-0005) — do not exist. The status catalog comes first because a task cannot be created without
the status `new` (ADR-0007, D1).

## What Changes

**API harness** (first task group; roadmap row 2):

- A base functional test case that validates every response, and every request except deliberately
  invalid ones, against `docs/api/openapi.yaml` (ADR-0003); a wiring test proves that a response breaking
  the contract fails.
- `docs/api/openapi.yaml` generated from code and committed, with a shared problem-details schema for
  error responses; a Makefile target regenerates it; `make openapi-check` (in `make check`) fails when the committed file
  differs from a fresh dump (ADR-0003).
- A normalizer that puts a project exception's message into the error `detail` (ADR-0005, amended), in
  the new `Shared` layer (ADR-0006, amended).
- Tests of the error codes of ADR-0005 (400, 415, 422, 404, 405, 500), each against the contract; the
  ones that need a request body use `POST /api/statuses`.

**Status catalog** (assignment lines 29–32, 75–91; ADR-0007):

- `POST /api/statuses` — create a status from `{"name", "title"}`; 201, the status, `Location`.
- `GET /api/statuses` — `{"items": [ … ]}` ordered by `id`.
- `GET /api/statuses/{id}` — one status; 404 for an unknown or non-UUID id.
- A migration creates the `status` table and the statuses `new` ("Новая"), `in_progress` ("В работе"),
  `done` ("Готово").
- `docs/api/curl-examples.md`, section "Статусы": one example per scenario.

## Out of scope

- Deleting a status: change `status-delete` (roadmap row 7).
- The `task` table and the foreign key to `status` (change `task-create-and-read`).
- Editing a status: no endpoint in the assignment (ADR-0007, D3).
- Invalid query string → 422 (ADR-0005): no endpoint here validates a query; change
  `task-filter-by-status`.
- Pagination (assignment line 141).

## Capabilities

### New Capabilities

- `statuses`: the status catalog — creating, listing and reading statuses, the initial statuses.

### Modified Capabilities

- `quality`: `QAS-MAINT-readability` gets its thresholds — method 5, class 20 — and the check moves from
  a CI report into `make check` (owner, 2026-10-07; `IMP-001-readability-threshold` fired once groups 1–2
  were merged). `QAS-DEPLOY-clean-clone-start` gets its `GET /api/statuses` → 200 check once the endpoint
  exists, as the scenario already says.

## Impact

- New code: module `Status` (`Domain`, `Application` slices `CreateStatus`, `ListStatuses`, `GetStatus`,
  `Infrastructure/Http`, `Infrastructure/Persistence` with XML mapping) per ADR-0006; the first migration;
  `src/Shared/Infrastructure/Http/` with the `detail` normalizer.
- New tests: base contract test case, groups `ADR-0003-api-contract`, `ADR-0005-validation`,
  `ADR-0006-module-structure` (`Shared` layer), `ADR-0007-api-conventions`, and the `REQ-STATUS-…`
  scenarios.
- New files: `docs/api/openapi.yaml`; Makefile
  targets for the dump and its check.
- Modified: `deptrac.yaml` (layer `Shared`); `config/packages/framework.yaml` (`framework.exceptions`);
  routes (`/api` prefix); `tests/bootstrap.php` (migrations); CI `clean-clone` (seed and restart checks);
  `docs/api/curl-examples.md`; `docs/architecture/asvs-l1.md`, `docs/architecture/README.md`.
- No new dependencies: the validator packages of ADR-0003 and `dama/doctrine-test-bundle` are installed.

## Roadmap

`docs/roadmap.md`, row 2 `status-catalog`.

## Coverage

| Category | Status | Rationale |
|---|---|---|
| Scope | clear | roadmap row 2 (owner, 2026-10-07): harness first, then create, list, get, initial statuses |
| Data | clear | ADR-0007 D1, D3 (fields, uniqueness, seed); title rules — owner, 2026-10-07 |
| Edge cases and failures | clear | ADR-0005 (amended), ADR-0007 D3, D5; owner's answers 2026-10-07; matrix below |
| Constraints | clear | `CON-STACK-rest`, `CON-STACK-php-symfony-pg`, `CON-DELIV-explain-without-ai`, `CON-PLAN-deadline` |
| Terminology | clear | status, `name`, `title` defined in ADR-0007 reading notes |
| Non-functional | clear | `QAS-MAINT-layering` (Deptrac), `QAS-MAINT-typing`, `QAS-DEPLOY-clean-clone-start` (CI `clean-clone`) |
| Done criteria | clear | table below |

### Done criteria

| Deliverable | Check |
|---|---|
| Harness | wiring test in group `ADR-0003-api-contract` fails on a contract-breaking response; `make openapi-check` green |
| Error codes | one test per ADR-0005 code, each response validated against the contract |
| `detail` | with debug off, a 409 carries the domain message, a 500 does not |
| Statuses | every `REQ-STATUS-…` scenario has a passing test (`restart` in CI); the ADR-0007 rows that apply to statuses are tested |
| Seed | CI `clean-clone` lists `new`, `in_progress`, `done` once each after the first and the second `up` |
| Docs | curl examples for every scenario pass by hand; asvs-l1 rows updated |

## Corner cases

Inputs: the `POST /api/statuses` body, the path id of `GET /api/statuses/{id}`, the request as a whole.
Codes follow ADR-0005 (amended) and ADR-0007; "violation on X" means 422 with a `violations` entry whose
`propertyPath` is X. Shape errors (unknown field, wrong type) are reported alone; value rules are checked
only when the shape is right.

| Input | Dimension | Expected behaviour |
|---|---|---|
| body | emptiness: no body with `Content-Type: application/json`, or `{}` | violations on `name` and `title` |
| body | emptiness: no body and no `Content-Type` | 415 |
| body | type: valid JSON that is not an object (`[]`, `"x"`, `42`, `null`) | 400 or 422, whichever the framework gives; a test fixes it (owner); never 500 |
| body | format: malformed JSON (`{"name":`), invalid UTF-8, lone `\ud800` escape | 400 |
| body | format: `Content-Type` not `application/json` | 415 |
| body | structure: unknown field (`"color"`) | violation on `color` only |
| body | structure: duplicate key (`{"name":"a","name":"b"}`) | the last value wins (PHP JSON decoder) (owner) |
| `name` | emptiness: missing, `null`, `""` | violation on `name` |
| `name` | type: number, array, boolean | violation on `name` only (shape error) |
| `name` | format: uppercase, space, leading digit or `_`, Cyrillic, `-`, final `\n` | violation on `name` |
| `name` | format: leading or trailing spaces (`" done"`) | violation on `name`; never trimmed (ADR-0007) |
| `name` | size: 1 character, 50 characters | 201 |
| `name` | size: 51 characters | violation on `name` |
| `name` | state: equals an existing name (`done`) | 409, `detail` `Status "done" already exists.` |
| `name` | state: two concurrent creates with the same name | one 201, one 409 (unique index) |
| `title` | emptiness: missing, `null`, `""`, only spaces, only NBSP | violation on `title` |
| `title` | type: not a string | violation on `title` only (shape error) |
| `title` | format: any control character anywhere (`"\nРевью"`, `"Ре\tвью"`, `\u0000`) | violation on `title` (owner: reject first, then trim) |
| `title` | format: leading or trailing spaces, NBSP included | trimmed before the length check and storage (owner) |
| `title` | format: format characters (U+200B, U+202E) | accepted: only category Cc is rejected (owner) |
| `title` | size: 255 code points after trimming, Cyrillic included | 201 (owner: characters = code points) |
| `title` | size: 256 code points after trimming | violation on `title` |
| `title` | state: equals another status's title | 201: `title` is not unique (ADR-0007) |
| `title` | trust: HTML or script text (`<script>`) | stored and returned as JSON-encoded text (owner) |
| path id | format: not a UUID (`abc`), uppercase UUID of an existing status | 404, JSON body |
| path id | state: well-formed, unknown | 404 |
| path id | state: one of the seeded statuses | 200 |
| request | method: `PUT`/`PATCH`/`DELETE` on `/api/statuses`, `POST` on `/api/statuses/{id}` | 405, JSON body (`DELETE /api/statuses/{id}` arrives with `status-delete`) |
| request | query: unknown parameter on `GET /api/statuses` | ignored (ADR-0007, D5) |
| list | state: only the seeded statuses | `items` = `new`, `in_progress`, `done`, in that order |
| list | state: a status created after the seed | listed after the seeded ones |

## Confirmed

- Order and content of the change: harness first, then create, list, get; initial statuses `new`,
  `in_progress`, `done` (owner, roadmap row 2, 2026-10-07).
- All of ADR-0007 (owner, 2026-10-07).
- Owner, 2026-10-07: `title` is trimmed before validation and storage; any control character in `title`
  → 422, checked before trimming, and Unicode spaces are trimmed too; length counted in characters (code
  points, as `varchar` and Symfony `Length` count); an empty body → 422 via `mapWhenEmpty`; a non-object
  JSON body gets the framework's 400 or 422; shape errors are reported before value errors; a normalizer
  in the new `Shared` layer puts project exception messages into `detail`.
- Owner, 2026-10-07 (accepted with the proposal): a duplicate JSON key keeps the last value, no special
  handling; `title` is not sanitised — HTML-looking text is stored and returned JSON-encoded, encoding for
  display is the client's job (`RUL-SEC-boundaries`); only Unicode category Cc counts as a control
  character, format characters such as U+200B and U+202E are accepted in `title`.

## Assumptions

None.

## Open questions

None.
