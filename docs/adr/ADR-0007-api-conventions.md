# ADR-0007: Task–status link, status deletion and API conventions

- **Status:** active, 2026-10-07
- **Errata:** 2026-10-07 — the Facts row on extra fields missed `collect_extra_attributes_errors: true`,
  without which an unknown field ends in 500; `detail` texts now follow `RUL-CODE-exception-messages` and
  reach the client through the normalizer of ADR-0005 (amended the same day). Found by the `spec-auditor`
  subagent (change `status-catalog`).
- **Kind:** architecture
- **Decided by:** project owner (data schema, API contract and error codes; interview before the change
  `status-catalog`, 2026-10-07)
- **Drafted by:** agent

> Reading notes: `CON-…` IDs are defined in `docs/constraints.md`, `QAS-…` in the `quality` spec
> (`openspec/specs/quality/spec.md`), `RUL-…` in `.claude/rules/code.md`. "Assignment line N" is
> `docs/task/assignment.txt:N`. A *status* is a row of the status catalog (`id`, `name`, `title`): `name` is the machine
> identifier used in requests (`done`), `title` the label for people ("Готово"). *Status module* and *Task module* are
> the code modules of ADR-0006; inside each, `Http` (entry points), `Application` (use cases) and `Persistence`
> (database adapter) are layers. A task also has a `title`, its own heading, unrelated to a status `title`. A *port* is
> an interface the domain declares and an adapter implements (ADR-0006). *Problem details* is the error body of ADR-0005
> (RFC 9457 fields `type`, `title`, `status`, `detail`, plus `violations` for validation errors). `framework.exceptions`
> is the Symfony setting that maps an exception class to an HTTP code.

This ADR records the product decisions that every endpoint shares, so that each product change
(`status-catalog`, `task-crud` — create, read, filter and delete tasks — `task-status-change` and
`status-delete` in `docs/roadmap.md`) specifies only its own edge cases.

## Context and drivers

- `CON-DELIV-readme-sections` — README must say how Task and Status are linked and how deleting a status
  is handled.
- `CON-DELIV-ambiguity-in-readme` — where the assignment can be read in more than one way, choose and
  describe the choice in README. Open points in the assignment: what a new task's status is, what deleting
  a status in use does, what an unknown status or extra field does, the shape of responses.
- `CON-STACK-rest` — status codes carry meaning.
- Assignment lines 115, 117, 136: correct HTTP codes, strict input validation; reviewers check
  nonexistent IDs and statuses and deleting a status that tasks use.
- Assignment lines 26, 54, 65–72, 83–91: a task has `status (new, in_progress, done, …)`; the status is
  set by `name` (`?status=done`, `PATCH /api/tasks/{id}/status {"status": "done"}`); every example `name`
  is lowercase with `_` (`code_review`). There is no endpoint to edit a status (lines 75–95).
- Assignment line 141: pagination is out of scope.
- `QAS-MAINT-layering` — HTTP entry points do not reach the database.
- `CON-DELIV-explain-without-ai` — framework mechanisms over home-made ones.
- ADR-0005 (codes 400/415/422/404/405/500, problem-details body, `framework.exceptions` for domain
  exceptions), ADR-0006 (modules, port `StatusUsage`, UUID v7 identifiers from `nextId()`).

Facts (checked 2026-10-07 in `vendor/`, Symfony 8.1.8):

| Fact | Source |
|---|---|
| `Requirement::UUID` is the regex `[0-9a-f]{8}-[0-9a-f]{4}-[13-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}`: lowercase only, versions 1 and 3–8; a path that does not match it matches no route | `symfony/routing` `Requirement/Requirement.php` |
| With the serializer options `allow_extra_attributes: false` and `collect_extra_attributes_errors: true` in a `#[MapRequestPayload]` serialization context, each unexpected field becomes a violation; with the first option alone the serializer throws and the request ends in 500. Shape violations are reported without the validator's: it runs only when the body could be mapped ("This attribute was not expected.", `propertyPath` = the field) answered with the validation code (422) | `symfony/http-kernel` `RequestPayloadValueResolver` (source read; not yet confirmed by a test) |
| `symfony/uid` UUID v7 carries a microsecond timestamp and is monotonic within one PHP process; ids made in the same microsecond by different processes are ordered by their random bits | `symfony/uid` `UuidV7.php` |

