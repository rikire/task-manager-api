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
- **Commit:** (filled in when the remedy is committed).

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
- **Commit:** (filled in when the remedy is committed).
