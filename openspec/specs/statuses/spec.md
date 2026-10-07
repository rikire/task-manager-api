# statuses Specification

## Purpose

The status catalog of the task manager API: the initial statuses `new`, `in_progress` and `done`, creating
a status, listing statuses and reading one. Shared rules (fields, codes, response shape) are ADR-0007.

## Requirements

### Requirement: REQ-STATUS-initial — the catalog starts with new, in_progress and done

After the database migrations have run, the catalog SHALL contain the statuses `new` ("Новая"),
`in_progress` ("В работе") and `done` ("Готово"), listed first and in that order. Starting the stack again
on the same database SHALL NOT duplicate them. Source: `docs/task/assignment.txt:26`, ADR-0007 (D1).

#### Scenario: REQ-STATUS-initial.fresh-database

- **WHEN** the migrations have run on an empty database and a client sends `GET /api/statuses`
- **THEN** the response is 200 with `items` = `new`, `in_progress`, `done`, each with its `id`, `name`
  and `title`

#### Scenario: REQ-STATUS-initial.restart

- **WHEN** the stack is stopped and started again on the same database
- **THEN** `GET /api/statuses` still lists each initial status exactly once

### Requirement: REQ-STATUS-create — a client adds a status

`POST /api/statuses` with a JSON object `{"name", "title"}` SHALL create a status and answer 201 with the
status `{id, name, title}` and a `Location` header holding `/api/statuses/{id}`.

- `name` MUST match `^[a-z][a-z0-9_]*$`, be 1–50 characters long and differ from every existing `name`.
- `title` MUST contain no control character (Unicode category Cc) anywhere; it is then trimmed of
  whitespace, Unicode spaces included, and the result MUST be 1–255 Unicode code points long. The trimmed
  value is stored.
- A body field other than `name` and `title`, or a field of the wrong type, SHALL answer 422 with a
  violation on that field; the value rules above are then reported on a later request (ADR-0005).
- Otherwise, broken value rules SHALL answer 422 with a violation on each offending field. An empty body
  is validated as `{}`.
- An existing `name` SHALL answer 409 with `detail` `Status "<name>" already exists.` and create nothing.

Source: `docs/task/assignment.txt:83-91,115,117`; ADR-0005, ADR-0007 (D3, D5); title rules and empty body
— owner, 2026-10-07.

#### Scenario: REQ-STATUS-create.created

- **WHEN** a client sends `{"name": "code_review", "title": "  Ревью кода "}`
- **THEN** the response is 201 with `name` `code_review`, `title` `Ревью кода` and a `Location` pointing
  at the new status, and `GET` on that location returns the same status

#### Scenario: REQ-STATUS-create.invalid-values

- **WHEN** a client sends `{"name": "Code Review", "title": "\nРевью"}`
- **THEN** the response is 422 with violations on `name` and `title`, and no status is created

#### Scenario: REQ-STATUS-create.unknown-field

- **WHEN** a client sends `{"name": "code_review", "title": "Ревью кода", "color": "red"}`
- **THEN** the response is 422 with a violation on `color`, and no status is created

#### Scenario: REQ-STATUS-create.duplicate-name

- **WHEN** a client sends `{"name": "done", "title": "Сделано"}` while `done` exists
- **THEN** the response is 409 with `detail` `Status "done" already exists.`, and the catalog is unchanged

### Requirement: REQ-STATUS-read — a client lists statuses and reads one

`GET /api/statuses` SHALL answer 200 with `{"items": [ … ]}`, every status ordered by `id` ascending
(creation order); unknown query parameters are ignored. `GET /api/statuses/{id}` SHALL answer 200 with
the status, and 404 when the id is not a lowercase UUID (an uppercase form of an existing id included) or
names no status. Source: `docs/task/assignment.txt:75-81`, ADR-0005, ADR-0007 (D4, D5).

#### Scenario: REQ-STATUS-read.list

- **WHEN** the catalog holds the initial statuses and `code_review`, and a client sends `GET /api/statuses`
- **THEN** the response is 200 and `items` lists `new`, `in_progress`, `done`, `code_review` in that
  order

#### Scenario: REQ-STATUS-read.get

- **WHEN** a client sends `GET /api/statuses/{id}` with the id of `in_progress`
- **THEN** the response is 200 with that status

#### Scenario: REQ-STATUS-read.not-found

- **WHEN** a client sends `GET /api/statuses/{id}` with a UUID that names no status, or with `abc`
- **THEN** the response is 404 with a JSON problem-details body
