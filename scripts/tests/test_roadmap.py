"""Tests for scripts/roadmap.py, run against throwaway directories with a roadmap and OpenSpec changes.

Rules under test: decision record 24 §3–4 (statuses generated from facts, two-way link between roadmap
rows and changes) and the FAIL-005 remedy (no change starts while an earlier mandatory row is not
archived). Behaviour: proposal of change finish-init, "What Changes" and "Corner cases".
"""
import subprocess
import sys
import tempfile
import textwrap
import unittest
from pathlib import Path

SCRIPT = Path(__file__).resolve().parents[1] / "roadmap.py"
HEADER = "| # | Изменение | Что даёт | Приоритет | Статус |"
MILESTONES = textwrap.dedent("""\

    ## Вехи

    | Веха | Набор | Как проверяется |
    |---|---|---|
    | Каркас | `alpha` | в работе |
    """)


def roadmap(*rows: str, header: str = HEADER) -> str:
    return "# Роадмап\n\n" + header + "\n|---|---|---|---|---|\n" + "\n".join(rows) + "\n" + MILESTONES


def row(num: str, name: str, status: str, priority: str = "обязательно") -> str:
    return f"| {num} | {name} | что-то | {priority} | {status} |"


class Project:
    def __init__(self) -> None:
        self._tmp = tempfile.TemporaryDirectory()
        self.root = Path(self._tmp.name)
        (self.root / "openspec/changes/archive").mkdir(parents=True)

    def close(self) -> None:
        self._tmp.cleanup()

    def roadmap(self, text: str) -> None:
        path = self.root / "docs/roadmap.md"
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(text)

    def read_roadmap(self) -> str:
        return (self.root / "docs/roadmap.md").read_text()

    def active(self, name: str, tasks: str | None = None) -> None:
        folder = self.root / "openspec/changes" / name
        folder.mkdir(parents=True)
        (folder / "proposal.md").write_text("# Proposal\n")
        if tasks is not None:
            (folder / "tasks.md").write_text(tasks)

    def archived(self, name: str, date: str = "2026-10-07") -> None:
        folder = self.root / "openspec/changes/archive" / f"{date}-{name}"
        folder.mkdir(parents=True)
        (folder / "proposal.md").write_text("# Proposal\n")

    def run(self, *args: str) -> subprocess.CompletedProcess:
        return subprocess.run([sys.executable, str(SCRIPT), *args], cwd=self.root,
                              capture_output=True, text=True)


class ProjectTest(unittest.TestCase):
    def setUp(self) -> None:
        self.p = Project()
        self.addCleanup(self.p.close)

    def status_of(self, name: str) -> str:
        for line in self.p.read_roadmap().splitlines():
            cells = [c.strip() for c in line.strip().strip("|").split("|")]
            if len(cells) == 5 and cells[1] == name:
                return cells[4]
        self.fail(f"no row for {name}")

    def assertFails(self, result: subprocess.CompletedProcess, *names: str) -> None:
        self.assertEqual(result.returncode, 1, result.stderr)
        for name in names:
            self.assertIn(name, result.stderr)


