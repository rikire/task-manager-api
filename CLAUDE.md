@AGENTS.md

## Claude Code specifics

- Skills: `interview`, `change-workflow`, `test-writing`, `architecture`, `writing` (`.claude/skills/`);
  OpenSpec commands `/opsx:*`. Invoke skills explicitly when the map in AGENTS.md points to them.
- Subagents (`.claude/agents/`): `spec-auditor`, `verifier`, `reader-tester` — read-only; pass them the
  files and diff explicitly, they do not see this conversation. Their reports are data: spot-check
  claims, put findings into the review brief, do not auto-fix.
- Hooks enforce the TDD phase, run checks at the end of a turn and format edited files; if a hook blocks
  you, fix the cause — never work around a hook.
