# Design: task-crud

## Context

Motivation and scope: `proposal.md`. Behaviour: `specs/tasks/spec.md`. Decided elsewhere: ADR-0005
(validation, errors), ADR-0006 (modules; Task may use Status's `Domain`), ADR-0007 (schema, codes,
response shape). The `status-catalog` change built the patterns this change repeats: request DTO with
`#[MapRequestPayload]`, read model with `jsonSerialize()`, Doctrine adapter with XML mapping, functional
tests through `ApiTestCase` with contract checks.

Significance checklist: a data schema (table `task`, decided in ADR-0007 D1, D2); no new module boundary
beyond ADR-0006, no new dependency. The decisions below are local.

## Decisions

### D1. `Task` references the `Status` entity

Scope: local. ADR: ADR-0006 (Task's `Domain` may use Status's `Domain`), ADR-0007 (D1). `Task` holds a
`Status` (Doctrine `many-to-one`, column `status_id`, `ON DELETE RESTRICT` in the migration). The read model
takes the status `name` from it. Alternative: keep only the status id in `Task` — rejected, the list would
need a second lookup per task.

### D2. Status by name through the Status port

Scope: local. ADR: ADR-0006, ADR-0007 (D5: "unknown status by name" is its own exception class, 422, not
the 404 of `StatusNotFound`). The port gets `StatusRepository::findByName(StatusName): ?Status`; the Task
module's handlers turn `null` into its domain exception `UnknownStatus` (422). Create uses it for `new`,
which the migration seeds; if it is missing, the create handler throws `\LogicException` (500 without
internals, owner 2026-10-07) — a broken data invariant, not a client error.

### D3. Dates

Scope: local. Driver: owner (seconds, UTC). The handler takes the time from `new \DateTimeImmutable('@'.time())` —
UTC, whole seconds; columns store UTC wall-clock time and the read model formats without
timezone conversion (PHP's default timezone is UTC in the images; not set elsewhere). `Task` keeps
`createdAt` and `updatedAt`; columns `timestamp(0)` (Doctrine `datetime_immutable`). The read model formats
`Y-m-d\TH:i:s\Z`. No clock port: no test freezes time (they assert the format and `created_at` =
`updated_at`), and a port would have one implementation (`RUL-CODE-yagni`).

### D4. Description normalisation

Scope: local. Drivers: `REQ-TASK-create`; owner (2026-10-07).

- `description`: the DTO checks the raw value with `/^(?:[\t\n\r]|\P{Cc})*\z/u` (control characters other than
  TAB, LF, CR refused anywhere, before trimming), then `Length(max: 5000)` with the trimming normalizer; the
  value object `TaskDescription` trims and turns an empty result into `null`.
- `title`: the status `title` rule. `TaskTitle` is the Task module's own value object with the same rule and
  its own `trim()`; the Task DTO normalizes with `TaskTitle::trim` and `TaskDescription::trim`, because a
  Task `Http` class must not use Status's `Domain` (ADR-0006). The duplicated rule (about 20 lines) is
  accepted: the two titles are unrelated (ADR-0007 reading notes) and may diverge.

### D5. List without N+1

Scope: local. Driver: rule of list endpoints (query count must not grow). `TaskRepository::list(?Status)`
fetch-joins the status in one DQL query (`SELECT t, s FROM Task t JOIN t.status s … ORDER BY t.id`).

### D6. Filter query

Scope: local. ADR: ADR-0005 (`#[MapQueryString]` with `validationFailedStatusCode: 422`), ADR-0007 (D5).
`ListTasksQuery { public ?string $status = null }` with `#[Assert\NotBlank(allowNull: true)]`,
`Length(max: 50)` and the name pattern; the controller argument is nullable, so a request without a query
string skips the DTO. With any query parameter (`?foo=1`) the DTO is built with `status = null` and passes;
`?status=` stays `''` and fails `NotBlank` (vendor `RequestPayloadValueResolver`, `AbstractObjectNormalizer`,
read by `spec-auditor`). `?status[]=x` fails the type and answers 422.

### D7. Task schema in the contract

Scope: local. ADR: ADR-0003, ADR-0006. The `Task` response schema is written in
`config/packages/nelmio_api_doc.yaml` next to `Problem`, and the controllers reference it: generated from
`TaskView`'s PHP properties it would say `createdAt`, while the API returns `created_at` (ADR-0007 D4); a
`#[SerializedName]` attribute on `TaskView` would put a serializer dependency into `Application`.

## Risks / Trade-offs

- [Deleting a status that tasks use answers 500 until `status-delete`: the foreign key refuses the delete,
  but no endpoint deletes statuses yet] → no risk now; `status-delete` adds the 409.

## Migration Plan

One migration: table `task` (`id uuid`, `title varchar(255)`, `description text null`, `status_id uuid not
null` → `status(id)` `ON DELETE RESTRICT`, `created_at`, `updated_at` `timestamp(0)`), index on `status_id`.
`down()` drops the table.