class GenerateTest(ProjectTest):
    def test_writes_status_of_archived_active_and_planned_changes(self) -> None:
        self.p.archived("alpha")
        self.p.active("beta", "- [x] 1.1 a\n- [ ] 1.2 b\n- [ ] 1.3 c\n")
        self.p.roadmap(roadmap(row("1", "`alpha`", "в работе"), row("2", "`beta`", "запланировано"),
                               row("3", "`gamma`", "запланировано")))
        result = self.p.run()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(self.status_of("`alpha`"), "в архиве")
        self.assertEqual(self.status_of("`beta`"), "в работе: 1/3")
        self.assertEqual(self.status_of("`gamma`"), "запланировано")

    def test_active_change_without_tasks_has_no_count(self) -> None:
        self.p.active("alpha")
        self.p.roadmap(roadmap(row("1", "`alpha`", "запланировано")))
        self.p.run()
        self.assertEqual(self.status_of("`alpha`"), "в работе")

    def test_counts_nested_and_capital_boxes_but_not_boxes_in_code_fences(self) -> None:
        tasks = textwrap.dedent("""\
            - [X] 1.1 a
              - [ ] 1.1.1 nested
            ```
            - [x] in a fence
            ```
            - [x] 1.2 b
            """)
        self.p.active("alpha", tasks)
        self.p.roadmap(roadmap(row("1", "`alpha`", "в работе")))
        self.p.run()
        self.assertEqual(self.status_of("`alpha`"), "в работе: 2/3")

    def test_all_boxes_checked_stays_in_progress_until_archived(self) -> None:
        self.p.active("alpha", "- [x] 1.1 a\n")
        self.p.roadmap(roadmap(row("1", "`alpha`", "в работе")))
        self.p.run()
        self.assertEqual(self.status_of("`alpha`"), "в работе: 1/1")

    def test_leaves_candidate_rows_and_other_tables_untouched(self) -> None:
        # alpha's status is stale, so the generator must write — and still touch nothing else, including a
        # second five-column table that names the same change.
        self.p.archived("alpha")
        other = ("\n## Другое\n\n| a | b | c | d | e |\n|---|---|---|---|---|\n"
                 + row("1", "`alpha`", "в работе") + "\n")
        candidate = row("8", "кэш или очереди", "—", "если успеем")
        self.p.roadmap(roadmap(row("1", "`alpha`", "в работе"), candidate) + other)
        result = self.p.run()
        self.assertEqual(result.returncode, 0, result.stderr)
        expected = roadmap(row("1", "`alpha`", "в архиве"), candidate) + other
        self.assertEqual(self.p.read_roadmap(), expected)

    def test_tasks_file_without_boxes_has_no_count(self) -> None:
        self.p.active("alpha", "# Tasks\n\nNothing ticked yet.\n")
        self.p.roadmap(roadmap(row("1", "`alpha`", "запланировано")))
        self.p.run()
        self.assertEqual(self.status_of("`alpha`"), "в работе")

    def test_row_with_priority_not_doing_is_treated_like_any_other(self) -> None:
        self.p.active("alpha")
        self.p.roadmap(roadmap(row("1", "`alpha`", "запланировано", "не делаем")))
        self.p.run()
        self.assertEqual(self.status_of("`alpha`"), "в работе")

    def test_row_without_trailing_pipe_keeps_its_cells(self) -> None:
        self.p.archived("alpha")
        self.p.roadmap(roadmap(row("1", "`alpha`", "в работе").rstrip(" |")))
        result = self.p.run()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("| 1 | `alpha` | что-то | обязательно | в архиве |", self.p.read_roadmap())

    def test_rejects_unknown_arguments_without_writing(self) -> None:
        self.p.archived("alpha")
        text = roadmap(row("1", "`alpha`", "в работе"))
        self.p.roadmap(text)
        result = self.p.run("--chek")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("--chek", result.stderr)
        self.assertEqual(self.p.read_roadmap(), text)

    def test_generated_roadmap_passes_check(self) -> None:
        self.p.archived("alpha")
        self.p.active("beta", "- [ ] 1.1 a\n")
        self.p.roadmap(roadmap(row("1", "`alpha`", "запланировано"), row("1b", "`beta`", "запланировано")))
        self.p.run()
        result = self.p.run("--check")
        self.assertEqual(result.returncode, 0, result.stderr)


