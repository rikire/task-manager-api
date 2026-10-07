"""Shared helpers for Claude Code hooks (docs/pre-init/16-guardrails-harness.md).

Blocking hooks are fail-closed: any internal error exits with code 2, because Claude Code
treats every other non-zero code as a non-blocking error and lets the action proceed.
"""

import json
import os
import subprocess
import sys
from pathlib import Path

# Python tests of scripts and hooks follow the same TDD phases (change finish-init, task 2.3).
TEST_DIRS = ("tests/", "scripts/tests/", ".claude/hooks/tests/")
LOCKED_PHASES = ("impl", "refactor")


def project_dir() -> Path:
    return Path(os.environ.get("CLAUDE_PROJECT_DIR") or os.getcwd()).resolve()


def state_dir() -> Path:
    return project_dir() / ".agent-state"


def read_phase() -> str:
    try:
        return (state_dir() / "phase").read_text(encoding="utf-8").strip() or "off"
    except FileNotFoundError:
        return "off"


def read_event() -> dict:
    raw = sys.stdin.read()
    return json.loads(raw) if raw.strip() else {}


def relative(path: str) -> str:
    """Path relative to the project, with forward slashes; absolute input allowed."""
    p = Path(path)
    if p.is_absolute():
        try:
            p = p.resolve().relative_to(project_dir())
        except ValueError:
            return str(p)
    rel = p.as_posix()
    return rel[2:] if rel.startswith("./") else rel


def is_test_path(path: str) -> bool:
    rel = relative(path)
    return any(rel == d.rstrip("/") or rel.startswith(d) for d in TEST_DIRS)


def make_has_target(target: str) -> bool:
    makefile = project_dir() / "Makefile"
    if not makefile.exists():
        return False
    result = subprocess.run(
        ["make", "-C", str(project_dir()), "-n", target],
        capture_output=True, text=True,
    )
    return result.returncode == 0


def block(message: str) -> None:
    print(message, file=sys.stderr)
    sys.exit(2)


def run_fail_closed(main) -> None:
    try:
        main()
    except SystemExit:
        raise
    except Exception as exc:  # fail closed: an internal error must block, not pass
        print(f"hook internal error (blocking by design): {exc!r}", file=sys.stderr)
        sys.exit(2)
