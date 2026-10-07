# tasks Specification

## Purpose

Tasks of the task manager API: creating a task (it starts in status `new`), reading one, listing them
with an optional status filter, deleting one. Shared rules (fields, codes, response shape) are ADR-0007.

## Requirements

### Requirement: REQ-TASK-create — a client adds a task

`POST /api/tasks` with a JSON object `{"title", "description"?}` SHALL create a task with status `new` and
answer 201 with the task `{id, title, description, status, created_at, updated_at}` and a `Location` header
holding `/api/tasks/{id}`.

- `title` MUST contain no control character (Unicode category Cc) anywhere; it is then trimmed of
  whitespace, Unicode spaces included, and the result MUST be 1–255 code points. The trimmed value is
  stored. Titles need not be unique.
- `description` is optional. It MUST contain no control character other than LF, CR and TAB anywhere; it
  is then trimmed the same way; the result MUST be at most 5000 code points. An absent, `null` or empty
  (after trimming) value is stored as `null`.
- A body field other than `title` and `description`, or a field of the wrong type, SHALL answer 422 with a
  violation on that field; value rules are then reported on a later request (ADR-0005). Otherwise, broken
  value rules SHALL answer 422 with a violation on each offending field. An empty body is validated as `{}`.
- `created_at` and `updated_at` are equal on creation, ISO 8601 UTC with `Z`, to the second.

Source: `docs/task/assignment.txt:36-44`; ADR-0005, ADR-0007 (D1, D3, D4); owner, 2026-10-07.

#### Scenario: REQ-TASK-create.created

- **WHEN** a client sends `{"title": "Подготовить отчет", "description": "Отчет по продажам за май"}`
- **THEN** the response is 201 with that title and description, `status` `new`, equal `created_at` and
  `updated_at`, and a `Location` that `GET` answers with the same task

#### Scenario: REQ-TASK-create.invalid

- **WHEN** a client sends `{"title": "", "description": "Строка\u0000"}`
- **THEN** the response is 422 with violations on `title` and `description`, and no task is created

#### Scenario: REQ-TASK-create.status-field

- **WHEN** a client sends `{"title": "Отчет", "status": "done"}`
- **THEN** the response is 422 with a violation on `status`, and no task is created

### Requirement: REQ-TASK-read — a client reads one task

`GET /api/tasks/{id}` SHALL answer 200 with the task, and 404 when the id is not a lowercase UUID or names
no task. Source: `docs/task/assignment.txt:57-58`; ADR-0007 (D5).

#### Scenario: REQ-TASK-read.get

- **WHEN** a client sends `GET /api/tasks/{id}` for an existing task
- **THEN** the response is 200 with the task

#### Scenario: REQ-TASK-read.not-found

- **WHEN** a client sends `GET /api/tasks/{id}` with a UUID that names no task, or with `abc`
- **THEN** the response is 404 with a JSON problem-details body

### Requirement: REQ-TASK-list — a client lists tasks, optionally by status

`GET /api/tasks` SHALL answer 200 with `{"items": […]}`, every task ordered by `id`. `?status=<name>` SHALL
keep only the tasks with that status. A `name` of no status SHALL answer 422 with `detail`
`Unknown status "<name>".`; a value that is empty, not a string or breaks the name rule (pattern and 1–50
characters, ADR-0007 D3) SHALL answer 422 with a violation on `status`. Unknown query parameters are ignored. Source: `docs/task/assignment.txt:49-55`;
ADR-0007 (D4, D5).

#### Scenario: REQ-TASK-list.all

- **WHEN** two tasks exist and a client sends `GET /api/tasks`
- **THEN** the response is 200 and `items` lists both, in creation order

#### Scenario: REQ-TASK-list.filtered

- **WHEN** one task is `new`, one is `done`, and a client sends `GET /api/tasks?status=done`
- **THEN** `items` lists only the `done` task

#### Scenario: REQ-TASK-list.unknown-status

- **WHEN** a client sends `GET /api/tasks?status=archived` and no status `archived` exists
- **THEN** the response is 422 with `detail` `Unknown status "archived".`

### Requirement: REQ-TASK-delete — a client deletes a task

`DELETE /api/tasks/{id}` SHALL delete the task and answer 204 with no body; an id that is not a lowercase
UUID or names no task SHALL answer 404. Source: `docs/task/assignment.txt:61-62`; ADR-0007 (D4, D5).

#### Scenario: REQ-TASK-delete.deleted

- **WHEN** a client deletes an existing task
- **THEN** the response is 204, `GET` on it answers 404, and the list no longer contains it

#### Scenario: REQ-TASK-delete.not-found

- **WHEN** a client deletes a task that does not exist, or the same task twice
- **THEN** the response is 404

### Requirement: REQ-TASK-status-change — a client changes a task's status

`PATCH /api/tasks/{id}/status` with a JSON object `{"status": "<name>"}` SHALL set the task's status to the
status with that name and answer 200 with the task. Any status may follow any other.

- A status different from the current one SHALL set `updated_at` to the second at which the change is
  applied (UTC); `created_at` stays. The same status SHALL change nothing, `updated_at` included.
- Errors, checked in this order: an id that is not a lowercase UUID matches no route → 404 before the body
  is read (ADR-0007 D5); then a body field other than `status` or a wrong type → 422 with a violation on that
  field alone (ADR-0005); then an empty or rule-breaking `status` (empty body validated as `{}`, name rule of
  ADR-0007 D3) → 422 with a violation on `status`; then a well-formed id that names no task → 404; then a
  `name` of no status — also one deleted while the change is saved — → 422 with `detail`
  `Unknown status "<name>".`, the task unchanged.
- A body that is not JSON SHALL answer 415; `application/merge-patch+json` counts as JSON (the framework's
  mapping, owner).

Source: `docs/task/assignment.txt:65-72`; ADR-0005, ADR-0007 (D2, D3, D5); owner, 2026-10-07.

#### Scenario: REQ-TASK-status-change.changed

- **WHEN** a task is `new` with `updated_at` in the past and a client sends `{"status": "done"}`
- **THEN** the response is 200 with `status` `done`, `updated_at` of the current second, the stored
  `created_at`, and `GET` on the task shows `done`

#### Scenario: REQ-TASK-status-change.same-status

- **WHEN** a task is `done` with `updated_at` in the past and a client sends `{"status": "done"}`
- **THEN** the response is 200 and the task, `updated_at` included, is unchanged

#### Scenario: REQ-TASK-status-change.invalid

- **WHEN** a client sends `{"status": "Done"}`, or `{"status": "done", "color": "red"}`
- **THEN** the response is 422 with a violation on `status`, respectively on `color` alone

#### Scenario: REQ-TASK-status-change.unknown-status

- **WHEN** a client sends `{"status": "archived"}` and no status `archived` exists
- **THEN** the response is 422 with `detail` `Unknown status "archived".`, and the task keeps its status

#### Scenario: REQ-TASK-status-change.status-deleted

- **WHEN** the status a task is being moved to is deleted before the change is saved
- **THEN** the change answers 422 with `detail` `Unknown status "<name>".` and the task keeps its status
  (verified on the persistence adapter, ADR-0007 Confirmation: the request-level race is not tested)

#### Scenario: REQ-TASK-status-change.not-found

- **WHEN** a client sends `{"status": "done"}` or `{"status": "archived"}` for a task that does not exist, or
  for the id `abc`
- **THEN** the response is 404
