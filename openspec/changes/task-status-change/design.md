# Design: task-status-change

## Context

Motivation and scope: `proposal.md`. Behaviour: `specs/tasks/spec.md`. Patterns from `task-crud`: request
DTO with `#[MapRequestPayload]`, `TaskView`, `StatusRepository::findByName()`, `UnknownStatus` (422).

Significance checklist: no schema change, no new module or dependency; the decisions below are local.

## Decisions

### D1. `Task::changeStatus()` knows "nothing changed"

Scope: local. Driver: `REQ-TASK-status-change.same-status`. `changeStatus(Status $status, \DateTimeImmutable
$now): bool` sets the status and `updatedAt` only when the status differs and returns whether it changed;
the handler saves only then. The rule lives in the entity, so no caller can move `updated_at` without a
change.

### D2. Status deleted during the change

Scope: local. ADR: ADR-0007 (D2: "the Task module's `Persistence` turns the foreign key violation into the
unknown-status exception → 422"). A separate port method
`TaskRepository::saveStatusChange(Task)` catches the foreign key violation on flush and throws
`UnknownStatus` for the task's status name; `save()` stays as it is, so creating a task keeps its 500 for a
missing `new` (owner, 2026-10-07). Tested on the adapter against the test database (a status row deleted
with SQL before the flush); Doctrine closes the entity manager after the failed flush, which ends that test.

### D3. Order of checks

Scope: local. Driver: owner (404 before unknown status). The handler loads the task first (`TaskNotFound`),
then the status (`UnknownStatus`); body violations come earlier, from `#[MapRequestPayload]`.

### D4. Request and route

Scope: local. ADR: ADR-0005, ADR-0007 (D5). `ChangeTaskStatusRequest { string $status }` with `NotBlank`,
`Length(max: 50)` and the name pattern (`htmlPattern` for the contract); extra fields rejected as in the
create requests. Route `/api/tasks/{id}/status` with `Requirement::UUID`, method `PATCH`; the response is
`TaskView` (schema `Task`).

## Risks / Trade-offs

- [Free transitions: a client may move `done` back to `new`] → the owner's choice; README lists it as a
  deliberate simplification; ASVS V2.3.1 row updated.
- [Concurrent writes: last write wins; a PATCH racing a DELETE may answer 200 for a deleted task] → accepted
  by the owner; README lists it.
