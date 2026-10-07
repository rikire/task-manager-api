# Tasks

## 1. API harness (ADR-0003, ADR-0005, ADR-0006 `Shared`)

- [x] 1.1 Phase `tests`: Deptrac fixture tests for the `Shared` layer (a `Shared` class using a module, a module class
  using `Shared`); unit test of the `detail` normalizer (an `App\` exception below 500 → its message; a vendor
  exception and any 500 → unchanged); functional tests with debug off: unknown `/api/…` path → 404 and test-only
  throwing route → 500, both JSON without an `Accept` header and valid against the `Problem` schema; verify they fail
  for the right reason
- [x] 1.2 Phase `impl`: `ApiTestCase`, `Problem` component schema, `_format: json` on app routes and the `/api`
  request-format listener (ADR-0005, amended), test-only routes, `DomainProblemNormalizer` and the `Shared` layer in
  `deptrac.yaml`, first `docs/api/openapi.yaml` (design D6, D7); verify the 1.1 tests pass (migrations in
  `tests/bootstrap.php` moved to 2.1: no table before group 2, and tests are locked in phase `impl`)
- [x] 1.3 `make openapi`, `make openapi-check` in `make check` (pre-commit and CI); verify `make openapi-check` fails
  after a hand edit of `docs/api/openapi.yaml` and passes after `make openapi`
- [x] 1.4 Phase `refactor`; review brief; verify `make check` is green

## 2. Initial statuses, list and read (REQ-STATUS-initial, REQ-STATUS-read)

- [x] 2.1 Phase `tests`: functional tests for `REQ-STATUS-initial.fresh-database` and `REQ-STATUS-read.*` (the
  non-seed status inserted through the repository, design D8), migrations in `tests/bootstrap.php`; ADR-0007 tests:
  list order, unknown query parameter ignored, `abc` and an uppercase existing id → 404 with JSON and no `Accept`
  header; `/api/doc` stays HTML; wiring test of group `ADR-0003-api-contract` (a response that breaks the `GET
  /api/statuses` schema fails with a schema error); migration test for the unique index on `status.name`; verify they
  fail for the right reason
- [x] 2.2 Phase `impl`: `Status` domain (entity, `StatusId`, `StatusName`, `StatusTitle`, ports `StatusRepository`,
  exception `StatusNotFound`), slices `ListStatuses` and `GetStatus` with `StatusView`, routes with
  `Requirement::UUID`, `StatusNotFound` → 404, Doctrine adapter and XML mapping, migration with the seed, error
  responses annotated (design D2, D5); verify the 2.1 tests pass and `make deptrac` reports 0 violations
- [x] 2.3 Phase `refactor`; regenerate `docs/api/openapi.yaml`; curl examples for `GET /api/statuses` and `GET
  /api/statuses/{id}`; review brief; verify `make check` is green and the examples work against `make up`

## 3. Create a status (REQ-STATUS-create, ADR-0005 codes with a body)

- [x] 3.1 Phase `tests`: functional tests for `REQ-STATUS-create.*` and the proposal's corner-case rows for the body,
  `name` and `title`; ADR-0005 tests for 400, 415, 422 (empty body, wrong type) and 405 (no `Accept` header, JSON
  asserted) on `/api/statuses`; ADR-0007 tests for 201 + `Location`; the 409 `detail` with debug off; unit test of the
  adapter turning a unique violation into `StatusNameTaken`; verify they fail for the right reason
- [x] 3.2 Phase `impl`: slice `CreateStatus` (DTO with the attribute options and constraints of design D3, handler,
  `StatusNameTaken` → 409), adapter catch of the unique violation, error responses annotated (design D3, D4); verify
  the 3.1 tests pass; a non-object body may give 400 or 422 (owner), a 500 is a defect
- [x] 3.3 Phase `refactor`; regenerate `docs/api/openapi.yaml`; curl examples for `POST /api/statuses` (created,
  invalid values, unknown field, duplicate); review brief; verify `make check` is green and the examples work against
  `make up`

## 4. Polish

- [ ] 4.1 CI `clean-clone` (`QAS-DEPLOY-clean-clone-start`, `REQ-STATUS-initial.restart`): after the first and the
  second `up`, `GET /api/statuses` → 200 listing `new`, `in_progress`, `done` once each, in that order; and
  `POST /api/statuses` → 201 on the prod image (tests run with dev dependencies and missed a 500 there, group 3);
  verify the job is green
- [ ] 4.2 ADR-0002 check: with the database stopped, `GET /api/statuses` on the `prod` image answers 500 without a
  stack trace; verify by hand, output in the review brief
- [ ] 4.3 Names; error message wording (`RUL-CODE-exception-messages`); boundary input; `docs/architecture/asvs-l1.md`
  rows marked "запланировано: изменение `status-catalog`" point to their tests; `docs/architecture/README.md` §9
  sentence on `AbstractController` replaced by design D1, §5 gets the `Shared` layer; no out-of-scope changes in the
  diff; verify with the review brief and the `verifier` subagent
