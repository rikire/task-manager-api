## ADDED Requirements

### Requirement: REQ-STATUS-delete — a client deletes a status no task uses

`DELETE /api/statuses/{id}` SHALL delete the status and answer 204 with no body when no task uses it and it is
not `new`. Checked in this order: an id that is not a lowercase UUID or names no status → 404; the status `new`
→ 409 with `detail` `Status "new" cannot be deleted.`; a status that any task uses — also one a task is moved
to while it is being deleted — → 409 with `detail` `Status "<name>" is used by tasks.`. A refused delete
changes nothing. Source: `docs/task/assignment.txt:93-95,136`; ADR-0007 (D2, D5).

#### Scenario: REQ-STATUS-delete.deleted

- **WHEN** a client deletes a status that no task uses and that is not `new`
- **THEN** the response is 204 with no body, `GET` on it answers 404, and the list no longer contains it

#### Scenario: REQ-STATUS-delete.in-use

- **WHEN** a client deletes the status `done` while a task is `done`
- **THEN** the response is 409 with `detail` `Status "done" is used by tasks.`, and the status and the task
  are unchanged

#### Scenario: REQ-STATUS-delete.initial

- **WHEN** a client deletes the status `new` while a task is `new`, and again when no task is `new`
- **THEN** both answer 409 with `detail` `Status "new" cannot be deleted.`

#### Scenario: REQ-STATUS-delete.moved-in-meanwhile

- **WHEN** a task is moved to the status after the usage check and before the delete is saved
- **THEN** the delete answers 409 with `detail` `Status "<name>" is used by tasks.`, and the status and the
  task are unchanged (verified on the persistence adapter, ADR-0007 Confirmation)

#### Scenario: REQ-STATUS-delete.not-found

- **WHEN** a client deletes a status that does not exist, one already deleted, the id `abc`, or the uppercase
  id of an existing status
- **THEN** the response is 404
