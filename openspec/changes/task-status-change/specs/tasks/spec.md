## ADDED Requirements

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
