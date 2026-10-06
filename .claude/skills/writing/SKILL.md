---
name: writing
description: Write or revise any document so a reader without the conversation understands it - writing rules plus a reader test for significant documents. Use when writing or editing markdown docs, ADRs, proposals, README, architecture descriptions, decision records.
when_to_use: creating or editing *.md; "write up", "document", "describe"; before presenting a significant document as done.
---

# Writing

Rules: `.claude/rules/writing.md` (the single source). Decision: `docs/pre-init/19-agent-writing.md`.

## Steps

1. State to yourself: who reads, what they already know, what they must understand or do after reading.
2. Write: the point first; self-contained; why next to what; detail proportional to importance; examples
   for abstractions; one fact in one place with links; language by the primary reader.
3. Cut: delete every sentence whose removal loses nothing; delete what the code already says.
4. **Reader test** for significant documents (decision records, ADR, README, architecture, proposals):
   follow stage 3 "Reader Testing" of `.claude/skills/doc-coauthoring/SKILL.md` using the
   `reader-tester` subagent: predict 5–10 reader questions, let the subagent answer them from the
   document alone, then ask it for ambiguities, assumed knowledge and contradictions. Fix and repeat
   until the answers are right.
5. Report in the review brief that the reader test passed and what was fixed.

For a large document written from scratch (README, architecture description) use `/doc-coauthoring`.
