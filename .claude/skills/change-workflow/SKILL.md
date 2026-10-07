---
name: change-workflow
description: Carry an OpenSpec change from accepted proposal to a reviewed commit - TDD phases, polish, review brief, explain-back. Use when implementing or continuing an OpenSpec change, when the human asks to implement a slice, or before asking to commit.
when_to_use: /opsx:apply; "implement", "continue the change", "let's do the tests"; end of a task group; before any commit.
---

# Change workflow

Sources: `docs/pre-init/04-definition-of-done.md`, `05-tdd.md`, `10-debt-polish-headroom.md`,
`18-human-comprehension.md`, `25-work-history.md`.

## Before starting

- Branch `change/<change-name>` exists and is checked out (the active change).
- Proposal and specs are accepted by the human; `Assumptions` and `Open questions` are resolved.
- Read `.agent-state/notes.md`, the change `tasks.md` and `git status`.

## Per task group

1. **Phase `tests`** (`scripts/phase tests` — the human confirms the unlock). Write tests for the group's
   scenarios with the `test-writing` skill. Run them: they must fail for the right reason (an assertion,
   not a missing class or import). Present the red output and the corner-case matrix with a
   "covered by" column. The human accepts the tests.
2. **Phase `impl`** (`scripts/phase impl`, no confirmation needed). Minimal code to green. Tests are
   locked; if a test looks wrong — stop and ask.
3. **Phase `refactor`** (`scripts/phase refactor`). Structure only, tests stay green and locked.
4. Tick the group's tasks in `tasks.md` (status "done"; the end-of-turn hook re-runs the checks).
5. **Review brief**, then **explain-back**, then wait for the human's "commit".

The last group is **Polish**: names; error handling and error message wording (within the format the
human decided); logging; boundary input; API contract and docs updated; no out-of-scope changes.

## Review brief (template)

1. Ready to commit? Why.
2. Data flow: request path from entry through logic to storage, with file links (short).
3. Must read: places and why.
4. Check by hand: scenario and command.
5. Key decisions and why — links to `design.md` / ADR.
6. Corner-case matrix (when tests are accepted); red output of phase `tests`.
7. Simplifications / Debt / Not done — write "none" if empty.
8. Maturity per axis (functionality, reliability, performance, security, maintainability,
   observability, consumer experience): prototype / working minimum / production-ready / polished; top
   3–5 improvements, each proposed as "now (Polish)" or "registry with a trigger".
9. Recommended extra checks and their result: `verifier` for non-trivial work, `/security-review` when
   auth, input parsing, data access or secrets are touched, `reader-tester` for significant documents;
   pre-commit warnings (diff size).

Attach command output for every claim of "green".

## Explain-back

Ask 3–5 short questions ("what is returned if…", "where is … checked", "why X and not Y"), let the human
answer briefly, explain the gaps. The human may say "skip"; advise against skipping for architecturally
significant changes or new concepts.

Each question (source: `docs/registers/failures.md` FAIL-003):
- is about what exists in this diff or its accepted decisions — not about code or files that do not exist
  yet; for a decision-only diff ask "why X and not Y", not "what happens at run time";
- carries its own context: name the endpoint, field, file or ADR it refers to;
- explains every term on first use (for example "`flush()` — Doctrine writes the collected changes to the
  database").

## Commit

- Only on the human's request; one green commit per task group; message per AGENTS.md "Commits".
- Push and open a PR with auto-merge only on request.
- After the last group: `/opsx:verify`, then `/opsx:archive`; suggest `/clear`.
- Overwrite `.agent-state/notes.md` at the end of a work chunk.
