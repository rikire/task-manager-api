# Proposal: finish-init

## Why

The initialization checklist (`docs/pre-init/35-init-checklist.md`) is not finished: step 11 lacks
`docs/api/curl-examples.md`, steps 13 (stack style guides and skills) and 14 (freezing `docs/pre-init/`)
are not done, and none of the 16 "checks at setup" is marked. The agent nevertheless started the first
product change, following its own session notes (`.agent-state/notes.md`, not in git) instead of the
checklist. Planning and progress were therefore invisible to the owner, and the roadmap went stale: it
still showed `architecture-kickoff` as "in progress" after it was archived.

## What Changes

**Tracking in the repository** (decision record 24 §3–4; pulled forward from decision record 34 §3.3 and
§4):

- This change is roadmap row 1b; every remaining initialization step is a task in `tasks.md` with its
  check.
- `scripts/roadmap` writes the status column of the changes table in `docs/roadmap.md` from
  `openspec/changes/` and its archive. Status strings, exactly: `запланировано`; `в работе` or
  `в работе: N/M` (N checked of M task boxes in `tasks.md`); `в архиве`.
- `scripts/roadmap --check` runs in `make check` (so pre-commit, the Stop hook outside phase `tests`,
  and CI) and in the phase-`tests` subset of the Stop hook. It compares the status **word** only; the
  `N/M` count is written by the generator and never checked, so ticking a task never turns a check red.
  It fails when:
  - a change folder (active or archived) has no roadmap row, or a row with status `в работе` or
    `в архиве` has no folder (this one fails the generator too, which then leaves the row as it is, so
    regenerating never hides a lost change);
  - the status word of a row differs from the generated one;
  - an active change folder exists in `openspec/changes/` while a row **above it in the table** with
    priority `обязательно` is not `в архиве` (remedy of `FAIL-005`; a folder with only a proposal counts;
    archived folders are history and never trip the gate).
- Unknown arguments are rejected without writing anything.
- Tests for the script are written first.
- `.agent-state/notes.md` keeps only volatile session state (where work stopped, hypotheses, dead
  ends); "what next" is read from the roadmap and the active `tasks.md`. AGENTS.md "Session state" adds
  `docs/roadmap.md` to the files read at session start (separate instruction commit).

**Remaining initialization steps** (checklist 35):

- Checks at setup: each of the 16 items is verified and marked with its evidence (command output or
  commit), or moved to the roadmap with the reason after the owner confirms the move.
- Step 13: search for Symfony and PHP coding standards, best practices and skills; candidate cards in
  `design.md`; the owner's decision on each is recorded there.
- Step 11: `docs/api/curl-examples.md` with its purpose and the format of an example; examples are added
  by each product change.
- `FAIL-005` in `docs/registers/failures.md` with the remedy above.
- Decision records made stale by this change are amended before the freeze: 17 (files read at session
  start), 24 (guarantee of statuses becomes a script check), 34 (order: the "if time allows" items go
  before the product, with the cut line below).
- Step 14, last: freezing `docs/pre-init/` (freeze commit named in its README).

**"If time allows" items pulled before the product** (decision record 34 §3, all seven, in its order):

1. Infection on changed lines in CI.
2. Psalm taint analysis in CI.
3. Change-form and ADR-form check scripts.
4. PHPStan rules: dead code, cognitive complexity, no swallowed errors — a separate CI step with
   `continue-on-error` until `IMP-001-readability-threshold` fires, not part of `make check`.
5. `Assisted-by` check in `commit-msg`, required only on agent commits (decision record 21 §3), and only
   if checklist item "attribution … можно ли отличить коммит агента" shows they can be told apart;
   otherwise the item moves to the roadmap with that reason.
6. markdownlint and lychee: in `make check` lychee runs `--offline` (local links only); external links
   are checked in CI only.
7. `/security-review` configured for DoS and rate limiting.

**Cut line** (decision record 24 §7): items 1 and 2 are dropped from this change, and moved back to the
roadmap, if they are not green in CI when every other task is done; the product (`status-catalog` …
`status-delete`) must still be ready by the control point of decision record 34 §1 (evening of
2026-10-08).

