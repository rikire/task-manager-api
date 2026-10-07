#!/usr/bin/env python3
"""PreToolUse: shell writes to files under an `ask` rule must not skip the owner's confirmation.

Source: docs/registers/failures.md FAIL-004 (twice the agent changed Makefile / deptrac.yaml with a script
run through Bash, so the `ask` rule for Edit never fired). Protected paths are read from the `Edit(./…)`
entries of `permissions.ask` in .claude/settings.json, so the two lists cannot drift apart.
Bash commands are checked by known write forms only; uncovered bypasses are listed in BYPASSES.md.
"""

import json
import re
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from _common import block, project_dir, read_event, run_fail_closed  # noqa: E402

EDIT_RULE = re.compile(r"^Edit\(\./(.+)\)$")
# Write forms other than redirection; a redirection counts only when it points at the protected file,
# so `2>&1` or `> /dev/null` next to a read of a protected file is not a write.
WRITE_FORMS = re.compile(
    r"(\btee\b|\bsed\s+-i|\bperl\s+-[a-z]*i|\bcp\b|\bmv\b|\brm\b|\btruncate\b|\bdd\b|"
    r"open\([^)]*['\"][wa]|\.write_text\(|\.write_bytes\(|\bgit\s+(checkout|restore)\b)"
)
# Messages of commits and pull requests may name files and contain words like `cp`; they write nothing.
MESSAGE_COMMANDS = re.compile(r"^\s*(git\s+commit\b|gh\s+pr\s+(create|edit|comment)\b)")


def protected_paths() -> list[str]:
    """Regex fragments for the `Edit(./…)` paths of `permissions.ask`."""
    settings = json.loads((project_dir() / ".claude" / "settings.json").read_text())
    paths = []
    for rule in settings.get("permissions", {}).get("ask", []):
        match = EDIT_RULE.match(rule)
        if match:
            path = match.group(1)
            paths.append(re.escape(path[:-2]) if path.endswith("/**") else re.escape(path) + r"(?![\w.-])")
    return paths


def main() -> None:
    event = read_event()
    if event.get("tool_name") != "Bash":
        return
    command = (event.get("tool_input") or {}).get("command", "")
    if MESSAGE_COMMANDS.match(command):
        return
    has_write_form = WRITE_FORMS.search(command) is not None
    for path in protected_paths():
        redirected = re.search(r">>?\s*['\"]?(\./)?" + path, command)
        mentioned = re.search(r"(^|[\s'\"=/(:])(\./)?" + path, command)
        if redirected or (has_write_form and mentioned):
            block(
                "This command looks like it writes to a file under an `ask` rule in .claude/settings.json. "
                "Change it with Edit or Write so the owner is asked (docs/registers/failures.md FAIL-004)."
            )


if __name__ == "__main__":
    run_fail_closed(main)
