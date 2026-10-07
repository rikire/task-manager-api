#!/usr/bin/env python3
"""Checks for the git hooks that no ready-made tool covers.

    git_checks.py pre-commit           staged changes
    git_checks.py commit-msg <file>    the commit message being written

Rules and their sources:
- no `DEBUG:` marker in added lines of code and tests (decision record 10, 30);
- `TODO`/`FIXME`/`HACK` only with an existing `DEBT-`/`IMP-` ID from docs/registers/debt.md (10);
- agent instructions are committed separately from everything else (21);
- soft limit: warn when product code in the commit exceeds 400 lines (18);
- skill frontmatter: `name` equal to the folder, non-empty `description` (20);
- `Refs:` well-formed; required on `change/*` branches when code outside tests and docs changes (09).
Exit codes: 0 — pass (warnings go to stderr), 1 — violation.
"""
import re
import subprocess
import sys
from pathlib import Path

INSTRUCTION_PATHS = ("AGENTS.md", "CLAUDE.md", ".claude/", ".agents/", "openspec/config.yaml")
CODE_PREFIXES = ("src/", "config/", "migrations/", "public/", "bin/", "docker/")
CODE_FILES = ("Dockerfile", "compose.yaml", "compose.dev.yaml", "composer.json")
MARKER_PREFIXES = CODE_PREFIXES + ("tests/",)
PRODUCT_PREFIXES = ("src/", "config/", "migrations/")
DIFF_LIMIT = 400
REGISTER = Path("docs/registers/debt.md")

DEBUG_RE = re.compile(r"\bDEBUG:")
TODO_RE = re.compile(r"\b(TODO|FIXME|HACK)\b(\(([^)]*)\))?")
REGISTER_ID_RE = re.compile(r"^(DEBT|IMP)-\d{3}-[a-z0-9-]+$")
REFS_ID_RE = re.compile(r"^REQ-[A-Z][A-Z0-9]*-[a-z0-9-]+(\.[a-z0-9-]+)?$")


def git(*args: str) -> str:
    return subprocess.run(["git", *args], check=True, capture_output=True, text=True).stdout


def staged_files() -> list[str]:
    return [f for f in git("diff", "--cached", "--name-only", "--diff-filter=ACMR").splitlines() if f]


def added_lines(path: str) -> list[str]:
    diff = git("diff", "--cached", "--unified=0", "--no-color", "--", path)
    return [line[1:] for line in diff.splitlines() if line.startswith("+") and not line.startswith("+++")]


def is_instruction(path: str) -> bool:
    return any(path == p or path.startswith(p) for p in INSTRUCTION_PATHS)


def check_markers(files: list[str], register_ids: set[str]) -> list[str]:
    problems = []
    for path in files:
        if not path.startswith(MARKER_PREFIXES):
            continue
        for line in added_lines(path):
            if DEBUG_RE.search(line):
                problems.append(f"{path}: `DEBUG:` marker must not be committed: {line.strip()}")
            for match in TODO_RE.finditer(line):
                ref = match.group(3)
                if not ref or not REGISTER_ID_RE.match(ref):
                    problems.append(f"{path}: {match.group(1)} needs a register ID, e.g. "
                                    f"TODO(DEBT-012-slug): {line.strip()}")
                elif ref not in register_ids:
                    problems.append(f"{path}: {ref} is not in {REGISTER}")
    return problems


def check_separation(files: list[str]) -> list[str]:
    instructions = [f for f in files if is_instruction(f)]
    others = [f for f in files if not is_instruction(f)]
    if instructions and others:
        return ["agent instructions must go in a separate commit (decision record 21): "
                f"{', '.join(instructions)} together with {', '.join(others)}"]
    return []


def check_skills(files: list[str]) -> list[str]:
    problems = []
    for path in files:
        parts = Path(path).parts
        if len(parts) != 4 or parts[:2] != (".claude", "skills") or parts[3] != "SKILL.md":
            continue
        text = Path(path).read_text()
        match = re.match(r"^---\n(.*?)\n---\n", text, re.S)
        fields = dict(re.findall(r"^([a-z_]+):\s*(.*)$", match.group(1), re.M)) if match else {}
        if fields.get("name") != parts[2]:
            problems.append(f"{path}: frontmatter `name` must be `{parts[2]}`")
        if not fields.get("description", "").strip():
            problems.append(f"{path}: frontmatter needs a non-empty `description`")
    return problems


def diff_warning(files: list[str]) -> str:
    size = sum(len(added_lines(f)) for f in files if f.startswith(PRODUCT_PREFIXES))
    if size > DIFF_LIMIT:
        return (f"warning: {size} added lines of product code (soft limit {DIFF_LIMIT}, decision record 18); "
                "consider splitting the change")
    return ""


def pre_commit() -> int:
    files = staged_files()
    register_ids = set(re.findall(r"^## ((?:DEBT|IMP)-\d{3}-[a-z0-9-]+)", REGISTER.read_text(), re.M)) \
        if REGISTER.exists() else set()
    problems = check_markers(files, register_ids) + check_separation(files) + check_skills(files)
    warning = diff_warning(files)
    if warning:
        print(warning, file=sys.stderr)
    for problem in problems:
        print(problem, file=sys.stderr)
    return 1 if problems else 0


def commit_msg(message_file: str) -> int:
    message = Path(message_file).read_text()
    refs = [r.strip() for line in re.findall(r"^Refs:(.*)$", message, re.M) for r in line.split(",")]
    problems = [f"Refs: `{r}` is not a requirement or scenario ID (REQ-<CAP>-<slug>[.<scenario>])"
                for r in refs if r and not REFS_ID_RE.match(r)]
    branch = git("rev-parse", "--abbrev-ref", "HEAD").strip()
    touches_code = any(f.startswith(CODE_PREFIXES) or f in CODE_FILES for f in staged_files())
    if branch.startswith("change/") and touches_code and not refs:
        problems.append("Refs: required — this commit on a change branch changes code outside tests and "
                        "docs (decision record 09); add `Refs: REQ-…`")
    for problem in problems:
        print(problem, file=sys.stderr)
    return 1 if problems else 0


def main(argv: list[str]) -> int:
    if len(argv) >= 2 and argv[1] == "pre-commit":
        return pre_commit()
    if len(argv) == 3 and argv[1] == "commit-msg":
        return commit_msg(argv[2])
    print(__doc__, file=sys.stderr)
    return 2


if __name__ == "__main__":
    sys.exit(main(sys.argv))
