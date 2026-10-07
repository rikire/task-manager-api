# Review: status-catalog

Review brief per task group (template: `.claude/skills/change-workflow/SKILL.md`), kept with the change
(`FAIL-006`).

## Group 1 — API harness (tasks 1.1–1.4), 2026-10-07

**Ready to commit:** yes. `make check` exit 0: PHPStan no errors, Deptrac 0 violations, PHPUnit
`OK (32 tests, 152 assertions)`, `openapi-check`, markdown and links green.

**Data flow:** a request under `/api/…` → `ApiRequestFormatListener` (priority 64, before the router) sets
the request format to JSON → router; on an exception, framework.exceptions maps a project exception to its
code (wrapping it in an `HttpException`) → the error renderer calls the serializer →
`DomainProblemNormalizer` (decorates `serializer.normalizer.problem`) puts the project exception's message
into `detail` when the status is below 500 → JSON problem body.

**Must read:**

- `src/Shared/Infrastructure/Http/DomainProblemNormalizer.php` — the only place error bodies are changed.
- `src/Shared/Infrastructure/Http/ApiRequestFormatListener.php` — why errors of unmatched paths are JSON.
- `tests/Functional/ApiTestCase.php` — every later functional test builds on it.

**Check by hand:** `make dev`, then `curl -si http://localhost:8080/api/nope` → 404, `application/json`,
problem body; `curl -si http://localhost:8080/api/doc` → 200 HTML (Swagger UI untouched).

**Key decisions:** ADR-0005 (amended: `detail` normalizer, request-body rules, JSON for unmatched
requests), ADR-0006 (amended: layer `Shared`), design D6–D8.

**Corner-case matrix and red output:** accepted by the owner in phase `tests` (chat, 2026-10-07): 4
failures on assertions — `'Conflict'` instead of the rule message, 404 instead of 500 and 409 (no test
routes), `text/html` instead of `application/json`; Deptrac rule tests red before the `Shared` layer.

**Deviations from the plan, each agreed in chat:**

- `deptrac.yaml` got the `Shared` layer in phase `tests`: with a pass-through skeleton in `src/`, the Stop
  hook's Deptrac check was red, and without it PHPStan was red (the test referenced a missing class).
- `ApiTestCase::assertProblem` fixed in a second `tests` phase (owner): the league validator needs JSON
  objects as arrays, not `stdClass`.
- `phpunit.xml.dist` sets `<env name="APP_ENV" value="test" force="true">`: the dev container exports
  `APP_ENV=dev`, which `KernelTestCase` reads before `$_SERVER`.
