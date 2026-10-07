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

## Groups 3–4 — style and skills, curl examples, FAIL-005 (tasks 3.1–3.2, 4.1–4.2), 2026-10-07

**Ready to commit:** yes. `make check` green (PHP-CS-Fixer with the new config, PHPStan, Deptrac 0
violations, PHPUnit 19 tests, hooks 31, scripts 52, `openspec validate` 2/2, `roadmap-check`).

**What changed:** PHP-CS-Fixer adds `@Symfony:risky`, `@PHP8x4Migration`, `ordered_class_elements` and
mandatory `declare(strict_types=1)` (dry run first: 4 files changed — `strict_types` in `src/Kernel.php`,
`config/preload.php`, `public/index.php`; `\sprintf` in one test); six `RUL-CODE-…` rules; deviations
from Symfony Best Practices in `docs/architecture/README.md` §9; no skill or plugin installed; candidate
cards in `design.md`. `docs/api/curl-examples.md` skeleton; `FAIL-005` was written with group 1.

**Key decisions:** owner, 2026-10-07, answers 1a–4a (`design.md`, step 13 cards).

**Simplifications:** none.

**Debt / improvements:** none new. Open: PER-CS 3.1 has no fixer set yet — revisit when PHP-CS-Fixer
ships one (noted in the card, not a register entry: nothing to fix on our side).

**Not done:** whether controllers extend `AbstractController` — decided with the first endpoint
(`status-catalog`), noted in `docs/architecture/README.md` §9.

**Maturity:**

| Axis | Level | Why |
|---|---|---|
| Functionality | working minimum | style guide expressed in config, the rest in rules with sources |
| Reliability | not applicable | no runtime behaviour |
| Performance | not applicable | — |
| Security | not applicable | no skill or plugin installed, so no new code from outside |
| Maintainability | production-ready | formatting automated; rules cite their source; deviations listed in one place |
| Observability | not applicable | — |
| Consumer experience | working minimum | `curl-examples.md` defines the example format; the examples come with product changes |

**Top improvements:** none proposed now; `RUL-CODE-…` rules other than formatting are checked in review
only — a PHPStan rule for exception messages could be an `IMP` if review finds violations.

## Group 5 — form checks, attribution, markdown, security review (tasks 5.1–5.5), 2026-10-07

**Ready to commit:** yes, once this section exists (`make forms-check` found it missing — the check of
`FAIL-006` working on its own change). `make check` otherwise green: scripts 73 tests, hooks 31,
markdownlint 0 issues in 38 files, lychee offline 0 errors.

**What changed:** `scripts/forms.py` (11 form rules, `design.md` D4) in `make check`; `Assisted-by`
required on agent commits (`CLAUDECODE=1`, D5); `make md` / `make md-fix` and a CI step for external
links (D6); `/security-review` copied from upstream with DoS and rate limiting in scope; the
`change-workflow` skill writes review briefs to `review.md`. ADR-0001 options numbered (owner, "a").

**Key decisions:** owner, 2026-10-07: check list D4 ("a"), ADR-0001 reformat ("a"), keep the auto-fix
edits of protected files and exclude them from `make md-fix` (`FAIL-007`, "1a 2a"). Agent decisions,
reported: markdownlint config (D6); the external-link step skips `docs/pre-init` and accepts 403/429.

**Simplifications:** markdownlint rules MD036, MD041, MD060 off and line length 120 (D6); external links
of the frozen `docs/pre-init` history are not checked.

**Debt / improvements:** none new; `IMP-007` (false positives of the shell-write guard) fired three
more times in this group.

**Not done:** none in this group.

**Agent errors in this group:** `markdownlint-cli2 --fix` rewrote six protected files (`FAIL-007`); the
agent had claimed every ADR conformed to the form rules after checking only sections and fields.

**Maturity:**

| Axis | Level | Why |
|---|---|---|
| Functionality | working minimum | each D4 rule has a failing-then-green test; markdown and links checked locally and in CI |
| Reliability | working minimum | checks fail closed; external links can still flap on sites that change status codes |
| Performance | production-ready | the new local checks add about a second to the Stop hook |
| Security | working minimum | `/security-review` now covers DoS and rate limiting; the guard bypass of `FAIL-007` closed for markdown fixes only |
| Maintainability | working minimum | form rules live in one script; the security command stays diffable against upstream |
| Observability | working minimum | one message per violation naming file and rule |
| Consumer experience | working minimum | the form check names the missing section or field |

**Top improvements** (the owner chooses now or register):

1. `IMP-007` (false positives of the shell-write guard) — proposed: register with trigger; it is now the
   most frequent friction.
2. Check the upstream `security-review.md` for changes before each submission — proposed: Polish
   (task 7.3), one diff.

## Group 6 — PHPStan rules, Infection, Psalm taint (tasks 6.1–6.4), 2026-10-07

**Ready to commit:** yes. `make check` green (PHPUnit 20 tests including the new rule test, PHPStan
with the new rules over `src`, `tests`, `tools`); `make taint` 0 errors; `make mutation` exits 0 with no
changed sources. Tasks 6.3 and 6.4 are ticked once their CI steps are green on the pull request (cut
line of the proposal).

