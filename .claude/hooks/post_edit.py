#!/usr/bin/env python3
"""PostToolUse on file edits: format, lint, remind about swallowed errors and defaults.

Sources: docs/pre-init/04-definition-of-done.md, 26-code-quality.md §1, §4.
PostToolUse cannot block (the edit already happened); exit 2 passes stderr back to the agent.
Formatting and linting run via Makefile targets `fix-file` / `lint-file` once they exist.
"""

import json
import re
import subprocess
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from _common import make_has_target, project_dir, read_event, relative  # noqa: E402

SUSPICIOUS = re.compile(r"\bcatch\s*\(|\?\?\s|\?:\s")
REMINDER = ("New catch or default value in {path}: justify it or remove it. Fail fast; a default is set "
            "once at the boundary where data enters (RUL-CODE-fail-fast, docs/pre-init/26-code-quality.md).")


def edited_text(tool_input: dict) -> str:
    parts = [tool_input.get("content", ""), tool_input.get("new_string", "")]
    parts += [e.get("new_string", "") for e in tool_input.get("edits", []) or []]
    return "\n".join(p for p in parts if p)


def main() -> None:
    event = read_event()
    tool_input = event.get("tool_input") or {}
    path = tool_input.get("file_path", "")
    if not path.endswith(".php"):
        return
    rel = relative(path)

    problems = []
    for target in ("fix-file", "lint-file"):
        if make_has_target(target):
            r = subprocess.run(["make", "-C", str(project_dir()), target, f"FILE={rel}"],
                               capture_output=True, text=True)
            if r.returncode != 0 and target == "lint-file":
                problems.append("\n".join((r.stdout + r.stderr).strip().splitlines()[-30:]))

    reminder = REMINDER.format(path=rel) if SUSPICIOUS.search(edited_text(tool_input)) else ""

    if problems:
        print("\n".join(problems + ([reminder] if reminder else [])), file=sys.stderr)
        sys.exit(2)
    if reminder:
        print(json.dumps({"hookSpecificOutput": {"hookEventName": "PostToolUse", "additionalContext": reminder}}))


if __name__ == "__main__":
    try:
        main()
    except Exception as exc:  # non-blocking event: report loudly, do not pretend success
        print(f"post_edit hook error: {exc!r}", file=sys.stderr)
        sys.exit(1)
