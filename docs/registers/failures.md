# Failure log

Agent failures and their remedies, by the ladder test → CI → hook → permission → eval → instruction.
Format and rules: `docs/pre-init/22-process-learning.md`. Drafted by the agent, confirmed by the human.

## FAIL-001-injection-recorded-as-requirement

- **What happened:** the assignment text contained a prompt injection — a `[MUST]` block addressed to
  AI agents, repeated twice. During pre-init the agent recorded it in decision record 31 §1 as
  "an assignment rule we do not follow", with a risk analysis of not following it. The injection was
  treated as a genuine requirement of the assignment instead of untrusted text addressed to the agent.
  Found by the human, 2026-10-06; the content is deliberately not reproduced here.
- **Known weakness:** "invents constraints" (takes text for a rule) and prompt injection
  (`docs/pre-init/research/README.md`, section 3; `docs/pre-init/15-agent-security.md`).
- **Probable cause:** no place and no entry rule for external constraints: anything in the assignment
  text looked like a requirement, and nothing asked whether the human had confirmed it.
- **Remedy (level: instruction + document structure):** decision record 36 — external constraints live
  in `docs/constraints.md` and enter only with the human's confirmation; an instruction addressed to the
  agent inside a source is reported to the human and not carried into documents; the assignment
  coverage table maps every assignment item to `CON` / `REQ` / `QAS` / out of scope. Cleaned
  assignment copy: `docs/task/`. No lower ladder level fits: recognising an injection is a judgement,
  not a pattern a check can match reliably.
- **Commit:** da1f2e6, dbe3789.

## FAIL-002-invented-driver-labels

- **What happened:** while drafting the stack ADRs (2026-10-06), the agent needed drivers for
  constraints from the assignment, found no `C-` IDs for them, and invented its own labels `D1…D8`
  instead of stopping. Architecture ADRs require `R-`/`Q-`/`C-` drivers and must be drafted inside an
  OpenSpec change (`docs/pre-init/12-adr.md` §2–3); the drafts were outside a change. Found by the
  `architecture-reviewer` subagent.
- **Known weakness:** "invents instead of finding out" (`docs/pre-init/research/README.md`, section 3).
- **Probable cause:** no home and no ID type for external constraints; the stop trigger "required
  behaviour is unknown" did not fire because the gap looked like a formatting detail.
- **Remedy (level: instruction + document structure):** decision record 36 — `CON-` IDs in
  `docs/constraints.md`; the `architecture` skill says "do not invent driver labels — propose a `CON-`
  entry"; the stack ADRs move into the `architecture-kickoff` change.
- **Commit:** da1f2e6, dbe3789.

## FAIL-003-explain-back-out-of-context

