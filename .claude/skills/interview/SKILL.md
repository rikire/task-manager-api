---
name: interview
description: Clarify the human's intent before writing an OpenSpec proposal or spec. Use at the start of every new change, when a request introduces new behaviour, or when a proposal has partial or missing coverage categories.
when_to_use: new feature or behaviour request; "add X"; "make it work like Y"; before /opsx:new or /opsx:continue writes the proposal; when Assumptions or Open questions are non-empty.
---

# Interview

Goal: the human's intent is captured in the proposal; nothing the human did not confirm is presented as
fact. Source: `docs/pre-init/07-requirements-intent.md`.

## Steps

1. **Read first.** Read the request, existing specs (`openspec/specs/`), `docs/constraints.md`, related
   code and ADRs. What the repository answers is not a question.
2. **Fill the coverage table** for the change: categories `Scope`, `Data`, `Edge cases and failures`,
   `Constraints`, `Terminology`, `Non-functional`, `Done criteria`; status `clear` / `partial` /
   `missing` with a one-line rationale. You decide the status by evidence, not by feeling: a category is
   `clear` only if the human said it or an artifact states it. `Constraints` cites the `CON-…` IDs from
   `docs/constraints.md` that bind the change; a new external limit is proposed as a `CON-` entry and
   stays an assumption until the human confirms it (`docs/pre-init/36-constraints-and-ids.md`).
3. **Build the corner-case matrix:** each input × dimension (emptiness, size and boundaries, type and
   format, structure, state, trust) → expected behaviour, or a question.
4. **Ask** about every `partial` / `missing` row and every unanswered matrix cell:
   - at most 5 questions per round, numbered;
   - each with 2–4 options and your recommendation with a one-line reason;
   - the hard parts first; no questions the repository answers;
   - continue rounds until no category is `missing`. If everything is `clear`, ask nothing.
5. **Record** answers in the proposal: `Confirmed` — only what the human said; `Assumptions` — what you
   rely on without confirmation (the human resolves each); `Open questions` — what is still open.
6. **Hand off** to the `spec-auditor` subagent with the proposal and specs (it does not see this
   conversation); put its findings to the human, do not auto-apply them.

## Do not

- Do not decide behaviour inside a test or code because the spec is silent.
- Do not write tests or code while `Assumptions` or `Open questions` have unresolved items.