**What changed:** `shipmonk/dead-code-detector`, two rules of `thecodingmachine/phpstan-strict-rules`
and the project rule `CatchReturnsDefaultRule` (tests first, accepted by the owner) in `make stan`;
cognitive complexity as a report-only config, target and CI step; Infection with pcov in the dev image
(report-only CI step on pull requests); Psalm 6.19 with the Symfony plugin 5.3, taint analysis only, a
failing CI step. Each tool was proven on a temporary probe file, deleted afterwards: dead method, empty
`catch`, missing rethrow, `TaintedShell`.

**Key decisions:** owner, 2026-10-07: 1a, 2a, 3a (candidate cards in `design.md`). Agent decisions,
reported: the Infection installer plugin is disallowed (`allow-plugins: false`, no Infection extensions
used); git and `safe.directory` added to the dev image for `--git-diff-lines`; the project rule lives
in `tools/` (namespace `App\Tools\`), outside the Deptrac layers.

**Simplifications:** complexity and mutation score report without failing (`IMP-001`, `IMP-008`); the
project rule judges only literal return values, not computed fallbacks.

**Debt / improvements:** `IMP-008-mutation-score-threshold`. `IMP-007` fired once more (the word
`docker` in a command matched the protected `docker/` path).

**Not done:** whether Psalm sees input arriving through `#[MapRequestPayload]` DTOs is not verified —
the first product endpoint is the place to check it (a probe with a DTO). PHPUnit 13.4 with Infection is
verified only for the empty diff; a real mutation run waits for product code.

**Maturity:**

| Axis | Level | Why |
|---|---|---|
| Functionality | working minimum | every tool runs and was shown to report a planted problem |
| Reliability | working minimum | taint fails CI; complexity and mutation only report until thresholds exist |
| Performance | working minimum | PHPStan run slightly longer; Psalm ~8 s and Infection run in CI only |
| Security | working minimum | taint analysis on request input; DTO input coverage not verified |
| Maintainability | working minimum | one project rule with its own test; tools pinned in `composer.lock` |
| Observability | working minimum | Infection annotates pull requests; others print per-file findings |
| Consumer experience | not applicable | — |

**Top improvements** (the owner chooses now or register):

1. Verify Psalm taint through `#[MapRequestPayload]` with the first endpoint — proposed: a task in
   `status-catalog`.
2. Set the mutation threshold — `IMP-008`, trigger after the first product change.

## Group 7 — README, decision records, verification, freeze (tasks 7.1–7.4), 2026-10-07

**Ready to commit:** yes. `make check` green; `/opsx:verify` found no critical issue besides the open
tasks 7.3–7.4; the upstream `security-review.md` is unchanged since the copied commit `c19afa7`.

**What changed:** README section "Проверки сверх базовых требований" (`CON-DELIV-justify-extras`);
decision records 07, 15, 17, 24, 34 and `docs/architecture/asvs-l1.md` amended to what was built;
checklist 35 fully marked; the session-start hook and the `change-workflow` skill now point to the
roadmap (tests first, accepted by the owner); `bwrap`/`socat` no longer requested while the sandbox is
off; complexity report threshold 0 and a real `continue-on-error` step; new check configs, `tools/**`
and `scripts/*.py` under `ask`; `docs/pre-init/` frozen; change archived.

**Reviews and their results:**

| Review | Findings | Result |
|---|---|---|
| `reader-tester` on amended records and README | 34, mostly unchanged paragraphs contradicting the amendments (sandbox still "on", "if time allows" after the product, statuses "by hand") | fixed in the documents |
| `verifier` on the whole change | F1 hook and skill ignored the roadmap (contradicted the `FAIL-005` remedy); F2 README missed two extras; F3 new check configs not under `ask`; F4–F6, F10–F13 stale statements; F7 `-` instead of `continue-on-error`; F8 simplifications only here; F9 freeze could not name its own commit; H1 the complexity report printed nothing below the threshold (confirmed with a probe) | F1 tests first; F3 owner "2a"; F9 owner "3a"; the rest fixed; H1 threshold 0 |

**Key decisions (owner, 2026-10-07):** hook tests accepted; `ask` extended (F3); freeze mark "the
commit that adds this line", freeze and archive in one last commit (F9); `IMP-004` stays registered.

**Simplifications:** `design.md` S5–S7 (recorded in this group for groups 5–6).

**Debt / improvements:** none new; open for later: `forms.py` should fail when the section list from
`openspec/config.yaml` cannot be read; the project rule misses nested returns (S7); no machine check
keeps `docs/pre-init/` frozen; the `AGENTS.md` command table lacks `md-fix`, `taint`, `mutation`,
`complexity`, `forms-check` — proposed for the first `chore/` after `status-catalog`.

**Not done:** none of the tasks; `IMP-004` by the owner's choice.

**Maturity:**

| Axis | Level | Why |
|---|---|---|
| Functionality | working minimum | every Done criterion of the proposal has evidence |
| Reliability | working minimum | checks fail closed; complexity and mutation only report |
| Performance | production-ready | Stop hook 15.5 s of a ~60 s budget |
| Security | prototype | sandbox off (`DEBT-001`); taint analysis and `ask` rules hold the rest |
| Maintainability | working minimum | process documented once, frozen history amended to match |
| Observability | working minimum | review results and decisions recorded per group |
| Consumer experience | working minimum | README explains every extra check in one table |

**Top improvements** (the owner chooses now or register):

1. Command table in `AGENTS.md` — proposed: next `chore/` change.
2. Fail `forms.py` on an unreadable section list — proposed: `IMP` entry if it ever bites.
