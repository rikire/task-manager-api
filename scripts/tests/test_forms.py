"""Tests for scripts/forms.py --check: form of OpenSpec changes, review briefs and ADRs.

Rules under test: change finish-init, design.md D4 (decision records 07 §2–4, 12 §2 and §6; FAIL-006).
Each test starts from a project that passes and breaks one rule.
"""
import subprocess
import sys
import tempfile
import textwrap
import unittest
from pathlib import Path

SCRIPT = Path(__file__).resolve().parents[1] / "forms.py"
SECTIONS = ["Why", "What Changes", "Out of scope", "Capabilities", "Impact", "Roadmap", "Coverage",
            "Corner cases", "Confirmed", "Assumptions", "Open questions"]
CATEGORIES = ["Scope", "Data", "Edge cases and failures", "Constraints", "Terminology", "Non-functional",
              "Done criteria"]
CONFIG = ('rules:\n  proposal:\n    - "Sections, in this order: ' + "; ".join(SECTIONS)
          + ' (link to the docs/roadmap.md entry)."\n')


def proposal(sections: list[str] | None = None, coverage: dict[str, str] | None = None,
             assumptions: str = "None.", questions: str = "None.") -> str:
    coverage = coverage if coverage is not None else {c: "clear" for c in CATEGORIES}
    body = {
        "Coverage": "| Category | Status | Rationale |\n|---|---|---|\n"
                    + "".join(f"| {c} | {s} | why |\n" for c, s in coverage.items()),
        "Assumptions": assumptions + "\n",
        "Open questions": questions + "\n",
    }
    text = "# Proposal: demo\n\n"
    for name in sections or SECTIONS:
        text += f"## {name}\n\n{body.get(name, 'Text.\n')}\n"
    return text


TASKS = "# Tasks\n\n## 1. Thing\n\n- [x] 1.1 a\n- [x] 1.2 b\n\n## 2. Polish\n\n- [ ] 2.1 c\n"
REVIEW = textwrap.dedent("""\
    # Review: demo

    ## Group 1 — thing, 2026-10-07

    **Simplifications:** none.

    **Debt / improvements:** none.

    **Maturity:** working minimum.
    """)
DESIGN = "# Design\n\n## Decisions\n\n### D1. Something\n\nScope: local.\n\n### D2. Other\n\nADR: ADR-0001-x\n"
SPEC = textwrap.dedent("""\
    ## ADDED Requirements

    ### Requirement: REQ-DEMO-create — create a thing

    #### Scenario: REQ-DEMO-create.ok
    - **WHEN** a
    - **THEN** b

    #### Scenario: REQ-DEMO-create.invalid
    - **WHEN** c
    - **THEN** d
    """)
FIELDS = {"Status": "active, 2026-10-07", "Kind": "architecture", "Decided by": "project owner",
          "Drafted by": "agent"}
ADR_SECTIONS = ["Context and drivers", "Considered options", "Trade-offs", "Decision and rationale",
                "Consequences", "Confirmation", "Retires", "Revisit-when"]


def adr(fields: dict[str, str] | None = None, sections: list[str] | None = None,
        options: str = "1. **A** — a.\n2. **B** — b.\n", drivers: str = "- `QAS-MAINT-layering` — x.\n") -> str:
    text = "# ADR-0001: Something\n\n"
    text += "".join(f"- **{k}:** {v}\n" for k, v in (fields if fields is not None else FIELDS).items())
    body = {"Context and drivers": drivers, "Considered options": options}
    for name in sections or ADR_SECTIONS:
        text += f"\n## {name}\n\n{body.get(name, 'Text.\n')}"
    return text


