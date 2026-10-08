# Design: status-delete

## Context

Motivation: `proposal.md`. Behaviour: `specs/statuses/spec.md`. Decided: ADR-0006 (port `StatusUsage`,
Status never depends on Task), ADR-0007 D2 (codes, order, race).

Significance checklist: no schema change, no new module or dependency; the port was planned in ADR-0006. The
decisions below are local.

## Decisions

### D1. `StatusUsage` port, implemented by the Task module

Scope: local. ADR: ADR-0006. `Status\Domain\StatusUsage::isUsed(Status): bool`; the adapter
`Task\Infrastructure\Persistence\DoctrineStatusUsage` asks with one `EXISTS`-style query (`SELECT 1 FROM task
WHERE status_id = ? LIMIT 1`). Status's code knows only the interface; the container wires the Task adapter.

### D2. One exception class, two messages

Scope: local. ADR: ADR-0007 (D2: two 409s with different `detail`). `StatusNotDeletable::initial()` and
`::inUse(StatusName)`; one entry in `framework.exceptions` (409).

### D3. Order and race

Scope: local. ADR: ADR-0007 (D2). `DeleteStatusHandler`: `get()` (404) → `new`? — identified by the name
`new`, unique and immutable (ADR-0007 D3), not by the seeded id — (409 initial) → `StatusUsage::isUsed()`
(409 in use) → `StatusRepository::remove()`. `remove()` catches the foreign key
violation on flush (a task moved to the status in between) and throws `StatusNotDeletable::inUse()`; tested on
the adapter: a task row pointing at the status is inserted with SQL and `remove()` is called directly, standing
in for a check that has already passed; after the exception a raw query shows the status row and the task's
`status_id` unchanged.

## Risks / Trade-offs

- [The `isUsed()` check and the delete are not atomic] → the foreign key is the backstop, the client gets the
  same 409 (ADR-0007 D2).
- [Two concurrent deletes of the same unused status may both answer 204] → accepted by the owner: the end state
  is the same; README lists it with the other concurrency notes.
