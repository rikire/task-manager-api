# Tasks

## 1. Change the status (REQ-TASK-status-change)

- [x] 1.1 Phase `tests`: functional tests for `REQ-TASK-status-change.*` and the matrix rows (tasks inserted
  with SQL and `updated_at` in the past for both `.changed` and `.same-status`, so a moved or kept value is
  visible); adapter test for `.status-deleted`; every response checked against the contract; verify they
  fail for the right reason
- [x] 1.2 Phase `impl`: `Task::changeStatus()`, slice `ChangeTaskStatus` (request DTO, command, handler,
  controller), `TaskRepository::saveStatusChange()` (design D1–D4), `make openapi`; verify the 1.1 tests pass
  and `make deptrac` reports 0 violations
- [x] 1.3 Phase `refactor`; regenerate `docs/api/openapi.yaml`; review brief; verify `make check` is green
  and the endpoint works on the prod image

## 2. Polish

- [x] 2.1 Curl examples for the scenarios observable over HTTP (all but `.status-deleted`);
  `docs/architecture/asvs-l1.md` V2.3.1; README notes free transitions and the accepted concurrency; verify the
  examples against the prod image
- [x] 2.2 `verifier` subagent; verify: no unresolved finding left without the owner's decision
