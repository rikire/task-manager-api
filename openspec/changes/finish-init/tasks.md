# Tasks

Exception to "every group starts with failing tests" for groups without code (owner, 2026-10-07; see
proposal, Confirmed): such groups name their check instead.

## 1. Tracking in the repository

- [x] 1.0 In phase `tests`, the Stop hook tolerates failures of new or changed Python test files (`.claude/hooks/tests`, `scripts/tests`) as it does for PHPUnit; unchanged ones stay strict (owner, 2026-10-07: gap found when 1.1 turned the hook red). Tests first in `scripts/tests/test_stop_check.py`, then `scripts/stop_check.py unittest` and the phase-`tests` branch of `stop-check`; verify `make hooks-test` green and `make stop-check PHASE=tests` passes with 1.1's red tests
- [x] 1.1 Phase `tests`: write `scripts/tests/test_roadmap.py` covering the corner-case matrix of the proposal; verify they fail because `scripts/roadmap.py` does not behave yet (assertion failures against a stub), and the owner accepts the tests
- [x] 1.2 Phase `impl`: write `scripts/roadmap.py` (generate and `--check`, order gate); verify `make hooks-test` is green
- [x] 1.3 Add `roadmap-check` to `make check` and to the phase-`tests` subset of `stop-check`; verify `make check` runs it and passes on the current roadmap
- [x] 1.4 Run `scripts/roadmap.py` on `docs/roadmap.md`; verify `--check` passes and row 1b shows `в работе: N/M`
- [x] 1.5 AGENTS.md "Session state": read `docs/roadmap.md` at session start (instruction commit); verify `grep roadmap AGENTS.md`

## 2. Checks at setup (checklist 35)

- [ ] 2.1 Verify each of the 16 items and mark it `[x]` with evidence (command output or commit) in `docs/pre-init/35-init-checklist.md`; verify no item stays unmarked without a move confirmed by the owner
- [ ] 2.2 Measure the Stop-hook time (`make stop-check`); verify the number is recorded in checklist 35 and decides where later checks run (proposal, Confirmed)

## 3. Step 13 — style and skills for the stack

- [ ] 3.1 Search Symfony coding standards, best practices and existing skills (`researcher`); candidate cards in `design.md`; verify every card has the owner's decision
- [ ] 3.2 Apply the adopted items (rules, skills, config); verify `make check` green

## 4. Step 11 and FAIL-005

- [ ] 4.1 Write `docs/api/curl-examples.md` (purpose, example format; examples come with product changes) and list it in `docs/README.md`; verify the file exists and is listed
- [ ] 4.2 Write `FAIL-005` in `docs/registers/failures.md` with the remedy of group 1; verify its `Commit:` names the group-1 commit (entry written with group 1, owner 2026-10-07; ticked once the commit exists)

## 5. Cheap "if time allows" items (decision record 34 §3: 3, 5, 6, 7)

- [ ] 5.1 Phase `tests` → `impl`: change-form and ADR-form check scripts with tests; verify `make hooks-test` green and the scripts pass on the current repository
- [ ] 5.2 `Assisted-by` in `commit-msg` for agent commits, if checklist item 35 "attribution" showed they can be told apart; otherwise move to the roadmap with the reason; verify by a test in `scripts/tests/` or by the roadmap row
- [ ] 5.3 markdownlint and lychee `--offline` in `make check`, lychee with external links in CI; verify both green
- [ ] 5.4 Configure `/security-review` for DoS and rate limiting; verify the instruction file names both
- [ ] 5.5 Phase `tests` → `impl`: review-brief check (`FAIL-006`, owner 2026-10-07): an active change with a ticked task group has a `review.md` section for it with Simplifications, Debt and Maturity; rule in the `change-workflow` skill (instruction commit); verify tests green and `make check` passes on `finish-init`

## 6. Dependency items (decision record 34 §3: 4, 1, 2)

- [ ] 6.1 Candidate cards for PHPStan rule extensions, Infection, Psalm; verify the owner's decision on each in `design.md`
- [ ] 6.2 PHPStan rules (dead code, cognitive complexity, swallowed errors) as a CI step with `continue-on-error`; verify the step runs in CI
- [ ] 6.3 Infection on changed lines in CI; verify the CI step is green (cut line: if not green when the rest is done, move back to the roadmap)
- [ ] 6.4 Psalm taint analysis in CI; verify the CI step is green (same cut line)

## 7. Polish and freeze

- [ ] 7.1 README: one line per adopted extra check (`CON-DELIV-justify-extras`); verify each adopted item has its line
- [ ] 7.2 Amend decision records 17, 24, 34 to match what was implemented; verify with `reader-tester` on the amended sections
- [ ] 7.3 Review brief, `verifier` subagent; verify every Done criterion of the proposal has its evidence
- [ ] 7.4 Freeze `docs/pre-init/` (mark in its README) as the last commit; archive the change with `openspec archive --skip-specs`; verify `scripts/roadmap.py --check` shows row 1b `в архиве`
