# Review: finish-init

Review brief per task group (template: `.claude/skills/change-workflow/SKILL.md`, decision records 10 §6
and 18 §1), kept with the change so that simplifications, debt and maturity survive the chat
(`FAIL-006`).

## Group 1 — tracking in the repository (tasks 1.0–1.5), 2026-10-07

**Ready to commit:** yes, after the second pass below. `make check` output is attached to the chat brief;
the order gate proven on the real roadmap: a temporary `openspec/changes/status-catalog/` gave
`status-catalog: change started while earlier mandatory rows are not archived: finish-init (FAIL-005)`,
exit 1.

**Data flow:** `scripts/roadmap.py` reads `openspec/changes/` and its archive → finds the changes table in
`docs/roadmap.md` by its exact header → generate mode rewrites only status cells; `--check`
(`make roadmap-check`, part of `make check`: pre-commit, Stop hook, CI) writes nothing and reports
violations. `scripts/stop_check.py unittest` replaces `hooks-test` in the phase-`tests` branch of
`stop-check`.

**Key decisions:** `design.md` D1–D3.

**Simplifications:** `design.md` S1–S4.

**Debt / improvements:** `IMP-003-roadmap-pipe-in-cells`, `IMP-004-roadmap-missing-file-message`,
`IMP-005-roadmap-parsing-edges`, `IMP-006-stop-hook-repeats-unchanged-red` (`docs/registers/debt.md`).

**Not done:** none in this group. Tasks added during the change: 1.0 (owner) and 5.5 (`FAIL-006`);
`FAIL-005` (task 4.2) written with this group.

### Verifier review and second pass

The `verifier` subagent could not refute "done" but found two major gaps; the owner decided each
(2026-10-07), and tests were reopened (phase `tests`, accepted by the owner) before the fixes:

| Finding | Decision | Result |
|---|---|---|
| F1: the generator silently rewrote a started row without a folder to `запланировано` | fails in both modes, row untouched | fixed, test in both modes |
| F2: matrix rows "no boxes" and `не делаем` had no discriminating test | add tests | 2 tests |
| F3: a row without a trailing pipe lost a cell on rewrite | fix | fixed, test |
| Order gate counted archived folders | active folders only | fixed, test |
| Unknown flag (`--chek`) ran the generator and wrote the file | reject, exit 2 | fixed, test |
| `stop_check.py unittest` passed on a missing directory | fail, name it | fixed, test |
| Weak tests (candidate fixture, `active` duplicate subtest) | strengthen | both now fail against a wrong implementation |
| One accepted test contradicted the F1 decision (`gamma` "в работе" without folder) | fixture `gamma` → `запланировано` (owner, "a") | fixed in phase `tests` |
| IMP-004 wording wrong; `FAIL-005` cited but missing; proposal Impact missed tasks 1.0 and 5.5 | correct the records | done |
| `~~~` fences, rows with backticks escaping checks, no list of rewrites | register | `IMP-005` |

**Maturity:**

| Axis | Level | Why |
|---|---|---|
| Functionality | working minimum | every row of the proposal's corner-case matrix has a test that fails against a wrong implementation (after the second pass) |
| Reliability | working minimum | fails closed on a missing header, duplicates, orphan rows and folders (both modes), unknown arguments, missing test directories; a missing roadmap file gives a traceback (`IMP-004`) |
| Performance | working minimum | reads a few dozen files; per-file test runs in phase `tests` are slower (S4, not measured) |
| Security | not applicable | local repository files only; no external input |
| Maintainability | working minimum | standard library only, same form as `git_checks.py`; `main` mixes parsing and checks in one loop |
| Observability | working minimum | one stderr line per violation naming the change or row; rewrites are not listed (`IMP-005`) |
| Consumer experience | working minimum | messages say what to run; pipes inside cells are misread (`IMP-003`) |

**Top improvements** (the owner chooses now or register):

1. Missing-path messages — `IMP-004`; proposed: Polish of this change (task 7.3).
2. Rows with backticks that escape every check — `IMP-005`; proposed: register with trigger.
3. Pipes inside cells — `IMP-003`; proposed: register with trigger.
4. Split `main` of `scripts/roadmap.py` into parsing and checking — proposed: Polish, if the
   cognitive-complexity report (task 6.2) flags it.
5. Stop hook repeating identical red runs while waiting — `IMP-006`; proposed: register with trigger.

## Group 2 — checks at setup (tasks 2.1–2.4), 2026-10-07

**Ready to commit:** yes. `make check` green (hooks 31 tests, scripts 52 tests, Deptrac 0 violations,
`openspec validate` 2/2, `roadmap-check`); a plain Bash command runs without a sandbox override.

**What changed:** 14 of 16 checks at setup in `docs/pre-init/35-init-checklist.md` are marked with
evidence or pointers; the remaining OpenSpec `verify` item waits for task 7.3 (owner). Found and fixed:
the TDD hook did not lock `scripts/tests/` and `.claude/hooks/tests/` for Edit/Write, nor Bash redirects
into nested `tests/` directories (task 2.3; tests first, accepted by the owner). The Bash sandbox is
turned off (task 2.4).

**Key decisions (owner, 2026-10-07):** sandbox off instead of an AppArmor fix; Composer release delay
and the sandbox fix are roadmap candidates; thresholds are mapped to `IMP-001`, task 6.3 and decision
record 34 §4.

**Simplifications:** none in code. The sandbox decision is recorded as debt, not as a simplification,
because it is below the norm of decision record 15.

**Debt / improvements:** `DEBT-001-sandbox-disabled`, `IMP-007-protect-ask-paths-false-positives`.

**Not done:** OpenSpec `verify` check (task 7.3). `.claude/hooks/BYPASSES.md` rows that relied on the
sandbox are rewritten; decision record 15 itself is amended with the other decision records in task 7.2.

**Agent error in this group:** before the owner accepted the task 2.3 test, the agent claimed Bash
writes into `scripts/tests/` were already blocked; the test showed redirects were not. Corrected in the
same task.

**Maturity:**

| Axis | Level | Why |
|---|---|---|
| Functionality | working minimum | every check has evidence or an owner-confirmed pointer |
| Reliability | prototype | without the sandbox, guards are permission rules and pattern-matching hooks with known bypasses (`BYPASSES.md`) |
| Performance | production-ready | Stop hook 12 s, per-edit format and lint under 3 s |
| Security | prototype | no command isolation, no network allowlist (`DEBT-001`) |
| Maintainability | working minimum | results live next to each checklist item; bypasses listed in one file |
| Observability | working minimum | Stop-hook output shows each failing check |
| Consumer experience | working minimum | false positives of the shell-write guard cost a retry (`IMP-007`) |

**Top improvements** (the owner chooses now or register):

1. Restore the sandbox with a narrow AppArmor profile — `DEBT-001`; proposed: roadmap candidate
   (owner's decision).
2. Fewer false positives of the shell-write guard — `IMP-007`; proposed: register with trigger.
3. Repeated identical Stop-hook blocks — `IMP-006`; proposed: register with trigger.
