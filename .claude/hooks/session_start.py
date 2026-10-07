#!/usr/bin/env python3
"""SessionStart: health check of the harness (docs/pre-init/16-guardrails-harness.md).

Reports problems as the first line of the agent's context so a silently disabled guard is noticed.
"""

import json
import os
import shutil
import subprocess
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from _common import project_dir, read_event, read_phase  # noqa: E402

HOOKS = ("protect_tests.py", "stop_check.py", "post_edit.py", "session_start.py")


def checks() -> list[str]:
    root = project_dir()
    problems = []
    for name in HOOKS:
        if not os.access(root / ".claude" / "hooks" / name, os.X_OK):
            problems.append(f"hook {name} missing or not executable")
    if not os.access(root / "scripts" / "phase", os.X_OK):
        problems.append("scripts/phase missing or not executable")
    # bwrap/socat are not checked while the sandbox is off (DEBT-001-sandbox-disabled).
    if shutil.which("openspec") is None:
        problems.append("openspec not on PATH: activate mise shims in ~/.bashrc "
                        "(eval \"$(mise activate bash --shims)\")")
    if (root / ".githooks").is_dir():
        hooks_path = subprocess.run(["git", "-C", str(root), "config", "core.hooksPath"],
                                    capture_output=True, text=True).stdout.strip()
        if hooks_path != ".githooks":
            problems.append("git hooks not installed: run `make setup`")
    return problems


def main() -> None:
    read_event()
    problems = checks()
    lines = []
    if problems:
        lines.append("HARNESS WARNING: " + "; ".join(problems) + ". Tell the human before working.")
    lines.append(f"TDD phase: {read_phase()}. Read docs/roadmap.md, tasks.md of the active change, "
                 ".agent-state/notes.md and git status before continuing; the plan is the roadmap and "
                 "tasks.md, never the notes (AGENTS.md, Session state; FAIL-005).")
    print(json.dumps({"hookSpecificOutput": {"hookEventName": "SessionStart",
                                             "additionalContext": "\n".join(lines)}}))


if __name__ == "__main__":
    try:
        main()
    except Exception as exc:
        print(json.dumps({"hookSpecificOutput": {"hookEventName": "SessionStart",
                                                 "additionalContext": f"HARNESS WARNING: health check failed: {exc!r}"}}))
