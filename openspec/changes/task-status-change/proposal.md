# Proposal: task-status-change

## Why

The assignment asks to change a task's status (`docs/task/assignment.txt:65-72`): `PATCH
/api/tasks/{id}/status` with `{"status": "done"}`. Tasks can be created, read, listed and deleted
(`task-crud`, archived), but their status stays `new` forever.

## What Changes

1. **Change the status:** `PATCH /api/tasks/{id}/status` with `{"status": "<name>"}` → 200 with the task;
   any existing status may follow any other (owner); the same status again → 200, nothing changes; a new
   status moves `updated_at`. Unknown status → 422 with `detail`; unknown task → 404. A status deleted while
   the change is saved → 422 (ADR-0007 D2).
2. **Polish:** curl examples, `verifier`, archive.

## Out of scope

- Restricted transitions (owner: free, 2026-10-07).
- Deleting a status: change `status-delete`.
- Editing other task fields: not in the assignment.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `tasks`: requirement `REQ-TASK-status-change` added.

## Impact

- New code: slice `ChangeTaskStatus` (DTO, command, handler, controller), `Task::changeStatus()`;
  `TaskRepository::saveStatusChange()` turns a foreign key violation into `UnknownStatus`.
- Docs: `docs/api/openapi.yaml`, `docs/api/curl-examples.md`, `docs/architecture/asvs-l1.md` (V2.3.1: free
  transitions, decided), README (free transitions and the accepted concurrency, `CON-DELIV-ambiguity-in-readme`).
- No schema change, no new dependency.

## Roadmap

`docs/roadmap.md`, row 4 `task-status-change`.

## Coverage

| Category | Status | Rationale |
|---|---|---|
| Scope | clear | roadmap row 4; owner's answers 2026-10-07 |
| Data | clear | no schema change; `updated_at` rule — owner |
| Edge cases and failures | clear | ADR-0005 (amended), ADR-0007 D2, D5; matrix below |
| Constraints | clear | `CON-STACK-rest`, `CON-DELIV-explain-without-ai`, `CON-PLAN-deadline` |
| Terminology | clear | status `name` — ADR-0007 |
| Non-functional | clear | `QAS-MAINT-layering`, `QAS-MAINT-readability` |
| Done criteria | clear | every `REQ-TASK-status-change` scenario tested over HTTP; curl examples pass on the prod image |

## Corner cases

| Input | Dimension | Expected behaviour |
|---|---|---|
| body | emptiness: `{}`, empty body | 422, violation on `status` |
| body | structure: unknown field | 422, violation on that field (shape error, alone) |
| body | format: malformed JSON; non-empty body as `text/plain`; as `application/merge-patch+json` | 400; 415; accepted as JSON (owner, after a test showed the framework's mapping) |
| body | type: not an object (`"x"`, `42`, `null`, `[]`) | 422 (framework behaviour, as for tasks and statuses), never 500 |
| `status` | emptiness: `null`, `""` | 422, violation on `status` |
| `status` | type: number, array | 422, violation on `status` (shape error) |
| `status` | format: breaks the name rule (`Done`, 51 characters) | 422, violation on `status` |
| `status` | state: no such status (`archived`, 50 characters) | 422, `detail` `Unknown status "<name>".`, task unchanged |
| `status` | state: another existing status | 200, the task with the new status; `updated_at` moves, `created_at` stays |
| `status` | state: the task's current status | 200, nothing changes, `updated_at` stays (owner) |
| `status` | state: any to any, `done` → `new` included | allowed (owner: free transitions) |
| `status` | state: deleted while the change is saved | 422 `Unknown status`, task unchanged (ADR-0007 D2) |
| path id | format: not a UUID; state: unknown or deleted task | 404 |
| path id + `status` | state: unknown task and unknown status | 404: the task is checked first (owner) |
| task | state: two concurrent `PATCH`es; a `PATCH` concurrent with `DELETE` | last write wins; the `PATCH` may answer 200 for a task just deleted — accepted (owner) |
| request | method: `GET`/`POST` on `/api/tasks/{id}/status` | 405 |

## Confirmed

- All of ADR-0007 (owner, 2026-10-07).
- Owner, 2026-10-07 (interview of this change): transitions are free; the same status again answers 200
  and changes nothing (`updated_at` stays); a successful change answers 200 with the whole task.
- Owner, 2026-10-07 (after `spec-auditor`): a missing task wins over an unknown status (404 first);
  concurrent writes on one task are accepted as they are (last write wins, a PATCH racing a DELETE may
  answer 200); `application/merge-patch+json` accepted as JSON (first 415, changed after a test showed
  Symfony maps it to JSON); the foreign key → `UnknownStatus` translation applies only to saving a status
  change, so creating a task keeps its 500 for a missing `new`.

## Assumptions

None.

## Open questions

None.
