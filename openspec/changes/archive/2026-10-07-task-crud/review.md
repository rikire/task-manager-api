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

## Group 3 — delete (tasks 3.1–3.3), 2026-10-07

**Ready to commit:** yes. PHPUnit `OK (182 tests)`; on the prod image (isolated stack) `DELETE` answered 204
and, repeated, 404.

**Data flow:** `DELETE /api/tasks/{id}` → `Requirement::UUID` (else no route → 404) → `DeleteTaskController`
→ `DeleteTaskHandler(DeleteTask)` → `TaskRepository::get()` (`TaskNotFound` → 404) → `remove()` → 204 without a
body.

**Must read:** `src/Task/Application/DeleteTask/DeleteTaskHandler.php`.

**Check by hand:** curl examples "Удалить задачу" in `docs/api/curl-examples.md`.

**Key decisions:** ADR-0007 D4 (204), D5 (404).

**Corner-case matrix and red output:** the owner asked for groups 3 and 4 together (chat); red run: 2 of 3
on assertions (405 instead of 204); the malformed-id test was already green (no route → 404 in JSON).

**Deviation:** after adding the route the tests still saw 405 until the test kernel's no-debug cache was
cleared (the known stale-cache trap, `.agent-state/notes.md`).

**Simplifications:** none. **Debt:** none. **Not done:** none in this group.

**Maturity:** as groups 1–2.

**Extra checks:** `verifier` on the whole change (task 4.2), findings below with group 4.

## Group 4 — Polish (tasks 4.1–4.2), 2026-10-07

**Ready to commit:** yes, after `make check`; task 4.1 is ticked only when the pull request's `clean-clone`
run is green (the step ran by hand on the prod image of an isolated stack).

**Data flow:** unchanged.

**Must read:** `docs/api/curl-examples.md`, section "Задачи".

**Check by hand:** every curl example of "Задачи" against `make up` — done on the isolated stack: 201 with
`Location`; 422 on `title` and on `status`; 200 / 404; list, `?status=new`, `?status=archived` → 422; delete
204, again 404.

**`verifier` findings and what was done:**

- Non-object JSON bodies (`42`, `null`, `[]`) were tested only with `"x"` for tasks — added (owner): 422.
- Dates in list items were not checked to end in `Z` (ADR-0007 Confirmation) — added (owner).
- The CI step accepted any 2xx — now checks 201 and `status` `new`.
- Design D3 described the time source differently from the code — corrected; a DTO comment cited ADR-0007
  D3 for task rules — corrected.
- Accepted as covered by the same code path (owner): a title of ASCII spaces only, a control character in the
  middle of a title, `DELETE` with an uppercase id, the order of a filtered list, the query count of a
  filtered list.
- Not acted on (notes): an unused `ListTasksQuery` component in the generated contract (Nelmio leftover);
  `TaskView` formats without `setTimezone()` — correct while PHP's default timezone is UTC; two concurrent
  `DELETE`s may both answer 204 (unverified); `GET /api/tasks/{id}` loads the status lazily (2 queries).

**Simplifications:** the coverage gaps accepted above.

**Debt:** none new.

**Not done:** none — 4.1 confirmed by the `clean-clone` run of PR #24.

**Maturity:** functionality — working minimum (CRUD of tasks without status change); reliability —
production-ready for this scope; performance — production-ready (list one query); security — production-ready;
maintainability — production-ready; observability — prototype; consumer experience — working minimum.

**Extra checks:** `verifier` run (findings above).