## Considered options

Five decisions, options numbered within each; the chosen option is first. "D2.1" is option 1 of D2.

**D1. Task → status link.**

1. Foreign key `task.status_id → status.id`; the API speaks status `name`; a new task gets `new`.
2. The task stores the status `name` as a string, no foreign key.

**D2. Deleting a status that tasks use.**

1. Refuse with 409; nothing is deleted. `new` can never be deleted.
2. Move the tasks to `new`, then delete the status.
3. Delete the tasks with the status (cascade).

**D3. Status fields.**

1. `name` matches `^[a-z][a-z0-9_]*$`, 1–50 characters, unique, duplicate → 409.
2. `name` is any non-empty string up to 255 characters, duplicate → 422.
3. Any string, normalised by the server (trimmed, lowercased).

**D4. Response shape.**

1. Task `status` is the `name` string; lists are wrapped: `{"items": […]}`.
2. Task `status` is the `name` string; lists are bare arrays.
3. Task `status` is an object `{id, name, title}`.

**D5. References to nothing.**

1. Unknown status in the `PATCH` body or `?status=` → 422; extra body field → 422; non-UUID path id → 404.
2. Unknown `?status=` → empty list; extra fields ignored; non-UUID id → 400.

## Trade-offs

Attributes: **integrity** (data stays valid under concurrent requests), **client clarity** (the code
tells the client what to fix — assignment lines 115, 136), **explainability** (framework mechanisms, less
own code — `CON-DELIV-explain-without-ai`), **evolvability** (the contract can grow without breaking
clients).

| Option | Integrity | Client clarity | Explainability | Evolvability |
|---|---|---|---|---|
| D1.1 FK on id, API by `name` | + enforced by PostgreSQL | + matches the assignment's `{"status": "done"}` | ± one lookup `name` → id | + renaming a `name` would not touch tasks |
| D1.2 `name` string, no FK | − only in code; a status can vanish under its tasks | + | + simplest schema | − |
| D2.1 409 | + nothing lost or changed implicitly | + one rule | + `ON DELETE RESTRICT` follows from D1.1 | + |
| D2.2 move tasks to `new` | ± tasks change without the client touching them | − surprising | − own code | ± |
| D2.3 cascade | − one request silently deletes data | − | + | − |
| D3.1 strict `name`, 409 | + no `Done` / `done` / `done␠` near-duplicates (a PostgreSQL unique index is case-sensitive) | + 409 means "valid request, conflicts with existing data" (RFC 9110 §15.5.10); `name` goes into `?status=` without URL encoding | + a regex constraint | ± the 50-character limit is arbitrary |
| D3.2 free `name`, 422 | − near-duplicates | − duplicate looks like a format error | ± needs trimming and case rules | ± |
| D3.3 normalised | + | − the server silently rewrites input | − own code | ± |
| D4.1 `name` + `items` | + | + what the client reads is what it writes | + | + list fields (`total`) can be added without breaking clients |
| D4.2 `name` + bare array | + | + | + one level less | − list metadata later is a breaking change |
| D4.3 status object | + | − reads an object, writes a string | − a status loaded per task | ± |
| D5.1 strict | + | + a typo (`?status=dnoe`, `"descripton"`) is reported | + route requirement and serializer option, no own code | − clients that send extra body fields get errors |
| D5.2 lenient | + | − an empty list hides a typo; an ignored optional field loses data | − 400 for the path needs own code | + tolerant |

Sensitivity point (one decision that many properties depend on): D1 decides where integrity lives; with
D1.1 PostgreSQL enforces it, and D2 follows from it. Trade-off point (a decision that improves one property
at the cost of another): D5.1 buys client clarity at the cost of tolerance to clients that send more than
the contract.

## Decision and rationale

**Data (D1, D2).**

- `task.status_id` is a foreign key to `status.id` with `ON DELETE RESTRICT`; `status.name` has a unique
  index.