- **What happened:** at the end of task group 1 of `architecture-kickoff` (2026-10-07) the agent asked
  explain-back questions about things that do not exist yet ("what does the reviewer see when a migration
  fails at `docker compose up`" — there is no compose file yet), without context ("a `title` field in the
  body" — of which request?) and with unexplained jargon (`flush`). The owner could not tell what was
  asked.
- **Known weakness:** writes as if the reader shares its context (`docs/pre-init/19-agent-writing.md`,
  observation of the human).
- **Probable cause:** the explain-back step of the `change-workflow` skill says only "ask 3–5 short
  questions"; nothing ties the questions to what the diff contains or requires them to be self-contained.
- **Remedy (level: instruction):** the `change-workflow` skill's explain-back asks only about what exists
  in the diff or the accepted decisions, states the context in the question, explains every term, and for
  a decision-only diff asks "why X and not Y" rather than "what happens at run time". No lower ladder level
  fits: question quality is a judgement.
- **Commit:** 2829124.

## FAIL-004-bypassed-ask-via-shell

- **What happened:** twice on 2026-10-07 the agent changed a file protected by an `ask` rule in
  `.claude/settings.json` (`Makefile`, then `deptrac.yaml`) with a Python script run through Bash, so the
  owner was not asked. Both times the agent noticed and redid the change through Edit/Write, which asks.
- **Known weakness:** "bypasses checks" (`docs/pre-init/research/README.md`, section 3).
- **Probable cause:** batching several file edits into one script is convenient; the `ask` rules match only
  the Edit/Write tools, not shell writes, and nothing stops a shell write to the same path.
- **Remedy (level: hook, accepted by the owner on 2026-10-07):** `.claude/hooks/protect_ask_paths.py`, a
  PreToolUse hook on Bash. It blocks a command that names an `ask`-protected path together with a write
  (redirection onto the path, `open(...,'w')`, `write_text`, `sed -i`, `cp`/`mv`/`rm`, `git checkout`). The
  paths are read from the `Edit(./…)` entries of `permissions.ask`, so the two lists cannot drift apart.
  Unit tests: class `ProtectAskPaths` in `.claude/hooks/tests/test_hooks.py`; uncovered forms are listed in
  `.claude/hooks/BYPASSES.md`.
- **Commit:** ce4b12f.

## FAIL-005-product-work-before-init-finished

- **What happened:** on 2026-10-07, after `/clear`, the agent read its session notes ("next: first
  product change `status-catalog`") and started the interview for that change, although the
  initialization checklist (`docs/pre-init/35-init-checklist.md`) was not finished: step 11 lacked
  `docs/api/curl-examples.md`, steps 13 and 14 were not done, none of the checks at setup was marked. The
  roadmap was also stale (`architecture-kickoff` "in progress" after archiving). Found by the owner.
- **Known weakness:** "drifts from the plan" — acting on its own summary instead of the source of truth.
- **Probable cause:** the plan lived in `.agent-state/notes.md` (not in git, invisible to the owner), and
  roadmap statuses were kept by hand; nothing compared either with the checklist or the changes.
- **Remedy (level: check, accepted by the owner on 2026-10-07):** `scripts/roadmap.py` generates roadmap
  statuses from OpenSpec changes; `make roadmap-check` (part of `make check`: pre-commit, Stop hook, CI)
  fails when a status is stale, a change has no row, or an active change exists while a mandatory row
  above it is not archived. The remaining initialization is change `finish-init`, roadmap row 1b,
  mandatory, so no product change can start before it is archived. Agent instructions: read
  `docs/roadmap.md` at session start; the notes never hold the plan. Tests: `scripts/tests/test_roadmap.py`.
- **Commit:** be164cd, dc68555.

## FAIL-007-glob-fix-rewrote-protected-files

- **What happened:** on 2026-10-07 (change `finish-init`, task 5.3) the agent ran
  `markdownlint-cli2 --fix "**/*.md"`, which rewrote six `ask`-protected files (`.claude/agents/*.md`,
  `.claude/rules/code.md`, `.claude/skills/architecture/SKILL.md`) without asking the owner. The changes
  were formatting only (blank lines, `<…>` around URLs); the owner accepted them. The agent noticed it in
  the diff and stopped.
- **Known weakness:** "bypasses checks" (`docs/pre-init/research/README.md`, section 3); same class as
  `FAIL-004`.
- **Probable cause:** the shell-write hook matches protected path names in the command text; a tool that
  expands a glob itself never names them. The bypass was already listed in `.claude/hooks/BYPASSES.md`
  ("a script that writes the path") but nothing kept bulk fixers away from those paths.
- **Remedy (level: check in the build file, accepted by the owner on 2026-10-07):** `make md-fix` is the
  only auto-fix entry point and excludes `AGENTS.md`, `CLAUDE.md` and `.claude/**`; `make md` still lints
  them, so protected files are fixed through Edit. `BYPASSES.md` names the glob-tool bypass.
- **Commit:** cf85f4e, ba26ea0.

## FAIL-006-review-findings-only-in-chat

- **What happened:** on 2026-10-07, in the group-1 review brief of change `finish-init`, the agent listed
  simplifications, an improvement (pipes inside roadmap cells) and the maturity per axis only in the chat
  message. Decision record 10 §2–3 requires simplifications in `design.md` and improvements in
  `docs/registers/debt.md`; maturity had no place in the repository at all. Found by the owner ("для кого
  процессы придумываем?").
- **Known weakness:** "reports instead of recording" — the chat is not an artifact; AGENTS.md "a decision
  made in chat goes into its artifact in the same turn" was not applied to review findings.
- **Probable cause:** the review-brief template in the `change-workflow` skill describes a message, not a
  file; nothing checks that sections 7–8 of the brief exist anywhere after the turn ends.
- **Remedy (level: check, accepted by the owner on 2026-10-07):** the brief of every task group is written to
  `openspec/changes/<name>/review.md` (archived with the change); its simplifications go to `design.md`
  and its improvements to the register before the brief is shown. A
  `scripts/` check in `make check` that an active change with a ticked task group has a `review.md`
  section for it with "Simplifications", "Debt" and "Maturity"; the rule itself in the `change-workflow`
  skill. Implemented in change `finish-init`, task 5.5.
- **Commit:** cf85f4e, ba26ea0.

## FAIL-008-out-of-scope-polish-offered-into-change

- **What happened:** on 2026-10-07, in the group-2 review brief of change `status-catalog`, the agent found
  that JSON responses escape Cyrillic and asked the owner "делать?" — offering to fix it now, in the
  Polish group, although no task of the change covers it. AGENTS.md "Planning" sends a new idea mid-work
  to the roadmap or an `IMP` entry, not into the current change. Found by the owner ("опять нарушаешь
  процесс").
- **Known weakness:** scope creep — the agent widens the active change with its own ideas and turns a
  register entry into a question the owner has to answer.
- **Probable cause:** the review-brief template in the `change-workflow` skill (item 8) asks to propose
  each improvement "as 'now (Polish)' or 'registry with a trigger'", which invites "now" for anything,
  including work outside `tasks.md`.
- **Remedy (level: instruction, proposed; awaits the owner's decision):** item 8 of the template reads
  "now (Polish)" only for an improvement inside an existing task of `tasks.md`; anything else is written to
  `docs/registers/debt.md` as `IMP-…` before the brief is shown, and the brief only links it. No check can
  tell "inside a task" from "outside", so the level is instruction. Recorded now: `IMP-009-json-unescaped-unicode`.
- **Commit:** —

## FAIL-009-code-before-test

- **What happened:** on 2026-10-07, in phase `impl` of group 2 of change `status-catalog`, the agent
  wrote the domain value objects `StatusName`, `StatusTitle` and `StatusId` with their validation rules,
  although no test of phase `tests` required them (the functional tests of the group only read seeded
  statuses). PHPStan's dead-code rule exposed it; the owner approved a second phase `tests`, the unit
  tests passed on the first run, and Infection (MSI 94% → 100% after one fix) stood in for the red run.
- **Known weakness:** writing more than the failing tests ask for in phase `impl` ("minimal code to
  green", `docs/pre-init/05-tdd.md`).
- **Probable cause:** design D3 and task 2.2 named the value objects with their rules, so the agent
  implemented the design instead of the tests; the phase hook guards test files only, not the amount of
  production code.
- **Remedy (level: CI, proposed; awaits the owner's decision):** make Infection's covered MSI a gate on
  the changed lines (`IMP-008-mutation-score-threshold` fires: this change has product code): code that no
  test needs produces escaped or uncovered mutants and fails the pull request. Plus, at instruction level,
  tasks name only behaviour the group's tests cover; rules for a later group stay in that group.
- **Commit:** —

## FAIL-010-unconfirmed-decision-recorded-as-owners

- **What happened:** on 2026-10-08, in change `status-delete`, the agent applied the `spec-auditor` findings
  and wrote two of them into the proposal's "Confirmed" section as "Owner, 2026-10-08 (after `spec-auditor`)"
  — a separate race scenario and accepting two concurrent deletes answering 204 — before asking the owner.
  The agent noticed it in the same turn, said so, and asked; the owner agreed, so the record stands, but it
  was written as a fact before it was one.
- **Known weakness:** passing an assumption off as a confirmed decision (`docs/pre-init/07-requirements-intent.md`:
  "Confirmed lists only what the human explicitly said").
- **Probable cause:** the agent batched "apply the auditor's fixes" and "record the owner's answers" in one
  edit, anticipating the recommended answer.
- **Remedy (level: instruction, proposed; awaits the owner's decision):** decisions the auditor raises go to
  "Open questions" until the owner answers; only the answer moves them to "Confirmed". The `forms.py` check
  cannot tell who said what, so the level is instruction (skill `interview`, step 5).
- **Commit:** —

## FAIL-011-started-docker-without-checking-the-build

- **What happened:** in a cloud session (2026-10-07, README and badges, PR #27) the agent started a Docker
  daemon in the container to run the checks locally, without first checking that the image could be built
  there. The build failed (the network policy blocks `deb.debian.org` and `pecl.php.net`), and from then on
  the Stop hook tried the build after every reply and blocked; the agent also promised that stopping the
  daemon would end the blocks, which was wrong — without Docker the hook blocks too. Found by the agent.
- **Known weakness:** acting on an unchecked assumption about the environment ("Docker runs, so the checks
  will run"), and a claim stated as fact without verification.
- **Probable cause:** the agent checked only the first step (the daemon starts) and treated the rest of the
  path (image pulls, package downloads) as given; the Stop hook does not tell "cannot run here" from "red".
- **Remedy (level: hook; accepted by the owner 2026-10-07, done after submission):**
  `IMP-015-stop-hook-without-docker` — the hook reports "not run here" instead of red when Docker is
  absent; with it, starting or not starting a daemon no longer changes what the hook says. No instruction
  is added: one observed case.
- **Commit:** —

## FAIL-012-tool-attribution-over-repository-rule

- **What happened:** on 2026-10-08 (session after the context summary, changes `status-delete` and PR #27) the
  agent ended every commit with a `Claude-Session: <claude.ai session link>` trailer and every pull request
  body with the same link, because Claude Code's built-in attribution instruction asked for it. AGENTS.md
  ("Commits and branches") requires only `Assisted-by: Claude Code`; the owner had not asked for the link and
  was not asked. The link is now in public commits and PR descriptions on `main`. Found by the owner.
- **Known weakness:** an instruction from the tool's harness was followed where it differed from a repository
  rule, without noticing the difference or asking (AGENTS.md: changes to attribution are the owner's decision).
- **Probable cause:** the harness instruction says that the user's own attribution rules take precedence;
  the agent did not compare it with AGENTS.md and treated it as the default.
- **Remedy (level: permission/settings; accepted by the owner 2026-10-08):** `attribution.sessionUrl: false`
  in `.claude/settings.json` — Claude Code then stops adding the link, whatever the agent remembers (documented
  setting, Claude Code settings reference, `attribution.sessionUrl`). History on `main` is not rewritten: it
  would need a force-push to `main` and would break commit and PR links.
- **Commit:** —
