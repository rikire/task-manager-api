# quality Specification

## Purpose
Quality attribute scenarios of the task manager API: measurable deployability and maintainability
targets that drive architecture decisions and are checked by tests or CI.

## Requirements

### Requirement: QAS-DEPLOY-clean-clone-start — the API starts from a clean clone

A reviewer with only Docker on the host SHALL bring the API up from a fresh clone by following README
with at most 2 commands and no manual steps, after which `GET /api/statuses` answers 200. Start-up time
is not limited. Artifact: the repository and its compose stack. Environment: a machine with Docker only, amd64 or arm64.
Check: the CI clean-clone job, or a manual run from a fresh clone before submission until the job
exists; the 200 response applies once the endpoint exists. Source: `docs/task/assignment.txt:108,135`.

#### Scenario: QAS-DEPLOY-clean-clone-start.fresh-clone
- **WHEN** the reviewer clones the repository and runs the start commands from README (at most 2)
- **THEN** `GET /api/statuses` answers 200 without any other action

#### Scenario: QAS-DEPLOY-clean-clone-start.database-not-ready
- **WHEN** the database is not yet accepting connections when the stack starts
- **THEN** the application does not start serving until the database is ready, and the start does not
  fail because of the order in which containers came up

#### Scenario: QAS-DEPLOY-clean-clone-start.no-migrations-yet
- **WHEN** the stack starts on a fresh clone before any migration exists
- **THEN** the migration step succeeds with nothing to apply and the application starts

#### Scenario: QAS-DEPLOY-clean-clone-start.migration-fails
- **WHEN** a database migration fails during start-up
- **THEN** start-up stops with a non-zero exit (which process reports it — the start command or the
  application container — is decided in the runtime ADR), the error is in the logs, the failed migration
  is not re-run automatically, and the API is not served on an unmigrated schema

### Requirement: QAS-DEPLOY-prod-image — one production image, configured only by environment

The production build of the application image SHALL contain no development dependencies and run with
debugging off, and SHALL take every environment-specific setting (database connection, secrets) from
environment variables, so the same image runs in any environment without a rebuild; the application
environment and debug flag are fixed by the production build, not configuration. Artifact: the application image, production build. Environment: the reviewer's start and CI.
Source: owner decision 2026-10-07 (deployment as an architecture driver; deploying itself is out of
scope, `docs/task/assignment.txt:141`; extras are justified in README, line 124).

#### Scenario: QAS-DEPLOY-prod-image.env-only
- **WHEN** the production image starts with the required environment variables set
- **THEN** the API answers HTTP requests, debugging is off and no development dependency is installed

#### Scenario: QAS-DEPLOY-prod-image.no-internals
- **WHEN** a request in the production image causes an unexpected error
- **THEN** the response is 500 without a stack trace or other internals

#### Scenario: QAS-DEPLOY-prod-image.missing-variable
- **WHEN** a required environment variable is not set
- **THEN** start-up fails with a non-zero exit and names the missing variable; no default is used

### Requirement: QAS-MAINT-layering — HTTP entry points do not reach the database

HTTP entry points (controllers) SHALL NOT depend on the persistence layer or the database connection, and
layers SHALL have no dependency cycles; the dependency-rule check reports 0 violations. Business logic
outside controllers is a rule checked in review, not part of this measure. Artifact: application code.
Environment: CI on every push. Source: `docs/task/assignment.txt:137`.

#### Scenario: QAS-MAINT-layering.clean
- **WHEN** the dependency-rule check runs in CI
- **THEN** it reports 0 violations

#### Scenario: QAS-MAINT-layering.controller-queries-database
- **WHEN** a change makes an HTTP entry point depend on the persistence layer or the database connection
- **THEN** the dependency-rule check reports a violation and CI fails

#### Scenario: QAS-MAINT-layering.cycle
- **WHEN** a change creates a dependency cycle between layers
- **THEN** the dependency-rule check reports a violation and CI fails

### Requirement: QAS-MAINT-typing — strict static typing without suppressions

The static type analyser at its strictest level SHALL report 0 errors on the application code and
tests, with no baseline of suppressed errors; an inline suppression is allowed only with a `DEBT-` or
`IMP-` register ID. Artifact: application code and tests. Environment: CI on every push. Source:
`docs/task/assignment.txt:137`.

#### Scenario: QAS-MAINT-typing.clean
- **WHEN** the static type analyser runs in CI at its strictest level
- **THEN** it reports 0 errors and uses no baseline file

#### Scenario: QAS-MAINT-typing.type-error
- **WHEN** a change introduces a missing or mismatched type
- **THEN** the analyser reports an error and CI fails

#### Scenario: QAS-MAINT-typing.suppression-without-id
- **WHEN** a change adds an inline suppression without a `DEBT-` or `IMP-` ID, or adds a baseline
- **THEN** CI fails

### Requirement: QAS-MAINT-readability — bounded cognitive complexity

Every method of the application code SHALL stay at or below a cognitive complexity threshold. The
threshold is set by measuring the code of the first product change and is held by
`IMP-001-readability-threshold` until then; before it is set, CI prints the per-method complexity score
without failing. Artifact: application code. Environment: CI on every push. Source:
`docs/task/assignment.txt:137`, decision record 32.

#### Scenario: QAS-MAINT-readability.within-threshold
- **WHEN** the complexity check runs in CI after the threshold is set
- **THEN** no method exceeds the threshold

#### Scenario: QAS-MAINT-readability.over-threshold
- **WHEN** a change adds a method above the threshold, after the threshold is set
- **THEN** the complexity check fails CI and names the method

### Requirement: QAS-MAINT-readme-matches-code — README architecture items match their sources

Every item of README's architecture decisions section SHALL link its source — an `active` ADR, a `REQ-`
requirement or a `DEBT-`/`IMP-` entry; a link to a missing or `deprecated` ADR is a failure. Every active
ADR whose Confirmation is machine-checkable SHALL pass it in CI; review-only Confirmations are checked in
the pre-submission review. Artifact: README and ADRs. Environment: CI on every push and the
pre-submission review. Source: `docs/task/assignment.txt:138`.

#### Scenario: QAS-MAINT-readme-matches-code.linked
- **WHEN** README is checked
- **THEN** each architecture item links an existing `active` ADR, `REQ-` requirement or `DEBT-`/`IMP-`
  entry

#### Scenario: QAS-MAINT-readme-matches-code.stale-link
- **WHEN** a README architecture item links a missing or `deprecated` ADR, or has no link
- **THEN** the check fails and names the item

#### Scenario: QAS-MAINT-readme-matches-code.confirmation-fails
- **WHEN** the machine-checkable Confirmation of an active ADR fails (its test or dependency rule)
- **THEN** CI fails, so README cannot describe a decision the code no longer follows
