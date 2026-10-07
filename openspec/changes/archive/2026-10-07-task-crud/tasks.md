# Tasks

## 1. Create and read (REQ-TASK-create, REQ-TASK-read)

- [x] 1.1 Phase `tests`: functional tests for `REQ-TASK-create.*` and `REQ-TASK-read.*` and the proposal's
  matrix rows for the body, `title`, `description`, response, path id and missing `new`; adapter test of
  `StatusRepository::findByName()`; migration test for the foreign key with `ON DELETE RESTRICT`; field-rule
  and D5 tests also in group `ADR-0007-api-conventions`; every response checked against the contract;
  verify they fail for the right reason
- [x] 1.2 Phase `impl`: `Task` domain (entity, `TaskId`, `TaskTitle`, `TaskDescription`, `TaskNotFound`,
  `UnknownStatus`, port `TaskRepository`), `StatusRepository::findByName()`, slices `CreateTask` and
  `GetTask`, `TaskView` in `Task/Application/` (shared by create, get, list — ADR-0006 amended), Doctrine
  adapter, XML mapping and its block in `config/packages/doctrine.yaml`, `TaskNotFound` → 404 in
  `framework.exceptions`, migration (design D1–D4); verify the 1.1 tests pass and `make deptrac` reports 0
  violations
- [x] 1.3 Phase `refactor`; regenerate `docs/api/openapi.yaml`; review brief; verify `make check` is green
  and `POST`/`GET` work on the prod image (`make up`)

## 2. List and filter (REQ-TASK-list)

- [x] 2.1 Phase `tests`: functional tests for `REQ-TASK-list.*` and the `?status=` and list matrix rows,
  query-count test 1 vs N tasks; task data by SQL with fixed ascending UUID v7 ids (no endpoint sets a
  status yet); verify they fail for the right reason
- [x] 2.2 Phase `impl`: slice `ListTasks` with `ListTasksQuery` (`#[MapQueryString]`, 422), fetch-joined
  list (design D5, D6), `UnknownStatus` → 422; verify the 2.1 tests pass
- [x] 2.3 Phase `refactor`; regenerate the contract; review brief; verify `make check` is green

## 3. Delete (REQ-TASK-delete)

- [x] 3.1 Phase `tests`: functional tests for `REQ-TASK-delete.*`; verify they fail for the right reason
- [x] 3.2 Phase `impl`: slice `DeleteTask`, `TaskRepository::remove()`; verify the 3.1 tests pass
- [x] 3.3 Phase `refactor`; regenerate the contract; review brief; verify `make check` is green

## 4. Polish

- [x] 4.1 Curl examples for every `REQ-TASK-…` scenario in `docs/api/curl-examples.md`; CI `clean-clone`
  creates a task on the prod image (`POST /api/tasks` → 201); verify the examples against `make up` and the
  job is green
- [x] 4.2 Names, messages, boundary input, no out-of-scope changes; verify with the `verifier` subagent: no
  unresolved finding left without the owner's decision
