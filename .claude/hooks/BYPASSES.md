# Known bypasses of the guards

Every `deny` rule and hook lists how it can be bypassed and what holds then
(docs/pre-init/16-guardrails-harness.md §4, principle 2 of docs/pre-init/01-process-frame.md).

| Guard | Bypass | Status |
|---|---|---|
| `protect_tests.py` — built-in edit tools on `tests/`, `scripts/tests/`, `.claude/hooks/tests/` in `impl` / `refactor` | none known for Edit, Write, MultiEdit, NotebookEdit | blocked; unit-tested |
| `protect_tests.py` — Bash writes into those directories | forms not matched by the pattern (e.g. a script file that writes tests, variables holding the path, `cd tests && …`) | not covered: held by the human's review of the diff and by phase `tests` acceptance |
| `protect_tests.py` — direct writes to `.agent-state/phase` | path built from variables; a script that writes the file | not covered: the phase is shown at session start and in the review brief |
| `protect_ask_paths.py` — Bash writes to files under `Edit(./…)` `ask` rules (FAIL-004) | write forms not in its pattern (e.g. a script file that writes the path, the path held in a variable, `cd docker && …`, `install`, `ln`); a tool that rewrites files matched by a glob (`markdownlint-cli2 --fix "**/*.md"`, FAIL-007); commands starting with `git commit` / `gh pr create`, `edit`, `comment` are not checked | not covered: held by the human's review of the diff; protected files are mostly instructions and build config, which go in separate commits; `make md-fix` excludes them (FAIL-007) |
| `deny Edit(./.agent-state/phase)` | Bash (see above) | as above |
| `deny Read(./.env.local)` and secrets | `grep -r`, scripts that open files themselves | not covered since the sandbox is off (`DEBT-001-sandbox-disabled`); dev `.env` has no real secrets (docs/pre-init/15-agent-security.md) |
| `ask` / `deny` Bash patterns (`git commit`, `--no-verify`, `docker compose exec`) | other command forms (`sh -c`, absolute paths, `git -c … commit`) | the sandbox is off (`DEBT-001-sandbox-disabled`); `--no-verify` held by CI (pre-commit checks repeat in CI) |
| Sandbox | turned off on this machine (`DEBT-001-sandbox-disabled`): every Bash command runs without isolation or network allowlist | permission rules, hooks and the human's review of the diff; restore before working with untrusted content |
| Stop hook | releases the turn after 3 consecutive blocks (by design) | the agent must report the red state; CI is the final gate |
| Stop hook | writing the current tree hash into `.agent-state/stop-fingerprint` makes the hook skip the check | not covered: deliberate tampering; CI is the final gate |
| Stop hook | `make stop-check` longer than the hook timeout (240 s): a timeout does not block | keep `stop-check` fast; CI runs the full suite |
| Git hooks | not installed on a fresh clone; `--no-verify` | `make setup` installs them; checks repeat in CI, except the `Assisted-by` check below |
| `commit-msg` — `Assisted-by` on agent commits (`CLAUDECODE=1`) | `--no-verify`; `env -u CLAUDECODE git commit` | not covered: CI cannot tell an agent commit from a human one; held by the review of the PR |
| Everything above | `bypassPermissions` mode | disabled via `disableBypassPermissionsMode` |
