#!/usr/bin/env python3
"""Form checks of OpenSpec changes, review briefs and ADRs (change finish-init, design.md D4).

    forms.py --check

Change form (every active change in openspec/changes/): proposal sections in the order of
openspec/config.yaml; Coverage with every category and a known status; once a task is ticked, no open
Assumptions or Open questions; every design.md decision marked `Scope: local` or `ADR: …`; the last task
group is Polish; requirement and scenario headers in delta and main specs carry IDs.
Review brief (FAIL-006): every task group with all boxes ticked has a `## Group N` / `## Groups N–M`
section in review.md naming Simplifications, Debt and Maturity.
ADR form (docs/adr/ADR-*.md): fields, the eight sections, at least two options, drivers by kind
(decision records 07 §2–4, 12 §2).
Exit codes: 0 — pass, 1 — violation (one stderr line each), 2 — unknown argument.
"""
import re
import sys
from pathlib import Path

CONFIG = Path("openspec/config.yaml")
CHANGES = Path("openspec/changes")
SPECS = Path("openspec/specs")
ADRS = Path("docs/adr")
CATEGORIES = ("Scope", "Data", "Edge cases and failures", "Constraints", "Terminology", "Non-functional",
              "Done criteria")
STATUSES = ("clear", "partial", "missing")
REVIEW_MARKERS = ("Simplifications", "Debt", "Maturity")
ADR_FIELDS = ("Status", "Kind", "Decided by", "Drafted by")
ADR_SECTIONS = ("Context and drivers", "Considered options", "Trade-offs", "Decision and rationale",
                "Consequences", "Confirmation", "Retires", "Revisit-when")
REQUIREMENT_ID = re.compile(r"^(REQ|QAS)-[A-Z]+-[a-z0-9-]+$")
DRIVERS = {"architecture": re.compile(r"\b(REQ|QAS|CON)-[A-Z]+-[a-z0-9-]+"),
           "process": re.compile(r"\b(FAIL|DEBT)-\d{3}-[a-z0-9-]+|decision record \d+|docs/pre-init/\d{2}-")}
BOX = re.compile(r"^\s*- \[( |x|X)\]")


def sections(text: str, level: str = "##") -> dict[str, str]:
    """Heading title -> body, for headings of exactly this level, in document order."""
    parts = re.split(rf"^{level} (.+)$", text, flags=re.M)
    return {parts[i].strip(): parts[i + 1] for i in range(1, len(parts), 2)}


def is_none(body: str) -> bool:
    """'None' (with an optional explanation) and no listed item."""
    return body.lstrip().startswith("None") and not re.search(r"^\s*(\d+\.|[-*])\s", body, re.M)


def proposal_order() -> list[str]:
    match = re.search(r"Sections, in this order: ([^\"]+?)\.?\"", CONFIG.read_text())
    if not match:
        return []
    return [re.sub(r"\s*\(.*\)$", "", name).strip() for name in match.group(1).split(";")]


def check_proposal(name: str, text: str, ticked: bool) -> list[str]:
    problems = []
    found = list(sections(text))
    expected = proposal_order()
    if [s for s in found if s in expected] != expected:
        problems.append(f"{name}/proposal.md: sections must be, in this order: {'; '.join(expected)}")
    body = sections(text)
    rows = dict(re.findall(r"^\|\s*([^|]+?)\s*\|\s*([^|]+?)\s*\|", body.get("Coverage", ""), re.M))
    for category in CATEGORIES:
        if rows.get(category) not in STATUSES:
            problems.append(f"{name}/proposal.md: Coverage row '{category}' needs a status clear / partial / missing")
    if ticked:
        for title in ("Assumptions", "Open questions"):
            if title in body and not is_none(body[title]):
                problems.append(f"{name}/proposal.md: {title} must be resolved (None) once a task is ticked")
    return problems


def check_design(name: str, text: str) -> list[str]:
    decisions = sections(sections(text).get("Decisions", ""), "###")
    return [f"{name}/design.md: decision '{title}' needs 'Scope: local' or 'ADR: ADR-NNNN-…'"
            for title, body in decisions.items() if not re.search(r"^(Scope: local|ADR: ADR-\d{4})", body, re.M)]


def task_groups(text: str) -> list[tuple[int, str, list[bool]]]:
    groups = []
    for title, body in sections(text).items():
        match = re.match(r"(\d+)\.\s*(.*)", title)
        if match:
            boxes = [m.group(1) != " " for line in body.splitlines() if (m := BOX.match(line))]
            groups.append((int(match.group(1)), match.group(2), boxes))
    return groups


