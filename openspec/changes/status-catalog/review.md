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
