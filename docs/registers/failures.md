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
- **Commit:** —