def check_tasks(name: str, groups: list[tuple[int, str, list[bool]]]) -> list[str]:
    if groups and "polish" not in groups[-1][1].lower():
        return [f"{name}/tasks.md: the last task group must be Polish"]
    return []


def check_review(name: str, review: str, groups: list[tuple[int, str, list[bool]]]) -> list[str]:
    covered: dict[int, str] = {}
    for title, body in sections(review).items():
        match = re.match(r"Groups? (\d+)(?:\s*[–-]\s*(\d+))?", title)
        if match:
            first = int(match.group(1))
            for number in range(first, int(match.group(2) or first) + 1):
                covered[number] = body
    problems = []
    for number, _, boxes in groups:
        if not boxes or not all(boxes):
            continue
        if number not in covered:
            problems.append(f"{name}/review.md: no section for completed task group {number} (FAIL-006)")
            continue
        for marker in REVIEW_MARKERS:
            if marker not in covered[number]:
                problems.append(f"{name}/review.md: section for group {number} must name {marker} (FAIL-006)")
    return problems


def check_spec(path: Path) -> list[str]:
    problems = []
    requirement = None
    for line in path.read_text().splitlines():
        if line.startswith("### Requirement:"):
            requirement = line.removeprefix("### Requirement:").strip().split(" ")[0]
            if not REQUIREMENT_ID.match(requirement):
                problems.append(f"{path}: requirement '{line}' needs an ID REQ-<CAP>-<slug> or QAS-<ATTR>-<slug>")
                requirement = None
        elif line.startswith("#### Scenario:"):
            scenario = line.removeprefix("#### Scenario:").strip().split(" ")[0]
            if requirement is None or not re.fullmatch(re.escape(requirement) + r"\.[a-z0-9-]+", scenario):
                problems.append(f"{path}: scenario '{line}' needs the ID <requirement ID>.<slug>")
    return problems


def check_change(folder: Path) -> list[str]:
    name = folder.name
    tasks = (folder / "tasks.md").read_text() if (folder / "tasks.md").exists() else ""
    groups = task_groups(tasks)
    ticked = any(any(boxes) for _, _, boxes in groups)
    problems = []
    if (folder / "proposal.md").exists():
        problems += check_proposal(name, (folder / "proposal.md").read_text(), ticked)
    if (folder / "design.md").exists():
        problems += check_design(name, (folder / "design.md").read_text())
    problems += check_tasks(name, groups)
    review = (folder / "review.md").read_text() if (folder / "review.md").exists() else ""
    problems += check_review(name, review, groups)
    for spec in sorted(folder.glob("specs/**/spec.md")):
        problems += check_spec(spec)
    return problems


def check_adr(path: Path) -> list[str]:
    text = path.read_text()
    adr = path.name.removesuffix(".md")
    fields = dict(re.findall(r"^- \*\*([A-Za-z ]+):\*\*\s*(.*)$", text, re.M))
    problems = [f"{adr}: field '{f}' is missing" for f in ADR_FIELDS if f not in fields]
    status = fields.get("Status", "").split(",")[0].strip()
    kind = fields.get("Kind", "").strip()
    if "Status" in fields and status not in ("active", "deprecated"):
        problems.append(f"{adr}: Status must be active or deprecated")
    if status == "deprecated" and "Replaced by" not in fields:
        problems.append(f"{adr}: a deprecated ADR needs 'Replaced by:'")
    if "Kind" in fields and kind not in DRIVERS:
        problems.append(f"{adr}: Kind must be architecture or process")
    body = sections(text)
    problems += [f"{adr}: section '{s}' is missing" for s in ADR_SECTIONS if s not in body]
    if len(re.findall(r"^\d+[a-z]?\.\s", body.get("Considered options", ""), re.M)) < 2:
        problems.append(f"{adr}: Considered options needs at least 2 options")
    if kind in DRIVERS and not DRIVERS[kind].search(body.get("Context and drivers", "")):
        problems.append(f"{adr}: Context and drivers must cite drivers for kind '{kind}'")
    return problems


def main(argv: list[str]) -> int:
    if argv[1:] != ["--check"]:
        print(f"unknown arguments: {' '.join(argv[1:])}; usage: forms.py --check", file=sys.stderr)
        return 2
    problems = []
    for folder in sorted(CHANGES.iterdir()) if CHANGES.exists() else []:
        if folder.is_dir() and folder.name != "archive":
            problems += check_change(folder)
    for spec in sorted(SPECS.glob("**/spec.md")):
        problems += check_spec(spec)
    for path in sorted(ADRS.glob("ADR-*.md")):
        problems += check_adr(path)
    for problem in problems:
        print(problem, file=sys.stderr)
    return 1 if problems else 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
