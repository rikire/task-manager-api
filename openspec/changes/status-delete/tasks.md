# Tasks

## 1. Delete a status (REQ-STATUS-delete)

- [x] 1.1 Phase `tests`: functional tests for `REQ-STATUS-delete.*` and the matrix rows; adapter tests: the
  foreign key violation on delete becomes `StatusNotDeletable` (in use), `DoctrineStatusUsage` answers both
  ways; Deptrac fixture unchanged (Status never depends on Task); every response checked against the
  contract; verify they fail for the right reason
- [x] 1.2 Phase `impl`: port `StatusUsage` and `DoctrineStatusUsage`, `StatusNotDeletable` (409), slice
  `DeleteStatus`, `StatusRepository::remove()` (design D1–D3), `make openapi`; verify the 1.1 tests pass and
  `make deptrac` reports 0 violations
- [x] 1.3 Phase `refactor`; review brief; verify `make check` is green and the endpoint works on the prod image

## 2. Polish

- [x] 2.1 Curl examples for every scenario; README item "how status deletion is handled"
  (`CON-DELIV-readme-sections`); verify the examples against the prod image
- [x] 2.2 `verifier` subagent; verify: no unresolved finding left without the owner's decision
