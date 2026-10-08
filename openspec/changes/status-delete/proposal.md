# Proposal: status-delete

## Why

The assignment asks to delete a status (`docs/task/assignment.txt:93-95`) and checks what happens when tasks
use it (line 136). It is the last endpoint of the assignment not yet built; every decision about it is in
ADR-0007 D2.

## What Changes

One pass (owner's preference for small changes), one task group plus Polish:

- `DELETE /api/statuses/{id}` → 204 when no task uses the status and it is not `new`.
- A status that tasks use → 409, `detail` `Status "<name>" is used by tasks.`; `new` → 409 always, `detail`
  `Status "new" cannot be deleted.`, checked first. Unknown or non-UUID id → 404.
- The port `StatusUsage` (ADR-0006) answers "is this status used by any task?"; the Task module's
  `Persistence` implements it. A task moved to the status while it is being deleted → the foreign key refuses
  the delete → the same 409 (ADR-0007 D2).
- Curl examples, README item "how status deletion is handled" (`CON-DELIV-readme-sections`).

## Out of scope

- Reassigning or deleting the tasks of a deleted status (ADR-0007 rejected both).
- Protecting `in_progress` and `done`: only `new` is protected (ADR-0007 D2).

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `statuses`: requirement `REQ-STATUS-delete` added.

## Impact

- New code: slice `DeleteStatus` (command, handler, controller), port `StatusUsage` and its adapter
  `DoctrineStatusUsage` in `Task/Infrastructure/Persistence`, exception `StatusNotDeletable` (409),
  `StatusRepository::remove()` translating the foreign key violation.
- Docs: `docs/api/openapi.yaml`, `docs/api/curl-examples.md`, README.
- No schema change (the foreign key with `ON DELETE RESTRICT` exists since `task-crud`), no new dependency.

## Roadmap

`docs/roadmap.md`, row 7 `status-delete`.

## Coverage

| Category | Status | Rationale |
|---|---|---|
| Scope | clear | roadmap row 7; ADR-0007 D2 |
| Data | clear | no schema change; FK `ON DELETE RESTRICT` exists |
| Edge cases and failures | clear | ADR-0007 D2, D5; matrix below |
| Constraints | clear | `CON-DELIV-readme-sections` (how deletion is handled), `CON-STACK-rest`, `CON-PLAN-deadline` |
| Terminology | clear | ADR-0007 |
| Non-functional | clear | `QAS-MAINT-layering` (Status never depends on Task; the port breaks the cycle) |
| Done criteria | clear | every `REQ-STATUS-delete` scenario tested; curl examples pass on the prod image |

## Corner cases

| Input | Dimension | Expected behaviour |
|---|---|---|
| status | state: created by a client, no tasks | 204, no body; then `GET` → 404, not listed |
| status | state: seeded `in_progress` or `done`, no tasks | 204 (only `new` is protected) |
| status | state: used by one or more tasks | 409, `detail` `Status "<name>" is used by tasks.`; nothing deleted |
| status | state: `new`, with or without tasks | 409, `detail` `Status "new" cannot be deleted.` (checked first) |
| status | state: a task moved to it while it is being deleted | 409 as "used" — the foreign key refuses (adapter test) |
| path id | format: not a UUID, uppercase UUID of an existing status; state: unknown or already deleted | 404 |
| request | method: `PUT`/`PATCH` on `/api/statuses/{id}` | 405 (tested under group `ADR-0005-validation`) |
| status | state: two concurrent deletes of the same unused status | both may answer 204 — accepted (owner) |

## Confirmed

- All of ADR-0007, D2 included (owner, 2026-10-07).
- One pass for small changes (owner, 2026-10-07).
- Owner, 2026-10-08 (after `spec-auditor`): a separate scenario for the race; two concurrent deletes of the
  same unused status may both answer 204.

## Assumptions

None.

## Open questions

None.
