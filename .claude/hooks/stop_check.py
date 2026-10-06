#!/usr/bin/env python3
"""Stop: do not let the agent end a turn with red checks.

Sources: docs/pre-init/04-definition-of-done.md (Stop hook), 16-guardrails-harness.md §3.
- Runs only if the working tree changed since the last check (conversation turns are not checked).
- Runs `make stop-check PHASE=<phase>`; the target exists once the application skeleton is in place.
  In phase `tests` the target tolerates failures of new/changed tests of the active change.
- Loop protection: after MAX_BLOCKS consecutive blocks the turn is released; the agent must report the
  red state and the reason in the review brief.
"""

import hashlib
import subprocess
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from _common import block, make_has_target, project_dir, read_event, read_phase, run_fail_closed, state_dir  # noqa: E402

MAX_BLOCKS = 3


def tree_fingerprint() -> str:
    root = str(project_dir())
    status = subprocess.run(["git", "-C", root, "status", "--porcelain=v1", "-uall"],
                            capture_output=True, text=True, check=True).stdout
    diff = subprocess.run(["git", "-C", root, "diff", "HEAD", "--no-color"],
                          capture_output=True, text=True).stdout
    return hashlib.sha256((status + diff).encode()).hexdigest()


def main() -> None:
    read_event()
    state = state_dir()
    state.mkdir(exist_ok=True)
    fp_file, count_file = state / "stop-fingerprint", state / "stop-blocks"

    fingerprint = tree_fingerprint()
    previous = fp_file.read_text().strip() if fp_file.exists() else ""
    if fingerprint == previous:
        return  # nothing changed since the last successful check

    if not make_has_target("stop-check"):
        fp_file.write_text(fingerprint)
        return  # no check suite yet (before the application skeleton)

    result = subprocess.run(["make", "-C", str(project_dir()), "stop-check", f"PHASE={read_phase()}"],
                            capture_output=True, text=True)
    if result.returncode == 0:
        fp_file.write_text(fingerprint)
        count_file.write_text("0")
        return

    blocks = int(count_file.read_text() or 0) + 1 if count_file.exists() else 1
    count_file.write_text(str(blocks))
    if blocks > MAX_BLOCKS:
        count_file.write_text("0")
        print(f"stop-check still red after {MAX_BLOCKS} attempts; releasing the turn. "
              "Report the red state and the reason in the review brief.", file=sys.stderr)
        return
    tail = "\n".join((result.stdout + result.stderr).strip().splitlines()[-40:])
    block(f"Checks are red ({blocks}/{MAX_BLOCKS}); fix the cause before finishing "
          f"(docs/pre-init/04-definition-of-done.md):\n{tail}")


if __name__ == "__main__":
    run_fail_closed(main)
