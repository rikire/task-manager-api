#!/usr/bin/env python3
"""Checks for the git hooks and CI that no ready-made tool covers.

    git_checks.py pre-commit             staged changes
    git_checks.py commit-msg <file>      the commit message being written
    git_checks.py ci <base> <branch>     every commit in <base>..HEAD (local hooks can be skipped)

Rules and their sources:
- no `DEBUG:` marker in added lines of code and tests (decision record 10, 30);
- `TODO`/`FIXME`/`HACK` only with an existing `DEBT-`/`IMP-` ID from docs/registers/debt.md (10);
- agent instructions are committed separately from everything else (21);
- soft limit: warn when product code in the commit exceeds 400 lines (18);
- skill frontmatter: `name` equal to the folder, non-empty `description` (20);
- `Refs:` holds requirement or quality-scenario IDs (`REQ-`, `QAS-`); required on `change/*` branches
  when code outside tests and docs changes (09).
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
REFS_ID_RE = re.compile(r"^(REQ|QAS)-[A-Z][A-Z0-9]*-[a-z0-9-]+(\.[a-z0-9-]+)?$")


def git(*args: str) -> str:
    return subprocess.run(["git", *args], check=True, capture_output=True, text=True).stdout


class Changes:
    """Files and added lines of the staged changes (rev=None) or of one commit."""

    def __init__(self, rev: str | None = None) -> None:
        self.rev = rev

    def files(self) -> list[str]:
        if self.rev is None:
            out = git("diff", "--cached", "--name-only", "--diff-filter=ACMR")
        else:
            out = git("show", "--name-only", "--diff-filter=ACMR", "--format=", self.rev)
        return [f for f in out.splitlines() if f]

    def added_lines(self, path: str) -> list[str]:
        if self.rev is None:
            diff = git("diff", "--cached", "--unified=0", "--no-color", "--", path)
        else:
            diff = git("show", "--unified=0", "--no-color", "--format=", self.rev, "--", path)
        return [line[1:] for line in diff.splitlines() if line.startswith("+") and not line.startswith("+++")]

    def content(self, path: str) -> str:
        return Path(path).read_text() if self.rev is None else git("show", f"{self.rev}:{path}")


def is_instruction(path: str) -> bool:
    return any(path == p or path.startswith(p) for p in INSTRUCTION_PATHS)


def register_ids() -> set[str]:
    if not REGISTER.exists():
        return set()
    return set(re.findall(r"^## ((?:DEBT|IMP)-\d{3}-[a-z0-9-]+)", REGISTER.read_text(), re.M))


def check_markers(changes: Changes, files: list[str], ids: set[str]) -> list[str]:
    problems = []
    for path in files:
        if not path.startswith(MARKER_PREFIXES):
            continue
        for line in changes.added_lines(path):
            if DEBUG_RE.search(line):
                problems.append(f"{path}: `DEBUG:` marker must not be committed: {line.strip()}")
            for match in TODO_RE.finditer(line):
                ref = match.group(3)
                if not ref or not REGISTER_ID_RE.match(ref):
                    problems.append(f"{path}: {match.group(1)} needs a register ID, e.g. "
                                    f"TODO(DEBT-012-slug): {line.strip()}")
                elif ref not in ids:
                    problems.append(f"{path}: {ref} is not in {REGISTER}")
    return problems


def check_separation(files: list[str]) -> list[str]:
    instructions = [f for f in files if is_instruction(f)]
    others = [f for f in files if not is_instruction(f)]
    if instructions and others:
        return ["agent instructions must go in a separate commit (decision record 21): "
                f"{', '.join(instructions)} together with {', '.join(others)}"]
    return []


def check_skills(changes: Changes, files: list[str]) -> list[str]:
    problems = []
    for path in files:
        parts = Path(path).parts
        if len(parts) != 4 or parts[:2] != (".claude", "skills") or parts[3] != "SKILL.md":
            continue
        match = re.match(r"^---\n(.*?)\n---\n", changes.content(path), re.S)
        fields = dict(re.findall(r"^([a-z_]+):\s*(.*)$", match.group(1), re.M)) if match else {}
        if fields.get("name") != parts[2]:
            problems.append(f"{path}: frontmatter `name` must be `{parts[2]}`")
        if not fields.get("description", "").strip():
            problems.append(f"{path}: frontmatter needs a non-empty `description`")
    return problems


def check_refs(message: str, branch: str, files: list[str]) -> list[str]:
    refs = [r.strip() for line in re.findall(r"^Refs:(.*)$", message, re.M) for r in line.split(",")]
    problems = [f"Refs: `{r}` is not a requirement or scenario ID (REQ-<CAP>-<slug> or QAS-<ATTR>-<slug>, optional .<scenario>)"
                for r in refs if r and not REFS_ID_RE.match(r)]
    touches_code = any(f.startswith(CODE_PREFIXES) or f in CODE_FILES for f in files)
    if branch.startswith("change/") and touches_code and not refs:
        problems.append("Refs: required — this commit on a change branch changes code outside tests and "
                        "docs (decision record 09); add `Refs: REQ-…` or `QAS-…`")
    return problems


def diff_warning(changes: Changes, files: list[str]) -> str:
    size = sum(len(changes.added_lines(f)) for f in files if f.startswith(PRODUCT_PREFIXES))
    if size > DIFF_LIMIT:
        return (f"warning: {size} added lines of product code (soft limit {DIFF_LIMIT}, decision record 18); "
                "consider splitting the change")
    return ""


def report(problems: list[str], warning: str = "") -> int:
    if warning:
        print(warning, file=sys.stderr)
    for problem in problems:
        print(problem, file=sys.stderr)
    return 1 if problems else 0


def pre_commit() -> int:
    changes = Changes()
    files = changes.files()
    problems = check_markers(changes, files, register_ids()) + check_separation(files) \
        + check_skills(changes, files)
    return report(problems, diff_warning(changes, files))


def commit_msg(message_file: str) -> int:
    branch = git("rev-parse", "--abbrev-ref", "HEAD").strip()
    return report(check_refs(Path(message_file).read_text(), branch, Changes().files()))


def ci(base: str, branch: str) -> int:
    problems = []
    ids = register_ids()
    for rev in git("rev-list", "--reverse", f"{base}..HEAD").split():
        changes = Changes(rev)
        files = changes.files()
        subject = git("log", "-1", "--format=%s", rev).strip()
        found = check_markers(changes, files, ids) + check_separation(files) + check_skills(changes, files) \
            + check_refs(git("log", "-1", "--format=%B", rev), branch, files)
        problems += [f"{rev[:7]} {subject}: {p}" for p in found]
    return report(problems)


def main(argv: list[str]) -> int:
    if len(argv) == 2 and argv[1] == "pre-commit":
        return pre_commit()
    if len(argv) == 3 and argv[1] == "commit-msg":
        return commit_msg(argv[2])
    if len(argv) == 4 and argv[1] == "ci":
        return ci(argv[2], argv[3])
    print(__doc__, file=sys.stderr)
    return 2


if __name__ == "__main__":
    sys.exit(main(sys.argv))
