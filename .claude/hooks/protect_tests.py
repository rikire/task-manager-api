#!/usr/bin/env python3
"""PreToolUse: lock test files in TDD phases impl/refactor; guard the phase file.

Sources: docs/pre-init/05-tdd.md, 16-guardrails-harness.md §2.
Built-in edit tools are blocked reliably. Bash commands are checked by known write forms only;
uncovered bypasses are listed in .claude/hooks/BYPASSES.md.
"""

import re
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from _common import LOCKED_PHASES, block, is_test_path, read_event, read_phase, run_fail_closed  # noqa: E402

EDIT_TOOLS = {"Edit", "Write", "MultiEdit", "NotebookEdit"}
WRITE_FORMS = re.compile(
    r"(\btee\b|\bsed\s+-i|\bperl\s+-[a-z]*i|\bcp\b|\bmv\b|\brm\b|\btruncate\b|\bdd\b|"
    r"\bpython3?\s+-c|\bphp\s+-r|\bnode\s+-e|\bgit\s+(checkout|restore)\b)"
)
TEST_REF = re.compile(r"(^|[\s'\"=/])(\./)?tests/")
REDIRECT_TO_TESTS = re.compile(r">>?\s*['\"]?(\./)?tests/")
PHASE_FILE = ".agent-state/phase"
PHASE_SCRIPT_ONLY = re.compile(r"^\s*(\./)?scripts/phase(\s+\w+)?\s*$")


def main() -> None:
    event = read_event()
    tool = event.get("tool_name", "")
    tool_input = event.get("tool_input") or {}
    phase = read_phase()

    if tool in EDIT_TOOLS:
        path = tool_input.get("file_path") or tool_input.get("notebook_path") or ""
        if phase in LOCKED_PHASES and path and is_test_path(path):
            block(
                f"Tests are locked in phase '{phase}' (docs/pre-init/05-tdd.md). "
                "If a test looks wrong, stop and ask the human; to change tests run "
                "`scripts/phase tests` (needs the human's confirmation)."
            )
        return

    if tool == "Bash":
        command = tool_input.get("command", "")
        if PHASE_FILE in command and not PHASE_SCRIPT_ONLY.match(command):
            block("The phase file is changed only via `scripts/phase` (docs/pre-init/16-guardrails-harness.md).")
        writes_tests = REDIRECT_TO_TESTS.search(command) or (TEST_REF.search(command) and WRITE_FORMS.search(command))
        if phase in LOCKED_PHASES and writes_tests:
            block(
                f"This command looks like it writes to tests/ in phase '{phase}'. Tests are locked "
                "(docs/pre-init/05-tdd.md); stop and ask the human if a test must change."
            )


if __name__ == "__main__":
    run_fail_closed(main)
