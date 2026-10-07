# Review: task-status-change

Review brief (template: `.claude/skills/change-workflow/SKILL.md`), kept with the change (`FAIL-006`). The
owner asked to run the whole change in one pass (2026-10-07): both groups below, one pull request.

## Groups 1–2 — change the status, Polish (tasks 1.1–2.2), 2026-10-07

**Ready to commit:** yes, after `make check`. PHPUnit `OK (211 tests)`; on the prod image (isolated stack)
the curl examples answered as documented: change → 200 with a new `updated_at`, same status → 200 with the
same `updated_at`, `Done` → 422 on `status`, `archived` → 422 `Unknown status`, back to `new` → 200, unknown
task → 404.

**Data flow:** `PATCH /api/tasks/{id}/status` → `Requirement::UUID` → `#[MapRequestPayload]
ChangeTaskStatusRequest` (shape, then name rule) → `ChangeTaskStatusHandler`: `TaskRepository::get()`
(`TaskNotFound` → 404) → `StatusRepository::findByName()` (`UnknownStatus` → 422) → `Task::changeStatus()`
(false for the same status) → `saveStatusChange()` (foreign key violation → `UnknownStatus`) → `TaskView`.

**Must read:** `src/Task/Domain/Task.php` (`changeStatus()`), `src/Task/Application/ChangeTaskStatus/
ChangeTaskStatusHandler.php` (order of checks), `DoctrineTaskRepository::saveStatusChange()`.

**Check by hand:** curl examples "Сменить статус" in `docs/api/curl-examples.md`.

**Key decisions:** design D1–D4; ADR-0007 D2, D5; owner's answers (free transitions, same status no-op, 200
with the task, 404 before unknown status, concurrency accepted, FK translation only on status change).

**Corner-case matrix and red output:** red run: 25 new tests on assertions; the owner asked not to stop for
acceptance (one pass).

**Deviations:**

- `application/merge-patch+json`: I proposed 415 as the framework default "not verified"; a test showed
  Symfony maps it to JSON (200). The owner chose to accept it as JSON; test, spec and matrix changed.
- `make check` cache trap again: after the new route the no-debug test cache had to be cleared.

**Simplifications:** free transitions; concurrent writes on one task unprotected (README, section
"Архитектурные решения и компромиссы").

**Debt:** none. **Not done:** none.

**Maturity:** functionality — working minimum (all endpoints of the assignment except status delete);
reliability — production-ready for single writers; security — production-ready; maintainability —
production-ready; observability — prototype; consumer experience — working minimum (`IMP-009`).

**Extra checks:** `verifier` (task 2.2): no code defect; order of checks, `updated_at` rule, foreign key
translation only in `saveStatusChange()`, layers and messages confirmed. What was done with its findings:

- The spec said body violations come before "an id that is not a UUID → 404"; the route requirement answers
  404 before the body is read (ADR-0007 D5). Spec text corrected to the code.
- Curl examples gained the extra-field and `abc` cases.
- The status name rule is copied in three DTOs: `IMP-012-status-name-rule-in-three-dtos`.
- Accepted as is (no extra cycle, owner's one-pass request): the `.status-deleted` adapter test asserts the
  exception, not the stored row — after the failed flush the test's database transaction is aborted, so a
  read would fail (not verified); no test pins "body errors before 404" for an unknown well-formed id; the
  uppercase id is tested on GET, not on PATCH; the `.invalid` test sends `Done` plus `color` instead of
  `done` plus `color` (stronger).
- The contract lists only `application/json` for the request body; `application/merge-patch+json` is accepted
  as the framework's tolerance and recorded in the spec (owner, 2026-10-07).