- The request-format listener (owner's choice over accepting HTML); ADR-0005 amended.
- Contract drift runs as `make openapi-check` inside `make check` (pre-commit and the CI job
  `clean-clone`), not as a separate CI job `contract-drift`; the owner chose to amend ADR-0003.
- Migrations in `tests/bootstrap.php` moved to task 2.1.

**Simplifications:** the listener matches the `/api/` path prefix as a string; Swagger UI (`/api/doc`,
`/api/doc.json`) is the only exception and is not covered by a test yet.

**Debt:** none. Observation for `.agent-state/notes.md`: a kernel booted with debug off reuses its cached
container and does not see changed files; after changing services, clear both test caches
(`cache:clear --env=test` with and without `--no-debug`).

**Not done:** none in this group.

**Maturity:** functionality — working minimum (no product endpoint yet); reliability — working minimum;
security — production-ready for error bodies (no internals at 500, checked with debug off);
maintainability — production-ready (layer rule enforced); observability — prototype (framework logging
only); consumer experience — working minimum (JSON errors everywhere under `/api`). Improvements: a test
that `/api/doc` stays HTML — now, in group 2's tests.

**Extra checks:** `verifier` not run for this group (harness only; planned for the change in 4.3);
`/security-review` not needed yet (no input parsing of product data).

## Group 2 — initial statuses, list and read (tasks 2.1–2.3), 2026-10-07

**Ready to commit:** yes, after `make check` (output in the chat brief). PHPUnit `OK (75 tests)`, PHPStan
no errors, Deptrac 0 violations, `doctrine:schema:validate` OK; the prod image (`make up`) served the curl
examples of `docs/api/curl-examples.md`.

**Data flow:** `GET /api/statuses` → `ListStatusesController` (`src/Status/Infrastructure/Http/ListStatuses/`)
→ `ListStatusesHandler` → port `StatusRepository::all()` → `DoctrineStatusRepository` (`findBy` ordered by
`id`, one query) → `StatusView` list → `{"items": …}`. `GET /api/statuses/{id}`: route requirement
`Requirement::UUID` (non-UUID → no route → 404) → `GetStatusController` → `GetStatusHandler(GetStatus)` →
`StatusRepository::get()` → `StatusNotFound` → 404 via `framework.exceptions`, message in `detail`.

**Must read:**

- `migrations/Version20261007120000.php` — schema, unique index and the fixed seed ids.
- `src/Status/Domain/StatusTitle.php` — the title rule (reject control characters, then trim, then length).
- `src/Status/Application/StatusView.php` — exactly what a response contains.

**Check by hand:** the three curl examples of `docs/api/curl-examples.md`, section "Статусы", against
`make up`.

**Key decisions:** design D2 (read models, `\JsonSerializable`), D5 (migration and seed), D8 (test data
by SQL); ADR-0006, ADR-0007.

**Corner-case matrix and red output:** accepted by the owner in phase `tests` (chat): 9 functional tests
red on assertions.

**Deviations, each agreed in chat or stated here:**

- Process slip: the domain value objects (`StatusName`, `StatusTitle`, `StatusId`) were written in phase
  `impl` before their tests. The owner approved a second phase `tests`; the unit tests were green on the
  first run except the entity constructor test. Their strength was checked with Infection instead: 17
  mutants, 16 killed; the escaped one (`StatusTitle::trim` public) was fixed by making it private → MSI
  100%.
- `phpstan.neon`: `containerXmlPath` (services built by the container count as used) and
  `objectManagerLoader` (`tools/PHPStan/object-manager.php`, Doctrine-written entity fields count as
  written); `make container-xml` warms the dev cache before `stan`, `lint-file` and `complexity`.
- `StatusView` implements `\JsonSerializable` instead of relying on `ObjectNormalizer`: see design D2.
- The list handler takes no query object (no input); `GetStatusHandler` takes `GetStatus` (ADR-0006).
- `phpunit.xml.dist`: `memory_limit` 512M. With more functional tests, the PHPStan rule test ran out of the
  CLI default 128M when the random order put it late (`make check` red once, green twice after the fix).

**Simplifications:** the list has no limit (pagination is out of scope, assignment line 141).

**Debt:** none.

**Not done:** `REQ-STATUS-initial.restart` is checked in CI (task 4.1).

**Maturity:** functionality — working minimum (read only); reliability — working minimum (constraints in
the database); performance — production-ready for this size (one query per list, test guards it);
security — production-ready (read models list fields explicitly; no internals in errors); maintainability
— production-ready (layer rules, typed, mutation-tested domain); observability — prototype;
consumer experience — working minimum. Improvements: Cyrillic in JSON is escaped (`\u041d…`) — valid
JSON, but `JSON_UNESCAPED_UNICODE` would make curl output readable — registry: `IMP-009-json-unescaped-unicode`
(first offered as "now", `FAIL-008`); README badges — roadmap row 9.

**Extra checks:** `verifier` planned for the change (task 4.3).

## Group 3 — create a status (tasks 3.1–3.3), 2026-10-07

**Ready to commit:** yes, after `make check` (output in the chat brief). PHPUnit `OK (117 tests)`; the
prod image (`make up`) answered the four curl examples of "Создать статус" as documented.

**Data flow:** `POST /api/statuses` → `ApiRequestFormatListener` (JSON) → `#[MapRequestPayload]` builds
`CreateStatusRequest` (`mapWhenEmpty`, extra fields and type errors → 422) and validates it →
`CreateStatusController` → `CreateStatusHandler(CreateStatus)` → `Status` with `StatusName`, `StatusTitle`
→ `DoctrineStatusRepository::save()` (unique index → `StatusNameTaken` → 409) → 201, `StatusView`,
`Location` from the router.

**Must read:**

- `src/Status/Infrastructure/Http/CreateStatus/CreateStatusRequest.php` — every input rule in one place.
- `src/Status/Infrastructure/Persistence/DoctrineStatusRepository.php` — `save()`: the unique index is the
  only duplicate check.
- `src/Shared/Infrastructure/Http/DomainProblemNormalizer.php` — `setSerializer()` (the group-1 defect below).

**Check by hand:** the curl examples "Создать статус", "Неверные значения", "Лишнее поле", "Имя занято"
against `make up`.

**Key decisions:** design D3 (DTO, `ObjectNormalizer` with `symfony/property-access`), D4 (index only);
ADR-0005 (amended), ADR-0007 D3.

**Corner-case matrix and red output:** accepted by the owner (chat): 36 red on assertions (405 without
the route; adapter skeleton).

**Deviations and findings, each agreed in chat or stated here:**

- Group-1 defect: `DomainProblemNormalizer` did not implement `SerializerAwareInterface`, so the framework
  normalizer never got the serializer and every 422 lost its `violations`. Group 1 had no 422 test; the
  group-3 tests caught it. Fixed by forwarding `setSerializer()`.
- `symfony/property-access` added to production dependencies (owner, candidate card in chat): every
  object denormalizer uses it; the prod image answered 500 to `POST /api/statuses`. My group-2 note that
  `PropertyNormalizer` would avoid it was not verified on the prod image and was wrong; the workaround
  is removed. Task 4.1 now also checks `POST` on the prod image.
- No body and no `Content-Type` → 422, not 415 (owner): the framework checks emptiness first; the test
  and the matrix row changed in a second phase `tests`.
- A JSON body that is not an object → 422 (observed; the owner accepted 400 or 422).
- `existsByName()` not written: no test needs it (design D4, `FAIL-009`).
- The invalid-UTF-8 case moved out of a data provider: PHPUnit printed raw bytes and crashed the Stop hook
  (`IMP-010-stop-hook-non-utf8-output`).
- Infection on `src/Status` and `src/Shared`: 73 mutants, 67 killed. Of the 6 escaped: 2 code
  simplifications (the `/api/doc` exception in the listener was dead — removed; the exception code literal
  — removed), 2 test gaps closed with the owner's approval (`testOrdersByIdNotByInsertion`,
  `testLeavesPathsOutsideApiToTheDefaultFormat`, both shown to fail on the hand-applied mutant), 1 false
  positive from cached validator metadata (`IMP-011-infection-stale-validator-cache`, checked by hand).

**Simplifications:** none beyond design D4.

**Debt:** none; improvements `IMP-010`, `IMP-011`.

**Not done:** none in this group.

**Maturity:** functionality — working minimum (create, list, read); reliability — production-ready for
this scope (database constraints decide duplicates, concurrent case tested on the adapter); security —
production-ready (strict input, control characters refused, no internals in errors); maintainability —
production-ready; observability — prototype; consumer experience — working minimum (`IMP-009`).

**Extra checks:** `/security-review` recommended for the change before archiving (input parsing added);
`verifier` in task 4.3.