- The first migration creates the statuses `new` ("Новая"), `in_progress` ("В работе"), `done`
  ("Готово") with fixed UUID v7 literals in ascending order whose timestamps are earlier than any runtime id (for
  example 2026-01-01), so lists show them first and in that order. A created
  task gets `new`; the create request has no `status` field, so sending one is an extra field → 422 (D5).
- Deleting a status that any task uses → **409**, nothing deleted. The Status module asks the port
  `StatusUsage` ("is this status used by any task?", implemented by the Task module's `Persistence`)
  before deleting. Deleting `new` → **409** always, because every created task needs it; this check comes
  first, so its `detail` wins even when tasks use `new`. The two 409s differ in `detail`: `Status "x" is
  used by tasks.` / `Status "new" cannot be deleted.` Deleting an unknown id → 404.
- Concurrent requests (the foreign key decides; each loser gets the same code as without the race):
  - a `PATCH` moves a task to status X while X is being deleted, and the delete commits first → the
    Task module's `Persistence` turns the foreign key violation into the "unknown status" exception → 422;
  - the `PATCH` commits first → the Status module's `Persistence` turns the foreign key violation on
    delete into the "status is used" exception → 409.

**Status fields (D3).**

- `name`: `^[a-z][a-z0-9_]*$`, 1–50 characters; otherwise 422 on `name`. Duplicate → **409**, `detail` `Status "<name>"
  already exists.`: a domain exception mapped with `framework.exceptions` (ADR-0005); a concurrent duplicate caught by
  the unique index is turned into the same exception in the Status module's `Persistence`.
- `title`: non-empty after trimming, at most 255 characters, not unique; otherwise 422 on `title`.
  `"Code Review"` is a valid `title` and an invalid `name`.
- No endpoint edits a status (the assignment has none); `name` and `title` stay as created.

**Responses (D4).**

- Task: `{"id", "title", "description", "status": "<name>", "created_at", "updated_at"}`. Status:
  `{"id", "name", "title"}`. Whether `description` may be empty is decided in `task-crud`.
- Lists (`GET /api/tasks`, `GET /api/statuses`): `{"items": [ … ]}`, ordered by `id` ascending; 200; without `?status=`,
  all tasks. UUID v7 grows with time, so this is creation order to the microsecond; ids made in the same microsecond by
  different requests keep a stable but arbitrary order (Facts).
- Dates: ISO 8601 in UTC with the `Z` suffix, for example `2026-10-07T12:00:00Z` (not `+00:00`).
- `POST` → 201 with the created resource and a `Location` header holding the resource path
  (`/api/statuses/{id}`); `DELETE` → 204, no body.

**References to nothing (D5).**

| Request | Code and body |
|---|---|
| `PATCH /api/tasks/{id}/status` with a status `name` that does not exist | 422, `detail` `Unknown status "<name>".`; no `violations` |
| `GET /api/tasks?status=<name>` with a `name` that does not exist | 422, the same body |
| `?status=` or a `PATCH` body `status` that fails the `name` pattern (`Done`) or is empty | 422, violation on `status` (request DTO constraint) |
| A body field the endpoint does not define (`{"title": "x", "foo": 1}`) | 422, violation on `foo` |
| An unknown query parameter (`?status=done&foo=1`) | ignored |
| A path id that does not match `Requirement::UUID` (`/api/tasks/abc`, an uppercase UUID) | 404 |
| A well-formed id that does not exist | 404 (ADR-0005) |

- Every `detail` text above is the domain exception's message, put into the body by the normalizer of
  ADR-0005 (with debug off the framework alone would show only `"Conflict"`).
- "Unknown status by `name`" is its own domain exception class, mapped to 422 with
  `framework.exceptions`. It is not the "status not found by id" exception of `GET /api/statuses/{id}`
  (404): `framework.exceptions` maps a class to one code for the whole application.
- Extra body fields are rejected per endpoint: `allow_extra_attributes: false` and `collect_extra_attributes_errors:
  true` in each `#[MapRequestPayload]` serialization context, not in the global serializer configuration, which would
  also reach `#[MapQueryString]`. Unknown query parameters are ignored because proxies and browsers add their own
  (`utm_*`, cache-busting `_=<timestamp>`), and a misspelt query parameter loses no data. Trade-off: a misspelt
  parameter name (`?stauts=done`) returns an unfiltered list; accepted because the parameters others add cannot be
  listed.
- A path that is not a UUID names no resource, so 404 is accurate, and the route requirement gives it
  without own code.

Rationale: integrity is enforced in the database (referential integrity, unique constraint), so the rules
hold under concurrent requests; client input fails fast with a code that says what to fix, which is what
the reviewers test (assignment line 136); the API follows the assignment's own examples (`name` in
requests, slug-shaped names); every mechanism is the framework's (route requirement, serializer option,
validator constraints, `framework.exceptions`) — `CON-DELIV-explain-without-ai`. The `items` wrapper is
the owner's choice: pagination is out of scope (assignment line 141), so no list metadata is planned, but
the wrapper lets `total` or a cursor be added later without a breaking change.

