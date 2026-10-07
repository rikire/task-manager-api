#!/usr/bin/env python3
"""Roadmap statuses generated from OpenSpec changes (decision record 24 §3–4).

    roadmap.py           rewrite the status column of the changes table in docs/roadmap.md
    roadmap.py --check   only verify; never writes

Statuses: `запланировано` (no change folder), `в работе` or `в работе: N/M` (active change; N of M task
boxes in tasks.md checked), `в архиве` (openspec/changes/archive/YYYY-MM-DD-<name>).
--check compares the status word only (the count is informational) and fails when:
- a change folder (active or archived) has no row, a started row has no folder, or names repeat;
- a status word differs from the generated one;
- a change folder exists while a mandatory row above it in the table is not archived (FAIL-005).
Rows whose name cell is not one backtick-quoted identifier are candidates and are left untouched.
A started row without a folder fails both modes and is never rewritten, so regenerating cannot hide a
lost change; archived folders never trip the order gate.
Exit codes: 0 — pass, 1 — violation (one stderr line each), 2 — unknown argument (nothing written).
"""
import re
import sys
from pathlib import Path

ROADMAP = Path("docs/roadmap.md")
CHANGES = Path("openspec/changes")
HEADER = "| # | Изменение | Что даёт | Приоритет | Статус |"
NAME = re.compile(r"^`([a-z0-9-]+)`$")
ARCHIVED = re.compile(r"^\d{4}-\d{2}-\d{2}-([a-z0-9-]+)$")
BOX = re.compile(r"^\s*- \[( |x|X)\]")
MANDATORY = "обязательно"
PLANNED, ACTIVE, DONE = "запланировано", "в работе", "в архиве"


def cells(line: str) -> list[str]:
    return [c.strip() for c in line.strip().strip("|").split("|")]


def task_count(tasks: Path) -> str:
    if not tasks.exists():
        return ACTIVE
    done = total = 0
    in_fence = False
    for line in tasks.read_text().splitlines():
        if line.lstrip().startswith("```"):
            in_fence = not in_fence
            continue
        match = None if in_fence else BOX.match(line)
        if match:
            total += 1
            done += match.group(1) != " "
    return f"{ACTIVE}: {done}/{total}" if total else ACTIVE


def folders(errors: list[str]) -> dict[str, str]:
    """Change name -> generated status."""
    found: dict[str, list[str]] = {}
    for path in sorted(CHANGES.iterdir()) if CHANGES.exists() else []:
        if path.is_dir() and path.name != "archive":
            found.setdefault(path.name, []).append(task_count(path / "tasks.md"))
    archive = CHANGES / "archive"
    for path in sorted(archive.iterdir()) if archive.exists() else []:
        match = ARCHIVED.match(path.name)
        if path.is_dir() and match:
            found.setdefault(match.group(1), []).append(DONE)
    for name, statuses in found.items():
        if len(statuses) > 1:
            errors.append(f"{name}: more than one change folder (active and archived, or archived twice)")
    return {name: statuses[0] for name, statuses in found.items()}


def word(status: str) -> str:
    return status.split(":", 1)[0].strip()


def rewrite_status(line: str, status: str) -> str:
    """Replace only the last cell, so the rest of the row keeps its formatting and line ending."""
    body = line.rstrip("\r\n")
    ending = line[len(body):]
    content = body.rstrip()
    if content.endswith("|"):
        content = content[:-1]
    return f"{content.rsplit('|', 1)[0]}| {status} |{ending}"


def main(argv: list[str]) -> int:
    unknown = [arg for arg in argv[1:] if arg != "--check"]
    if unknown:
        print(f"unknown argument: {' '.join(unknown)}; usage: roadmap.py [--check]", file=sys.stderr)
        return 2
    check = "--check" in argv[1:]
    lines = ROADMAP.read_text().splitlines(keepends=True)
    start = next((i for i, line in enumerate(lines) if line.strip() == HEADER), None)
    if start is None:
        print(f"{ROADMAP}: changes table not found; expected header: {HEADER}", file=sys.stderr)
        return 1

    errors: list[str] = []  # structural: wrong in any mode
    stale: list[str] = []  # status words the generator rewrites; fail only --check
    generated = folders(errors)
    seen: set[str] = set()
    open_mandatory: list[str] = []  # names of mandatory rows above, not archived
    for i in range(start + 2, len(lines)):
        if not lines[i].lstrip().startswith("|"):
            break
        row = cells(lines[i])
        match = NAME.match(row[1]) if len(row) == 5 else None
        if not match:
            continue
        name, priority, status = match.group(1), row[3], row[4]
        if name in seen:
            errors.append(f"{name}: more than one roadmap row")
        seen.add(name)
        expected = generated.get(name, PLANNED)
        if name not in generated and word(status) in (ACTIVE, DONE):
            errors.append(f"{name}: row says '{status}', but there is no change folder")
            continue
        if word(status) != word(expected):
            stale.append(f"{name}: status '{status}', expected '{expected}' (run scripts/roadmap.py)")
        if expected not in (PLANNED, DONE) and open_mandatory:
            errors.append(f"{name}: change started while earlier mandatory rows are not archived: "
                          + ", ".join(open_mandatory) + " (FAIL-005)")
        if priority == MANDATORY and expected != DONE:
            open_mandatory.append(name)
        if not check and status != expected:
            lines[i] = rewrite_status(lines[i], expected)

    for name in sorted(set(generated) - seen):
        errors.append(f"{name}: change folder without a roadmap row")

    if check:
        errors += stale
    else:
        ROADMAP.write_text("".join(lines))
    for error in errors:
        print(error, file=sys.stderr)
    return 1 if errors else 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
