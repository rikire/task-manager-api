# Review: task-crud

Review brief per task group (template: `.claude/skills/change-workflow/SKILL.md`), kept with the change
(`FAIL-006`).

## Group 1 — create and read (tasks 1.1–1.3), 2026-10-07

**Ready to commit:** yes, after `make check` (output in the chat brief). PHPUnit `OK (167 tests)`; the prod
image (isolated stack) answered `POST /api/tasks` → 201 with `Location`, `GET` → 200, `status` in the body →
422.

**Data flow:** `POST /api/tasks` → `CreateTaskRequest` (`#[MapRequestPayload]`, rules of design D4) →
`CreateTaskController` → `CreateTaskHandler(CreateTask)`: `StatusRepository::findByName('new')` (missing →
`\LogicException`, 500) → `Task` with `TaskTitle`, `TaskDescription`, the `Status` entity and the current
second in UTC → `DoctrineTaskRepository::save()` → `TaskView` → 201 + `Location`. `GET /api/tasks/{id}`:
`Requirement::UUID` → `GetTaskHandler(GetTask)` → `TaskNotFound` → 404.

**Must read:** `migrations/Version20261007130000.php` (FK `ON DELETE RESTRICT`),
`src/Task/Domain/TaskDescription.php` (control characters, trimming, null), `src/Task/Application/TaskView.php`.

**Check by hand:** `curl -s -i -X POST "$API/api/tasks" -H 'Content-Type: application/json' -d '{"title":
"Подготовить отчет"}'`, then `GET` the `Location`.

**Key decisions:** design D1–D4, D7; ADR-0007 D1, D2, D4.

**Corner-case matrix and red output:** accepted by the owner (chat): 38 red on assertions.

**Deviations:** `UnknownStatus` (task 1.2) is not written yet: no test of this group needs it; it comes with
the filter in group 2 (`FAIL-009`). The `Task` schema is written in the Nelmio config (design D7).

**Simplifications:** the task title rule duplicates the status title rule (design D4, accepted).

**Debt:** none.

**Not done:** none in this group.

**Maturity:** functionality — working minimum; reliability — production-ready for this scope (FK, value
objects); security — production-ready (strict input, no internals in errors); maintainability —
production-ready; observability — prototype; consumer experience — working minimum (`IMP-009`).

**Extra checks:** `verifier` in task 4.2.

## Group 2 — list and filter (tasks 2.1–2.3), 2026-10-07

**Ready to commit:** yes, after `make check`. PHPUnit `OK (179 tests)`; the prod image (isolated stack)
answered the list, `?status=new`, `?status=done` (empty), `?status=archived` (422 with `detail`) and
`?status=Done` (422, violation on `status`).

**Data flow:** `GET /api/tasks` → `#[MapQueryString(validationFailedStatusCode: 422)] ?ListTasksQuery`
(null without a query string; name rule on `status`) → `ListTasksHandler(ListTasks)`: a status name →
`StatusRepository::findByName()` or `UnknownStatus` (422, `detail`) → `DoctrineTaskRepository::list()` (one
query, status fetch-joined, `ORDER BY id`) → `TaskView` list → `{"items": …}`.

**Must read:** `src/Task/Infrastructure/Persistence/DoctrineTaskRepository.php` (`list()`),
`src/Task/Infrastructure/Http/ListTasks/ListTasksQuery.php`.

**Check by hand:** `curl -s "$API/api/tasks?status=archived"` → 422 `Unknown status "archived".`

**Key decisions:** design D5, D6; ADR-0007 D5.

**Corner-case matrix and red output:** accepted by the owner (chat): 12 red on assertions; the query-count
test first passed vacuously (both requests 404) and got a check that the list answered.

**Simplifications:** none. **Debt:** none. **Not done:** none in this group.

**Maturity:** as group 1; performance — production-ready for this size (one query per list, test guards
it; no pagination by the assignment).

**Extra checks:** `verifier` in task 4.2.