## Consequences

- Plus: README items "how Task and Status are linked" and "how status deletion is handled" have one source
  (`CON-DELIV-readme-sections`).
- Plus: no controller code for error mapping; every product error is a domain exception mapped in
  configuration (ADR-0005).
- Minus: 422 comes in two bodies — with `violations` (request DTO validation) and with `detail` only
  (unknown status, found by a lookup). In the second case there is no machine-readable field; the status
  name appears only in the human-readable `detail` (see Revisit-when).
- Minus: one extra query per filtered list (does the status exist?).
- Minus: each `Persistence` adapter translates two Doctrine exceptions (foreign key, unique) into domain
  exceptions; mapping a Doctrine exception class in `framework.exceptions` directly would apply to every
  foreign key violation in the application and is not done. Amended 2026-10-08 (change `status-delete`): a
  delete blocked by `ON DELETE RESTRICT` comes from PostgreSQL as SQLSTATE 23001, which Doctrine does not map
  to `ForeignKeyConstraintViolationException`; that adapter catches `DriverException` filtered on 23001.
- Risk: the 404 for a non-UUID path matches no route, so the `_format: json` default that ADR-0005 sets on
  all `/api` routes (it makes errors render as JSON) does not apply, and a client that does not ask for
  JSON may get an HTML error page. ADR-0005 already marks unmatched-route rendering as not verified; the D5
  test sends no `Accept` header to show the real behaviour. Closed 2026-10-08: the 404 answers JSON
  (`ErrorResponsesTest`, D5 tests).
- Risk: the 422 for extra body fields relies on framework behaviour read in the source but not yet tested
  (Facts); the first endpoint with a body confirms it. Closed 2026-10-08: confirmed by the extra-field
  scenarios (`CreateStatusTest`, `CreateTaskTest`, `ChangeTaskStatusTest`).
- Status `name` and `title` cannot be corrected after creation: delete and recreate, possible only while
  no task uses the status, never for `new`.

## Confirmation

Functional tests in the PHPUnit group `ADR-0007-api-conventions`, written in the change that introduces
each endpoint, every response validated against the OpenAPI contract (ADR-0003):

- one test per row of the D5 table, per code in D2 (in use, `new`, unknown id) and D3 (pattern, length,
  duplicate, `title`);
- list order: items come in `id` order; the seeded statuses come as `new`, `in_progress`, `done`;
- `POST` returns 201 with `Location` equal to the resource path; `DELETE` returns 204 with an empty body;
- every date string ends in `Z`;
- the schema has the foreign key with `ON DELETE RESTRICT` and the unique index on `status.name` (a
  migration test).

The two race cases of D2 are covered by unit tests of the `Persistence` adapters: they check that a foreign key
violation becomes the domain exception; the request-level race itself is not tested.

## Retires

Nothing. ADR-0005 said product-specific codes "are decided in the product changes' requirements"; this
ADR now sets the codes shared by several changes, and each change's `REQ-` scenarios restate the codes they
use and link this ADR.

## Revisit-when

- Pagination enters scope → add fields next to `items` (`total`, cursor), not a new shape.
- Statuses need editing → decide whether `name` may change and what happens to filters by the old name.
- Clients need a status label with every task → an embedded status object or an `include` parameter.
- Clients need the field of an unknown-status error in structured form → produce a violation on `status`
  (a catch in the controller rethrowing a validation failure, or a DTO constraint calling a Task
  `Application` query; a Task `Http` class must not use Status ports, ADR-0006).