class Project:
    def __init__(self) -> None:
        self._tmp = tempfile.TemporaryDirectory()
        self.root = Path(self._tmp.name)
        self.write("openspec/config.yaml", CONFIG)
        self.write("openspec/changes/demo/proposal.md", proposal())
        self.write("openspec/changes/demo/tasks.md", TASKS)
        self.write("openspec/changes/demo/review.md", REVIEW)
        self.write("openspec/changes/demo/design.md", DESIGN)
        self.write("openspec/changes/demo/specs/demo/spec.md", SPEC)
        self.write("openspec/changes/archive/.keep", "")
        self.write("openspec/specs/demo/spec.md", SPEC.replace("## ADDED Requirements\n\n", ""))
        self.write("docs/adr/ADR-0001-x.md", adr())

    def close(self) -> None:
        self._tmp.cleanup()

    def write(self, rel: str, content: str) -> None:
        path = self.root / rel
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(content)

    def run(self, *args: str) -> subprocess.CompletedProcess:
        return subprocess.run([sys.executable, str(SCRIPT), *args], cwd=self.root, capture_output=True,
                              text=True)


class FormsCase(unittest.TestCase):
    def setUp(self) -> None:
        self.p = Project()
        self.addCleanup(self.p.close)

    def assertFails(self, *names: str) -> None:
        r = self.p.run("--check")
        self.assertEqual(r.returncode, 1, r.stderr)
        for name in names:
            self.assertIn(name, r.stderr)

    def assertPasses(self) -> None:
        r = self.p.run("--check")
        self.assertEqual(r.returncode, 0, r.stderr)


class BaselineTest(FormsCase):
    def test_conforming_project_passes(self) -> None:
        self.assertPasses()

    def test_archived_changes_are_not_checked(self) -> None:
        self.p.write("openspec/changes/archive/2026-10-06-old/proposal.md", "# Proposal\n\n## Why\n")
        self.assertPasses()

    def test_rejects_unknown_arguments(self) -> None:
        r = self.p.run("--chek")
        self.assertNotEqual(r.returncode, 0)
        self.assertIn("--chek", r.stderr)


class ChangeFormTest(FormsCase):
    def test_proposal_sections_missing_or_out_of_order(self) -> None:
        swapped = SECTIONS.copy()
        swapped[0], swapped[1] = swapped[1], swapped[0]
        for name, sections in (("missing", [s for s in SECTIONS if s != "Roadmap"]), ("swapped", swapped)):
            with self.subTest(name=name):
                self.p.write("openspec/changes/demo/proposal.md", proposal(sections=sections))
                self.assertFails("demo", "proposal.md")

    def test_coverage_needs_every_category_with_a_known_status(self) -> None:
        missing = {c: "clear" for c in CATEGORIES if c != "Terminology"}
        unknown = {c: "clear" for c in CATEGORIES} | {"Data": "done"}
        for name, coverage, needle in (("missing", missing, "Terminology"), ("unknown", unknown, "Data")):
            with self.subTest(name=name):
                self.p.write("openspec/changes/demo/proposal.md", proposal(coverage=coverage))
                self.assertFails("demo", needle)

    def test_ticked_task_requires_resolved_assumptions_and_questions(self) -> None:
        for field in ("assumptions", "questions"):
            with self.subTest(field=field):
                self.p.write("openspec/changes/demo/proposal.md", proposal(**{field: "1. Is X right?"}))
                self.assertFails("demo", "Assumptions" if field == "assumptions" else "Open questions")

    def test_open_items_are_allowed_before_any_task_is_ticked(self) -> None:
        self.p.write("openspec/changes/demo/proposal.md", proposal(assumptions="1. Is X right?"))
        self.p.write("openspec/changes/demo/tasks.md", TASKS.replace("[x]", "[ ]"))
        self.p.write("openspec/changes/demo/review.md", "# Review\n")
        self.assertPasses()

    def test_every_design_decision_is_marked(self) -> None:
        self.p.write("openspec/changes/demo/design.md", DESIGN + "\n### D3. Unmarked\n\nText.\n")
        self.assertFails("demo", "D3")

    def test_last_task_group_is_polish(self) -> None:
        self.p.write("openspec/changes/demo/tasks.md", TASKS.replace("## 2. Polish", "## 2. Cleanup"))
        self.assertFails("demo", "Polish")

    def test_spec_headers_carry_ids(self) -> None:
        cases = {
            "requirement": SPEC.replace("REQ-DEMO-create — create", "Create"),
            "scenario": SPEC.replace("#### Scenario: REQ-DEMO-create.invalid", "#### Scenario: invalid input"),
            "foreign scenario": SPEC.replace("REQ-DEMO-create.invalid", "REQ-DEMO-other.invalid"),
        }
        for name, spec in cases.items():
            for rel in ("openspec/changes/demo/specs/demo/spec.md", "openspec/specs/demo/spec.md"):
                with self.subTest(case=name, file=rel):
                    self.p = Project()
                    self.addCleanup(self.p.close)
                    self.p.write(rel, spec)
                    self.assertFails(rel)


