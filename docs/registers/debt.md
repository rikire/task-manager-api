# Debt and improvement register

`DEBT-` — done below the norm, must be fixed. `IMP-` — works, could be better, with a trigger.
Format and rules: `docs/pre-init/10-debt-polish-headroom.md` §2.

## DEBT-001-sandbox-disabled

- **What is wrong:** the Claude Code Bash sandbox (decision record 15: filesystem isolation per command,
  network allowlist) is turned off in `.claude/settings.json`; every agent command runs without
  isolation, and the network allowlist is not enforced. Protection rests on permission rules and hooks.
- **Why it was done:** on this machine the sandbox fails for every command, before the command starts:
  `apply-seccomp: write /proc/self/setgroups (nested userns is capability-restricted; caller must provide
  CAP_SYS_ADMIN): Permission denied`. Tried 2026-10-07: plain `bwrap --ro-bind / / true` works; the host
  has `kernel.apparmor_restrict_unprivileged_userns = 1` (Ubuntu default), the probable cause (not
  verified). With `failIfUnavailable: true` each command needed an explicit sandbox override, so the
  sandbox gave no protection, only friction. Not tried: an AppArmor profile allowing user namespaces for
  Claude Code or `bwrap` only (needs `sudo`), or switching the sysctl off (weakens the whole machine). The
  owner decided (2026-10-07): fixing the sandbox is worth doing, but not for this project.
- **Risk:** a command the agent runs can read or write anything the user can, and reach any host; a
  prompt injection in fetched content would meet no isolation layer.
- **Marker:** `.claude/settings.json`, key `sandbox.enabled`; checklist 35, item "Песочница".
- **When to fix:** before the agent works with untrusted code or content in this repository, or in the
  next project on this machine — first a narrow AppArmor profile, then `sandbox.enabled: true` and the
  checklist item re-run.

## DEBT-002-hook-tests-on-handmade-events

- **What is wrong:** the unit tests of the Claude Code hooks (`.claude/hooks/tests/test_hooks.py`) feed
  events written by hand after the documented hook input format, not events recorded from a real
  session, as decision record 34 §2 and checklist 35 step 7 require.
- **Why it was done:** the hooks were built before a session could be recorded; on 2026-10-07 the owner
  chose to keep the tests as they are (change `finish-init`, final check of the initialization) because
  the hooks were exercised live many times in that session (blocks by `protect_tests.py`,
  `protect_ask_paths.py`, the Stop hook and `post_edit.py`).
- **Risk:** if Claude Code changes the event format, a hook may stop matching and let actions through
  while its tests stay green.
- **Marker:** `.claude/hooks/tests/test_hooks.py`, module docstring.
- **When to fix:** a hook fails to fire when it should, or a Claude Code release notes a change of the
  hook input format.

## IMP-001-readability-threshold

- **What could be better:** `QAS-MAINT-readability` has no cognitive complexity threshold yet, so the
  complexity check reports but does not fail CI.
- **Why not now:** decision record 32 sets the threshold after measurements, and there is no product
  code to measure before the first product change (owner, 2026-10-06; change `architecture-kickoff`).
- **Trigger:** the first product change is merged; its code is measured and the threshold set.
- **Size:** 1 config value (complexity check threshold), 1 CI step switched from report to fail, the
  `QAS-MAINT-readability` requirement text updated with the number.
- **Closed:** 2026-10-07 — the trigger fired (change `status-catalog`, groups 1–2 merged; measured method
  maximum 3, class maximum 6); the owner set method 5, class 20; `make complexity` is part of `make check`
  (pre-commit and CI), the report-only CI step is removed; `QAS-MAINT-readability` amended in change
  `status-catalog`.

## IMP-002-non-root-containers

- **What could be better:** the `app` and `migrate` containers run as `root`, the default of the
  `dunglas/frankenphp` image. A non-root user limits what a compromised process can do in the container.
- **Why not now:** nothing is deployed (deploying is out of scope, `docs/task/assignment.txt:141`); the
  reviewer runs the stack locally (owner, 2026-10-07; checklist step 8).
- **Trigger:** before any real deployment of the production image (`QAS-DEPLOY-prod-image`).
- **Size:** 1 `USER` line and ownership of `var/` in the `Dockerfile`; FrankenPHP's data and config
  directories made writable for that user; the clean-clone check re-run.

## IMP-003-roadmap-pipe-in-cells

- **What could be better:** `scripts/roadmap.py` splits a table row on every `|`, so a cell containing a
  literal or escaped pipe (for example `` `a|b` `` or `\|`) shifts the columns: the row is then skipped as
  a candidate, or its priority and status are read from the wrong cell.