Every new dependency (items 1, 2, 4 and anything adopted in step 13) gets a candidate card; the owner
approves it before it is installed (`docs/pre-init/13-authority.md`).

## Out of scope

- Product behaviour: `status-catalog` and later changes start after this change is archived.
- The throughput script (decision record 24 §5): there is no history to compute it from.
- The rest of decision record 34 §4 (traceability scripts, intent-gate hooks, `breaker`, `docs/process/`).
- Turning the complexity check into a failing one: `IMP-001-readability-threshold` fires after the first
  product change.
- Abandoning a change (deleting its folder while its row says `в работе`): not supported; the owner
  edits the roadmap row by hand.

## Capabilities

### New Capabilities

None: this change touches tooling, process and documents, not product or quality behaviour. The change
declares `skip_specs: true` in its `.openspec.yaml`, which `openspec validate` (1.14.0) requires for a
change without deltas; it is archived with `openspec archive --skip-specs`.

### Modified Capabilities

None.

## Impact

- New: `scripts/roadmap` and its tests; `scripts/stop_check.py unittest` mode (task 1.0, design D3); the
  review-brief check (task 5.5, `FAIL-006`); change-form and ADR-form check scripts and their tests,
  `docs/api/curl-examples.md`, `FAIL-005` entry, `design.md` (candidate cards, script decisions).
- Modified: `docs/roadmap.md` (row 1b, generated statuses, "if time allows" paragraph),
  `docs/pre-init/17-session-state.md`, `docs/pre-init/24-planning-tracking.md`,
  `docs/pre-init/34-core-vs-roadmap.md`, `docs/pre-init/35-init-checklist.md` (checks marked),
  `docs/pre-init/README.md` (freeze mark), `README.md` (one line per extra check,
  `CON-DELIV-justify-extras`), `Makefile`, `.github/workflows/ci.yml`, `.githooks/commit-msg`,
  `composer.json` (approved dev dependencies), `phpstan.neon`; instruction files (AGENTS.md, `.claude/`
  for `/security-review`, `change-workflow` skill if it mentions the roadmap) in a separate commit.

## Roadmap

`docs/roadmap.md`, row 1b `finish-init`.

## Coverage

| Category | Status | Rationale |
|---|---|---|
| Scope | clear | owner: checklist 35 in order, tracking in the repository, change form, all seven "if time allows" items with cut line on items 1–2 (2026-10-07) |
| Data | clear | no data schema |
| Edge cases and failures | clear | roadmap script matrix below; check semantics decided by the owner |
| Constraints | clear | `CON-PLAN-deadline` — submission by 2026-10-09; control point "product ready" on the evening of 2026-10-08 (decision record 34 §1, amended here); cut line above |
| Terminology | clear | status strings as in What Changes |
| Non-functional | partial | Assumption 2 (time budget of the Stop hook, not yet measured) |
| Done criteria | clear | see Done criteria below |

### Done criteria

| Deliverable | Check |
|---|---|
| Tracking | tests of `scripts/roadmap` green; `scripts/roadmap --check` passes in `make check` and CI |
| Checks at setup | every item in checklist 35 is `[x]` with evidence or moved with the owner's confirmation |
| Step 13 | every candidate card in `design.md` has the owner's decision |
| Step 11 | `docs/api/curl-examples.md` exists and is listed in `docs/README.md` |
| `FAIL-005` | entry in `docs/registers/failures.md` with `Commit:` of the remedy |
| Items 1–7 | each is a green CI step or Makefile target, or moved back by the cut line / item 5 condition |
| Extras justified | one README line per adopted extra check |
| Decision records | 17, 24, 34 amended before the freeze |
| Freeze | `docs/pre-init/README.md` names the freeze commit; last commit of the change |

## Corner cases

Input: `docs/roadmap.md` and `openspec/changes/` as read by `scripts/roadmap`.

