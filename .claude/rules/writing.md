---
paths:
  - "**/*.md"
---

# Writing rules

Source of these rules: `docs/pre-init/19-agent-writing.md`. Hooks and AGENTS.md carry excerpts.

1. **Reader first:** who reads, what they already know, what they must understand or do afterwards.
2. **The point first:** the first 1–2 sentences say what this is and why.
3. **Self-contained:** understandable without the conversation it came from; terms and abbreviations
   explained on first use or linked; a link instead of "as discussed".
4. **Why next to what;** numbers and claims carry a source.
5. **Detail proportional to importance:** decisions, reasons, risks in detail; obvious and
   code-derivable things not at all.
6. **An example where the text is abstract.**
7. **Concrete:** no "etc.", "various", "it is important to note".
8. **Form follows content:** table for comparison, list for enumeration, prose for reasoning.
9. **One fact, one place;** link elsewhere.

Language: by the primary reader (`docs/pre-init/23-documentation.md`): agent-facing files in English,
human-facing documents in Russian.

Significant documents (decision records, ADR, README, architecture, proposals) get a reader test by the
`reader-tester` subagent before they are presented as done.