- **Why not now:** no row of `docs/roadmap.md` contains a pipe inside a cell (checked 2026-10-07); a
  Markdown table parser would be the first dependency of the project scripts (change `finish-init`,
  group 1).
- **Trigger:** a roadmap row needs a pipe inside a cell, or a row is reported wrongly by
  `make roadmap-check`.
- **Size:** 1 function (`cells`) honouring backticks and `\|`, 2 tests in `scripts/tests/test_roadmap.py`.

## IMP-004-roadmap-missing-file-message

- **What could be better:** when `docs/roadmap.md` is missing (for example the script is run outside the
  repository root), `scripts/roadmap.py` stops with a Python traceback instead of one line naming the
  path; it still exits non-zero. When `openspec/changes/` is missing, the script sees no changes: every
  started row then fails as "no change folder", so nothing passes silently, but the message does not say
  the directory itself is missing.
- **Why not now:** both paths exist in this repository and `make` runs the script from the root; found in
  the group-1 review of change `finish-init` (the first wording of this entry, wrong about the second
  path, was corrected after the `verifier` review).
- **Trigger:** either message is seen outside a deliberate test, or the Polish group of `finish-init`
  (task 7.3) takes it.
- **Size:** 2 checks at the start of `main`, 2 tests.

## IMP-005-roadmap-parsing-edges

- **What could be better:** `scripts/roadmap.py` recognises only backtick code fences in `tasks.md`
  (`~~~` fences are counted as text), and a row whose name cell has backticks but does not match the name
  rule (for example `` `x` (v2) ``) is silently treated as a candidate — it escapes every check, the order
  gate included. A warning for such rows and a printed list of rewritten statuses would make both visible.
- **Why not now:** no `tasks.md` uses `~~~` and no roadmap row has such a name cell (checked 2026-10-07);
  found by the `verifier` review of group 1 of change `finish-init`.
- **Trigger:** a `~~~` fence appears in a `tasks.md`, or a roadmap row with backticks is reported as a
  candidate.
- **Size:** 1 regex, 1 warning, 1 print; 3 tests.

## IMP-006-stop-hook-repeats-unchanged-red

- **What could be better:** while the agent waits for the owner's decision, the Stop hook re-runs the full
  check after every reply and blocks up to three times with identical output, although the working tree
  did not change. It could remember the fingerprint of the last red run and stay quiet on an unchanged
  tree.
- **Why not now:** the blocks are noise, not a hole: the agent cannot finish green anyway; observed three
  times in change `finish-init` on 2026-10-07.
- **Trigger:** the owner finds the repeated blocks costly, or a session hits the three-block limit while
  waiting.
- **Size:** the Stop-hook script (reuse its state directory), 2 hook tests.

## IMP-008-mutation-score-threshold

- **What could be better:** Infection runs on the changed lines of every pull request but only reports
  escaped mutants (GitHub annotations); without `--min-covered-msi` a weak test suite still passes CI.
- **Why not now:** there is no product code yet (`src/` holds only `Kernel.php`), so no mutation score to
  measure; checklist 35 sets thresholds after the first measurements (owner, 2026-10-07; change
  `finish-init`, group 6).
- **Trigger:** the first product change is merged and its pull request shows a mutation score.
- **Size:** 1 flag in the `mutation` target (`--min-covered-msi=<measured>`), the CI step comment, this
  entry closed.

## IMP-007-protect-ask-paths-false-positives

- **What could be better:** the PreToolUse hook against shell writes to `ask`-protected files
  (`FAIL-004`) blocks a command whose *text* mentions a protected path (for example `.claude/hooks/…` or
  `AGENTS.md` inside a register entry) together with any write form, even when the write goes to an
  unprotected file. It blocked such writes twice on 2026-10-07 (change `finish-init`); the work was redone
  with Edit, which is the intended path anyway.
- **Why not now:** it fails closed — the cost is a retry with Edit, never a missed protection; narrowing
  the match risks the bypass the hook exists to stop.
- **Trigger:** a false positive blocks a write that Edit/Write cannot do, or more than one retry per day
  is needed.
- **Size:** match only write targets (redirection target, `open(...)` path, `sed -i` file) instead of
  any mention; 3 hook tests.

## IMP-010-stop-hook-non-utf8-output

- **What could be better:** the Stop hook (`.claude/hooks/stop_check.py`) decodes the output of
  `make stop-check` as strict UTF-8. On 2026-10-07 (change `status-catalog`, group 3, phase `tests`) a
  failing test printed its data-provider argument — a deliberately invalid UTF-8 body — as raw bytes; the
  hook crashed with `UnicodeDecodeError` and blocked the turn ("internal error, blocking by design").