The changes table is found by its exact header `| # | Изменение | Что даёт | Приоритет | Статус |`;
other tables in the file (milestones) are ignored. A row names a change when its name cell is exactly
one backtick-quoted identifier matching `[a-z0-9-]+`; other rows are candidates and are left untouched.
An archived folder `archive/YYYY-MM-DD-<name>` matches `<name>`. Task boxes are lines matching
`^\s*- \[( |x|X)\]` outside code fences, nested ones included.

| Input | Dimension | Expected behaviour |
|---|---|---|
| change table | structure: header missing or changed | fails, names the expected header |
| row | structure: name cell not a backtick identifier (row 8 "кэш или очереди") | candidate: ignored, status cell left as is |
| row | structure: two rows name the same change | fails, names the change |
| row | structure: `#` not numeric (`1b`) | allowed; order is table position, never `#` |
| row | state: `запланировано`, no folder | valid |
| row | state: `в работе` / `в архиве`, no folder | fails in both modes, names the row; the generator leaves the row unchanged |
| active folder | state: no row | fails, names the change |
| archived folder | state: no row | fails, names the change |
| change name | state: active and archived at once, or archived twice | fails, names the change |
| `tasks.md` | emptiness: missing or no boxes | `в работе` without a count |
| `tasks.md` | state: all boxes checked, not archived | `в работе: M/M` (archiving is a separate step) |
| `tasks.md` | structure: boxes inside a code fence | not counted |
| status cell | state: word edited by hand, differs from generated | `--check` fails; `scripts/roadmap` rewrites it |
| status cell | state: only the count differs | `--check` passes; the generator rewrites it |
| active folder | state: an earlier `обязательно` row not `в архиве` | `--check` fails, names both rows (`FAIL-005`) |
| row | state: priority `не делаем` | treated like any other row |
| row | structure: no trailing pipe | parsed; the generator rewrites only the status cell |
| archived folder | state: below an open `обязательно` row | passes: the order gate counts active folders only |
| arguments | type: unknown flag | fails, writes nothing |

## Confirmed

- Finish checklist 35 before any product change (owner, 2026-10-07, answer "a").
- The work is an OpenSpec change `finish-init` with its `tasks.md` (owner, 2026-10-07).
- Planning and tracking live in the repository, not in the agent's notes (owner, 2026-10-07).
- AGENTS.md: read `docs/roadmap.md` at session start (owner, 2026-10-07).
- All seven "if time allows" items of decision record 34 §3 go before the product; decision record 34 is
  amended; cut line: items 1–2 (owner, 2026-10-07, answer "1a").
- `--check` compares the status word only; the count is informational (owner, 2026-10-07, "2a").
- lychee `--offline` locally, external links in CI only (owner, 2026-10-07, "3a").
- `FAIL-005` remedy: machine gate on order by table position; any active change folder counts, also one
  with only a proposal (owner, 2026-10-07, "4a"); archived folders do not count (owner, 2026-10-07,
  after the group-1 `verifier` review).
- A started row without a folder fails the generator as well as `--check` (owner, 2026-10-07, after the
  group-1 `verifier` review).
- Exception to "one change = one vertical slice" and "every task group starts with failing tests"
  (`openspec/config.yaml`): the tasks are small and serve one goal, initializing the project. Task groups
  without code (documents, checks at setup, step 13) skip phase `tests` and name their check instead
  (owner, 2026-10-07).
- Proposal accepted with its assumptions (owner, 2026-10-07): order of work — tracking, checks at setup,
  step 13, step 11, `FAIL-005`, items 1–7, decision-record amendments, step 14 last; a check that breaks
  the ~1-minute Stop-hook budget (measured in the checks at setup) runs in CI only; `Assisted-by` only on
  agent commits and only if they can be told apart; PHPStan rules as a non-failing CI step until
  `IMP-001` fires.

## Assumptions

None open: the order of work, the Stop-hook time budget and the form of items 4–5 were accepted
with the proposal (owner, 2026-10-07; moved to Confirmed).

## Open questions

None.
