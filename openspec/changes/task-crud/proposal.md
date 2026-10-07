# Proposal: task-crud

## Why

The assignment's core is managing tasks (`docs/task/assignment.txt:21-63`): create, read one, list with a
status filter, delete. Nothing of it exists yet; the status catalog it depends on is done
(`status-catalog`, archived). The owner merged the former roadmap rows "create and read", "filter by status"
and "delete" into this one change to save process overhead (2026-10-07).

## What Changes

One task group per behaviour, each a green commit:

1. **Create and read:** module `Task` (ADR-0006), migration with table `task` and the foreign key to
   `status` (`ON DELETE RESTRICT`, ADR-0007 D1, D2); `POST /api/tasks` → 201 with the task and `Location`,
   the new task gets status `new`; `GET /api/tasks/{id}`.
2. **List and filter:** `GET /api/tasks` → `{"items": […]}` by `id`; `?status=<name>` filters; an unknown
   status → 422 with `detail`, a malformed or empty value → 422 with a violation (ADR-0007 D5).
3. **Delete:** `DELETE /api/tasks/{id}` → 204; unknown → 404.
4. **Polish:** curl examples, CI check of the prod image (`POST /api/tasks` → 201), `verifier`.

## Out of scope

- Changing a task's status: change `task-status-change` (roadmap row 4).
- Deleting a status that tasks use: change `status-delete` (row 7); the foreign key already refuses it at
  the database level.
- Editing a task's title or description: not in the assignment.
- Pagination (assignment line 141).

## Capabilities

### New Capabilities

- `tasks`: creating, reading, listing, filtering and deleting tasks.

### Modified Capabilities

None.

## Impact

- New code: module `Task` (`Domain`, `Application` slices `CreateTask`, `GetTask`, `ListTasks`, `DeleteTask`,
  `Infrastructure/Http`, `Infrastructure/Persistence` with XML mapping); one migration; `StatusRepository`
  gets `findByName()` (the create slice needs the status `new`, the filter needs a status by name).
- New tests: functional tests per scenario, adapter tests on the test database.
- Docs: `docs/api/openapi.yaml`, `docs/api/curl-examples.md` (section "Задачи"), CI `clean-clone`.
- No new dependencies.

## Roadmap

`docs/roadmap.md`, row 3 `task-crud`.

## Coverage

| Category | Status | Rationale |
|---|---|---|
| Scope | clear | roadmap row 3 (owner, 2026-10-07): create, read, list, filter, delete |
| Data | clear | ADR-0007 D1, D4 (fields, FK, response shape); title, description, dates — owner, 2026-10-07 |
| Edge cases and failures | clear | ADR-0005 (amended), ADR-0007 D5; matrix below |
| Constraints | clear | `CON-STACK-rest`, `CON-STACK-php-symfony-pg`, `CON-DELIV-explain-without-ai`, `CON-PLAN-deadline` |
| Terminology | clear | task, status `name` — ADR-0007 reading notes |
| Non-functional | clear | `QAS-MAINT-layering`, `QAS-MAINT-typing`, `QAS-MAINT-readability`; list query count must not grow |
| Done criteria | clear | table below |

### Done criteria

| Deliverable | Check |
|---|---|
| Behaviour | every `REQ-TASK-…` scenario has a passing functional test; responses checked against the contract |
| Schema | migration test: foreign key `task.status_id → status.id` with `ON DELETE RESTRICT` |
| Prod image | CI `clean-clone`: `POST /api/tasks` → 201 |
| Docs | curl examples for every scenario pass against `make up` |

## Corner cases

Inputs: the `POST /api/tasks` body, the path id, the `?status=` query. Codes follow ADR-0005 (amended) and
ADR-0007; "violation on X" means 422 with a `violations` entry for X. Shape errors (unknown field, wrong
type) come alone, as for statuses.

| Input | Dimension | Expected behaviour |
|---|---|---|
| body | emptiness: `{}`, empty body | violation on `title` |
| body | type: valid JSON that is not an object (`"x"`, `42`, `null`) | 422 (as observed for statuses) |
| body | structure: unknown field, including `status` | violation on that field (ADR-0007: the create request has no `status`) |
| body | format: malformed JSON; non-empty body as `text/plain` | 400; 415 (ADR-0005; one case each here) |
| `title` | emptiness: missing, `null`, `""`, only spaces or NBSP | violation on `title` |
| `title` | format: any control character anywhere | violation on `title` (rule of status `title`, owner) |
| `title` | type: not a string (`42`, `[]`) | violation on `title` only (shape error) |
| `title` | trust: `<script>`, U+202E | stored and returned as given (JSON-encoded) |
| `title` | size: 255 code points after trimming / 256 | 201 / violation |
| `title` | state: equal to another task's title | 201: titles are not unique (owner) |
| `description` | emptiness: missing, `null` | 201, `description: null` (owner) |
| `description` | emptiness: `""`, only spaces | 201, `description: null` — trimmed, empty becomes null (owner) |
| `description` | format: newlines, tabs (`\n`, `\r`, `\t`) | 201, kept (multiline text, owner) |
| `description` | format: other control characters anywhere (`\u0000`, `\u001b`, final `\f`) | violation on `description`; checked before trimming |
| `description` | size: 5000 code points after trimming / 5001 | 201 / violation |
| `description` | type: not a string | violation on `description` |
| response | state: just created | `status` = `new`; `created_at` = `updated_at`, ISO 8601 UTC with `Z`, seconds |
| path id | format: not a UUID, uppercase | 404 |
| path id | state: unknown; deleted before | 404 |
| `?status=` | state: existing name (`done`) | only tasks with that status, in `id` order |
| `?status=` | state: unknown name (`archived`) | 422, `detail` `Unknown status "archived".` (ADR-0007 D5) |
| `?status=` | format: fails the name pattern (`Done`), empty (`?status=`) | 422, violation on `status` |
| `?status=` | size: 50 characters (no such status) / 51 characters | 422 `Unknown status` / 422, violation on `status` (owner) |
| `?status=` | structure: array (`?status[]=done`) | 422, violation on `status` |
| query | structure: unknown parameter, alone (`?foo=1`) or with `status` | ignored: all tasks / filtered (ADR-0007 D5) |
| list | state: no tasks | `{"items": []}` |
| list | size: 1 vs N tasks | the number of SQL queries does not grow (`.claude/rules/tests.md`, list endpoints) |
| create | state: status `new` missing from the catalog (corrupted data) | 500 without internals: a broken invariant, not a client error (owner) |
| delete | state: task deleted | 204, no body; then `GET` → 404, not listed |

## Confirmed

- Scope and merge of the former rows 3, 5, 6 (owner, 2026-10-07).
- All of ADR-0007 (owner, 2026-10-07).
- Owner, 2026-10-07 (interview of this change): `description` is optional — absent or `null` → `null`;
  trimmed; empty after trimming → `null`; up to 5000 code points; newlines and tabs allowed, other control
  characters → 422. Task `title` follows the status `title` rule. Task titles need not be unique. Dates are
  ISO 8601 UTC with `Z`, to the second.
- Owner, 2026-10-07 (after `spec-auditor`): a missing status `new` on create is a server error (500); a
  `?status=` value is checked by the full name rule, so 51 characters → 422 with a violation on `status`.

## Assumptions

None.

## Open questions

None.
