# Tasks

## 1. JSON text in UTF-8 (REQ-API-json-utf8)

- [x] 1.1 Phase `tests`: functional tests for `REQ-API-json-utf8.*` on raw response bodies; responses checked
  against the contract; verify they fail for the right reason (escaped Cyrillic today)
- [x] 1.2 Phase `impl`: encoder decorator in `Shared` and its wiring (design D1); verify the 1.1 tests pass and
  `make deptrac` reports 0 violations
- [x] 1.3 Phase `refactor`; review brief; verify `make check` is green and Cyrillic is readable in `curl`
  output on the prod image

## 2. Polish

- [x] 2.1 ADR-0007 amendment; curl-examples note removed; README "Что дальше" and `IMP-009` updated;
  `docs/README.md` lists `API` among `<CAP>`; verify `make check`
- [x] 2.2 `verifier` subagent; verify: no unresolved finding left without the owner's decision