class CheckTest(ProjectTest):
    def test_passes_when_only_the_count_is_stale_and_does_not_write(self) -> None:
        self.p.archived("alpha")
        self.p.active("beta", "- [x] 1.1 a\n- [x] 1.2 b\n")
        text = roadmap(row("1", "`alpha`", "в архиве"), row("2", "`beta`", "в работе: 0/2"),
                       row("3", "`gamma`", "запланировано"))
        self.p.roadmap(text)
        result = self.p.run("--check")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(self.p.read_roadmap(), text)

    def test_fails_when_status_word_differs(self) -> None:
        self.p.archived("alpha")
        text = roadmap(row("1", "`alpha`", "в работе"))
        self.p.roadmap(text)
        result = self.p.run("--check")
        self.assertFails(result, "alpha")
        self.assertEqual(self.p.read_roadmap(), text)

    def test_fails_when_a_change_has_no_row(self) -> None:
        for kind in ("active", "archived"):
            with self.subTest(kind=kind):
                self.p = Project()
                self.addCleanup(self.p.close)
                if kind == "active":
                    self.p.active("orphan")
                else:
                    self.p.archived("orphan")
                self.p.roadmap(roadmap(row("1", "`alpha`", "запланировано")))
                self.assertFails(self.p.run("--check"), "orphan")

    def test_fails_when_a_started_row_has_no_change(self) -> None:
        # In both modes: the generator must not hide a lost change by rewriting its row.
        for status in ("в работе", "в архиве"):
            for args in ((), ("--check",)):
                with self.subTest(status=status, args=args):
                    text = roadmap(row("1", "`ghost`", status))
                    self.p.roadmap(text)
                    self.assertFails(self.p.run(*args), "ghost")
                    self.assertEqual(self.p.read_roadmap(), text)

    def test_fails_when_two_rows_name_the_same_change(self) -> None:
        self.p.roadmap(roadmap(row("1", "`alpha`", "запланировано"), row("2", "`alpha`", "запланировано")))
        self.assertFails(self.p.run("--check"), "alpha")

    def test_fails_when_a_change_name_has_two_folders(self) -> None:
        for second in ("active", "archived-again"):
            with self.subTest(second=second):
                self.p = Project()
                self.addCleanup(self.p.close)
                self.p.archived("alpha", "2026-10-06")
                if second == "active":
                    self.p.active("alpha")
                else:
                    self.p.archived("alpha", "2026-10-07")
                # Either status word would match one of the two folders; only duplicate detection fails.
                status = "в работе" if second == "active" else "в архиве"
                self.p.roadmap(roadmap(row("1", "`alpha`", status)))
                self.assertFails(self.p.run("--check"), "alpha")

    def test_fails_and_names_the_header_when_the_table_is_missing(self) -> None:
        self.p.roadmap(roadmap(row("1", "`alpha`", "запланировано"),
                               header="| # | Change | What | Priority | Status |"))
        for args in ((), ("--check",)):
            with self.subTest(args=args):
                self.assertFails(self.p.run(*args), HEADER)


class OrderGateTest(ProjectTest):
    """FAIL-005: a change folder must not exist while a mandatory row above it is not archived."""

    def test_fails_naming_both_rows_when_an_earlier_mandatory_row_is_open(self) -> None:
        # Table position decides "earlier", not the number in the first column; a folder with only a
        # proposal counts as started.
        self.p.active("alpha")
        self.p.roadmap(roadmap(row("2", "`beta`", "запланировано"), row("1", "`alpha`", "в работе")))
        self.assertFails(self.p.run("--check"), "alpha", "beta")

    def test_archived_folders_below_an_open_mandatory_row_pass(self) -> None:
        # Archived changes are history: a mandatory row may be inserted above them.
        self.p.archived("alpha")
        self.p.roadmap(roadmap(row("1", "`beta`", "запланировано"), row("2", "`alpha`", "в архиве")))
        result = self.p.run("--check")
        self.assertEqual(result.returncode, 0, result.stderr)

    def test_passes_when_earlier_rows_are_archived_or_not_mandatory(self) -> None:
        self.p.archived("alpha")
        self.p.active("gamma")
        self.p.roadmap(roadmap(row("1", "`alpha`", "в архиве"),
                               row("2", "`beta`", "запланировано", "если успеем"),
                               row("3", "`gamma`", "в работе"),
                               row("4", "`delta`", "запланировано")))
        result = self.p.run("--check")
        self.assertEqual(result.returncode, 0, result.stderr)


if __name__ == "__main__":
    unittest.main()
