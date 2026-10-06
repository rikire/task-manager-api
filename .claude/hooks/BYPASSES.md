# Known bypasses of the guards

Every `deny` rule and hook lists how it can be bypassed and what holds then
(docs/pre-init/16-guardrails-harness.md §4, principle 2 of docs/pre-init/01-process-frame.md).

| Guard | Bypass | Status |
|---|---|---|
| `protect_tests.py` — built-in edit tools on `tests/` in `impl` / `refactor` | none known for Edit, Write, MultiEdit, NotebookEdit | blocked; unit-tested |
| `protect_tests.py` — Bash writes into `tests/` | forms not matched by the pattern (e.g. a script file that writes tests, variables holding the path, `cd tests && …`) | not covered: held by the human's review of the diff and by phase `tests` acceptance |
| `protect_tests.py` — direct writes to `.agent-state/phase` | path built from variables; a script that writes the file | not covered: the phase is shown at session start and in the review brief |
| `deny Edit(./.agent-state/phase)` | Bash (see above) | as above |
| `deny Read(./.env.local)` and secrets | `grep -r`, scripts that open files themselves | partly held by the sandbox for Bash subprocesses; dev `.env` has no real secrets (docs/pre-init/15-agent-security.md) |
| `ask` / `deny` Bash patterns (`git commit`, `--no-verify`, `docker compose exec`) | other command forms (`sh -c`, absolute paths, `git -c … commit`) | held by the sandbox where possible; `--no-verify` additionally held by CI (pre-commit checks repeat in CI) |
| Sandbox | `make` and `docker` are in `excludedCommands` (docker needs its socket; make calls docker), so anything a make target runs is unsandboxed | `Makefile` edits are under `ask`; `docker exec` / `docker run` / `docker compose exec|run` are under `ask`; the dev container holds no real secrets |
| Stop hook | releases the turn after 3 consecutive blocks (by design) | the agent must report the red state; CI is the final gate |
| Stop hook | writing the current tree hash into `.agent-state/stop-fingerprint` makes the hook skip the check | not covered: deliberate tampering; CI is the final gate |
| Stop hook | `make stop-check` longer than the hook timeout (240 s): a timeout does not block | keep `stop-check` fast; CI runs the full suite |
| Git hooks | not installed on a fresh clone; `--no-verify` | `make setup` installs them; checks repeat in CI |
| Everything above | `bypassPermissions` mode | disabled via `disableBypassPermissionsMode` |