class ReviewBriefTest(FormsCase):
    def test_completed_group_needs_a_review_section(self) -> None:
        self.p.write("openspec/changes/demo/review.md", "# Review: demo\n")
        self.assertFails("demo", "review.md", "1")

    def test_review_section_needs_simplifications_debt_and_maturity(self) -> None:
        for marker in ("Simplifications", "Debt", "Maturity"):
            with self.subTest(marker=marker):
                self.p.write("openspec/changes/demo/review.md", REVIEW.replace(f"**{marker}", "**Other"))
                self.assertFails("demo", marker)

    def test_range_section_covers_several_groups(self) -> None:
        tasks = TASKS.replace("## 2. Polish", "## 2. More\n\n- [x] 2.1 d\n\n## 3. Polish")
        self.p.write("openspec/changes/demo/tasks.md", tasks)
        self.p.write("openspec/changes/demo/review.md", REVIEW.replace("## Group 1 —", "## Groups 1–2 —"))
        self.assertPasses()


class AdrFormTest(FormsCase):
    def test_required_fields_and_values(self) -> None:
        cases = {
            "missing Kind": {k: v for k, v in FIELDS.items() if k != "Kind"},
            "unknown status": FIELDS | {"Status": "accepted, 2026-10-07"},
            "unknown kind": FIELDS | {"Kind": "design"},
            "deprecated without replacement": FIELDS | {"Status": "deprecated, 2026-10-07"},
        }
        for name, fields in cases.items():
            with self.subTest(case=name):
                self.p.write("docs/adr/ADR-0001-x.md", adr(fields=fields))
                self.assertFails("ADR-0001")

    def test_deprecated_with_replacement_passes(self) -> None:
        fields = FIELDS | {"Status": "deprecated, 2026-10-08", "Replaced by": "ADR-0002-y"}
        self.p.write("docs/adr/ADR-0001-x.md", adr(fields=fields))
        self.assertPasses()

    def test_all_sections_present(self) -> None:
        self.p.write("docs/adr/ADR-0001-x.md", adr(sections=[s for s in ADR_SECTIONS if s != "Retires"]))
        self.assertFails("ADR-0001", "Retires")

    def test_at_least_two_options(self) -> None:
        self.p.write("docs/adr/ADR-0001-x.md", adr(options="1. **A** — the only one.\n"))
        self.assertFails("ADR-0001", "options")

    def test_drivers_cite_ids_by_kind(self) -> None:
        cases = [
            ("architecture without ID", FIELDS, "- the team likes it.\n", False),
            ("architecture with CON", FIELDS, "- `CON-PLAN-deadline` — x.\n", True),
            ("process with QAS only", FIELDS | {"Kind": "process"}, "- `QAS-MAINT-layering` — x.\n", False),
            ("process with FAIL", FIELDS | {"Kind": "process"}, "- `FAIL-004-bypassed-ask-via-shell`.\n", True),
            ("process with decision record", FIELDS | {"Kind": "process"},
             "- decision record 16 (`docs/pre-init/16-guardrails-harness.md`).\n", True),
        ]
        for name, fields, drivers, passes in cases:
            with self.subTest(case=name):
                self.p.write("docs/adr/ADR-0001-x.md", adr(fields=fields, drivers=drivers))
                if passes:
                    self.assertPasses()
                else:
                    self.assertFails("ADR-0001", "drivers")


if __name__ == "__main__":
    unittest.main()
