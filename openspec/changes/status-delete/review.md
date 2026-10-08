# Review: status-delete

Review brief (template: `.claude/skills/change-workflow/SKILL.md`), kept with the change (`FAIL-006`). One
pass (owner's preference): both groups below, one pull request.

## Groups 1–2 — delete a status, Polish (tasks 1.1–2.2), 2026-10-08

**Ready to commit:** yes, after `make check`. PHPUnit `OK (223 tests)`; on the prod image (isolated stack):
unused status → 204, again → 404, `new` → 409 "cannot be deleted", `done` used by a task → 409 "is used by
tasks".

**Data flow:** `DELETE /api/statuses/{id}` → `Requirement::UUID` → `DeleteStatusController` →
`DeleteStatusHandler`: `StatusRepository::get()` (404) → name `new`? (`StatusNotDeletable::initial()`, 409) →
`StatusUsage::isUsed()` — implemented by `Task\Infrastructure\Persistence\DoctrineStatusUsage`, one query →
(`inUse()`, 409) → `StatusRepository::remove()` (SQLSTATE 23001 → `inUse()`, 409) → 204.

**Must read:** `DeleteStatusHandler.php` (order), `DoctrineStatusRepository::remove()` (the 23001 catch),
`DoctrineStatusUsage.php` (the port across modules).

**Check by hand:** curl examples "Удалить статус".

**Key decisions:** design D1–D3; ADR-0006 (port), ADR-0007 D2.

**Corner-case matrix and red output:** red run: 9 of 12 new tests on assertions or "service not found" (the
port had no user yet); owner accepted the change with the auditor's two points.

**Findings during the work:**

- The race test found a real defect: PostgreSQL reports an `ON DELETE RESTRICT` violation as SQLSTATE 23001
  (restrict_violation), which Doctrine does not map to `ForeignKeyConstraintViolationException`; the first
  catch missed it and the race would have answered 500. Fixed by catching `DriverException` with 23001 only.
- Process: two auditor findings were written into "Confirmed" before the owner answered — `FAIL-010`.

**Simplifications:** two concurrent deletes of the same unused status may both answer 204 (owner, README).

**Debt:** `IMP-013` (initial status name written twice), `IMP-014` (no log when the foreign key backstop
fires). **Not done:** none.

**Maturity:** functionality — production-ready for the assignment (every endpoint exists); reliability —
production-ready for this scope (the foreign key decides races); security — production-ready;
maintainability — production-ready; observability — prototype; consumer experience — working minimum.

**Extra checks:** `verifier` (task 2.2): no correctness defect; the check order, layers, error texts and the
23001 handling confirmed, and `saveStatusChange` of the previous change confirmed (an UPDATE violation is
23503). Fixed in this pass: group `ADR-0007-api-conventions` on the in-use and `new` tests; the unknown-UUID 404
checked against the contract; the task under `new` checked unchanged after the refusal; the stale README intro
and OpenAPI line; ADR-0007 amended on 23001. Recorded: `IMP-013`, `IMP-014`.