- **Why not now:** it fails closed, so nothing slipped through; the cause was removed in the test (the
  invalid bytes are built inside the test method, not passed through a provider). Changing the hook is an
  instruction-and-harness change the owner decides.
- **Trigger:** any other non-UTF-8 output reaches the hook (a tool's binary output, a test name with raw
  bytes), or the next change to the hook.
- **Size:** decode with `errors="replace"` in the hook's output reading; one hook test with invalid bytes in
  the output.

## IMP-011-infection-stale-validator-cache

- **What could be better:** Infection reports mutants of validation attributes (`#[Assert\Length(max: 50)]`
  → 51) as escaped although the tests kill them: the functional tests boot a kernel whose cached validator
  metadata in `var/cache/test` does not see the mutated attribute. Checked by hand on 2026-10-07 (change
  `status-catalog`, group 3): the same mutant with cleared caches fails `testReportsEachInvalidField`
  (500 instead of 422).
- **Why not now:** the score is report-only (`IMP-008-mutation-score-threshold`); a false "escaped" hides no
  defect, it only lowers the reported score.
- **Trigger:** `IMP-008` sets a failing threshold, or a review relies on an escaped attribute mutant.
- **Size:** run Infection with a cache directory per mutant process (for example `APP_CACHE_DIR` from
  `TEST_TOKEN`, if the kernel supports it — not verified) or disable the validator metadata cache in the
  test environment; one check that the hand-applied mutant above is reported as killed.

## IMP-013-initial-status-name-twice

- **What could be better:** the name of the initial status `new` is written twice:
  `StatusNotDeletable::INITIAL` (Status module) and `CreateTaskHandler::INITIAL_STATUS` (Task module). Renaming
  it in one place would let a new task get a status that can be deleted. Found by the `verifier` subagent
  (change `status-delete`, 2026-10-08).
- **Why not now:** the name is fixed by the seed migration and ADR-0007; Task may depend on Status `Domain`
  (ADR-0006), so the fix is small, but it touches the Task slice outside this change.
- **Trigger:** the initial status becomes configurable, or a third place needs the name.
- **Size:** one constant in Status `Domain` (for example on `StatusName`), used by both; no test changes.

## IMP-014-no-log-on-restrict-backstop

- **What could be better:** when the foreign key rejects a status delete (SQLSTATE 23001, the race the
  `StatusUsage` check missed), the API answers 409 and nothing is logged, so how often the race happens is
  unknown. Found by the `verifier` subagent (change `status-delete`, 2026-10-08).
- **Why not now:** observability is at the prototype level for the assignment (review maturity); the answer to
  the client is already correct.
- **Trigger:** logging or metrics are added to the application.
- **Size:** one log line at `info` in `DoctrineStatusRepository::remove()`.

## IMP-012-status-name-rule-in-three-dtos

- **What could be better:** the status name rule (`NotBlank`, `Length(max: 50)`, the pattern, its message and
  `htmlPattern`) is written out in three request DTOs: `CreateStatusRequest`, `ListTasksQuery`,
  `ChangeTaskStatusRequest`. ADR-0006's Revisit-when ("the same rule duplicated in two slices → move it into
  `Domain`") has fired. Found by the `verifier` subagent (change `task-status-change`, 2026-10-07).
- **Why not now:** the copies are identical and each is tested over HTTP; moving them changes three slices
  outside the change that found it. A Task `Http` class may not use Status's `Domain` (ADR-0006), so the shared
  place needs a decision (a constant in each module, or a `Shared` constraint).
- **Trigger:** the name rule changes, or a fourth DTO needs it.
- **Size:** one compound constraint (or constants) used by three DTOs; the existing tests stay as they are.

## IMP-009-json-unescaped-unicode

- **What could be better:** JSON responses escape non-ASCII characters, so a Cyrillic `title` reaches
  the client as `"\u0412 \u0440\u0430\u0431\u043e\u0442\u0435"` instead of `"В работе"`. It is valid
  JSON and every client decodes it, but a reviewer reading `curl` output cannot read the titles
  (`docs/api/curl-examples.md`, section "Статусы").
- **Why not now:** not part of any task of change `status-catalog`; found while checking the prod image
  (2026-10-07, group 2). Out-of-scope ideas go to this register (AGENTS.md "Planning").
- **Trigger:** the README "Как запустить" examples are written, or a reviewer-facing check of the API by
  hand is prepared (submission checklist in `docs/roadmap.md`).
- **Size:** one setting — `JSON_UNESCAPED_UNICODE` in the JSON encoder context
  (`framework.serializer.default_context`) or in `AbstractController::json()` calls; one test asserting
  a literal Cyrillic `title` in the response body; the curl-examples note removed.
