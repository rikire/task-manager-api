"""Tests for scripts/git_checks.py, run against throwaway git repositories.

Rules under test: decision records 09 (Refs), 10 (TODO with a register ID, no DEBUG:), 18 (soft diff
limit), 20 (skill frontmatter), 21 (instructions in a separate commit).
"""
import subprocess
import sys
import tempfile
import textwrap
import unittest
from pathlib import Path

SCRIPT = Path(__file__).resolve().parents[1] / "git_checks.py"
REGISTER = "## IMP-001-readability-threshold\n\n- text\n"


class Repo:
    def __init__(self) -> None:
        self._tmp = tempfile.TemporaryDirectory()
        self.root = Path(self._tmp.name)
        self.git("init", "-q", "-b", "main")
        self.git("config", "user.email", "t@example.com")
        self.git("config", "user.name", "t")
        self.write("docs/registers/debt.md", REGISTER)
        self.git("add", "-A")
        self.git("commit", "-q", "-m", "init")

    def close(self) -> None:
        self._tmp.cleanup()

    def git(self, *args: str) -> str:
        return subprocess.run(["git", "-C", str(self.root), *args], check=True,
                              capture_output=True, text=True).stdout

    def write(self, rel: str, content: str) -> None:
        path = self.root / rel
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(content)

    def stage(self, rel: str, content: str) -> None:
        self.write(rel, content)
        self.git("add", rel)

    def run(self, *args: str) -> subprocess.CompletedProcess:
        return subprocess.run([sys.executable, str(SCRIPT), *args], cwd=self.root,
                              capture_output=True, text=True)


class PreCommitTest(unittest.TestCase):
    def setUp(self) -> None:
        self.repo = Repo()

    def tearDown(self) -> None:
        self.repo.close()

    def test_passes_clean_code(self):
        self.repo.stage("src/A.php", "<?php\nfinal class A {}\n")
        r = self.repo.run("pre-commit")
        self.assertEqual(r.returncode, 0, r.stderr)

    def test_blocks_debug_marker(self):
        self.repo.stage("src/A.php", "<?php\n// DEBUG: dump\nvar_dump(1);\n")
        r = self.repo.run("pre-commit")
        self.assertEqual(r.returncode, 1)
        self.assertIn("DEBUG:", r.stderr)
        self.assertIn("src/A.php", r.stderr)

    def test_blocks_todo_without_register_id(self):
        self.repo.stage("src/A.php", "<?php\n// TODO: later\n")
        r = self.repo.run("pre-commit")
        self.assertEqual(r.returncode, 1)
        self.assertIn("TODO", r.stderr)

    def test_blocks_todo_with_unknown_register_id(self):
        self.repo.stage("src/A.php", "<?php\n// TODO(DEBT-099-missing): later\n")
        r = self.repo.run("pre-commit")
        self.assertEqual(r.returncode, 1)
        self.assertIn("DEBT-099-missing", r.stderr)

    def test_allows_todo_with_existing_register_id(self):
        self.repo.stage("src/A.php", "<?php\n// TODO(IMP-001-readability-threshold): later\n")
        r = self.repo.run("pre-commit")
        self.assertEqual(r.returncode, 0, r.stderr)

    def test_ignores_markers_in_unchanged_lines_and_in_docs(self):
        self.repo.stage("docs/process.md", "Use `TODO(DEBT-…)` and never commit `DEBUG:`.\n")
        r = self.repo.run("pre-commit")
        self.assertEqual(r.returncode, 0, r.stderr)

    def test_blocks_instructions_mixed_with_other_files(self):
        self.repo.stage("AGENTS.md", "# rules\n")
        self.repo.stage("src/A.php", "<?php\n")
        r = self.repo.run("pre-commit")
        self.assertEqual(r.returncode, 1)
        self.assertIn("separate commit", r.stderr)

    def test_allows_instructions_alone(self):
        self.repo.stage(".claude/rules/code.md", "# rules\n")
        self.repo.stage("openspec/config.yaml", "schema: spec-driven\n")
        r = self.repo.run("pre-commit")
        self.assertEqual(r.returncode, 0, r.stderr)

    def test_warns_but_passes_on_large_product_diff(self):
        self.repo.stage("src/Big.php", "<?php\n" + "$a = 1;\n" * 450)
        r = self.repo.run("pre-commit")
        self.assertEqual(r.returncode, 0, r.stderr)
        self.assertIn("warning", r.stderr.lower())
        self.assertIn("400", r.stderr)

    def test_large_tests_do_not_warn(self):
        self.repo.stage("tests/BigTest.php", "<?php\n" + "$a = 1;\n" * 450)
        r = self.repo.run("pre-commit")
        self.assertEqual(r.returncode, 0, r.stderr)
        self.assertNotIn("warning", r.stderr.lower())

    def test_blocks_skill_without_description(self):
        self.repo.stage(".claude/skills/demo/SKILL.md", "---\nname: demo\n---\n\nBody\n")
        r = self.repo.run("pre-commit")
        self.assertEqual(r.returncode, 1)
        self.assertIn("description", r.stderr)

    def test_blocks_skill_name_not_matching_folder(self):
        self.repo.stage(".claude/skills/demo/SKILL.md",
                        "---\nname: other\ndescription: Does a thing. Use when asked.\n---\n")
        r = self.repo.run("pre-commit")
        self.assertEqual(r.returncode, 1)
        self.assertIn("name", r.stderr)

    def test_allows_valid_skill(self):
        self.repo.stage(".claude/skills/demo/SKILL.md",
                        "---\nname: demo\ndescription: Does a thing. Use when asked.\n---\n\nBody\n")
        r = self.repo.run("pre-commit")
        self.assertEqual(r.returncode, 0, r.stderr)


class CommitMsgTest(unittest.TestCase):
    def setUp(self) -> None:
        self.repo = Repo()

    def tearDown(self) -> None:
        self.repo.close()

    def msg(self, text: str) -> Path:
        path = self.repo.root / "MSG"
        path.write_text(textwrap.dedent(text))
        return path

    def test_change_branch_code_commit_requires_refs(self):
        self.repo.git("switch", "-q", "-c", "change/demo")
        self.repo.stage("src/A.php", "<?php\n")
        r = self.repo.run("commit-msg", str(self.msg("feat(demo): код\n\nAssisted-by: Claude Code\n")))
        self.assertEqual(r.returncode, 1)
        self.assertIn("Refs:", r.stderr)

    def test_change_branch_code_commit_with_refs_passes(self):
        self.repo.git("switch", "-q", "-c", "change/demo")
        self.repo.stage("src/A.php", "<?php\n")
        r = self.repo.run("commit-msg", str(self.msg(
            "feat(demo): код\n\nRefs: REQ-TASK-create-task, REQ-TASK-create-task.invalid-json\n")))
        self.assertEqual(r.returncode, 0, r.stderr)

    def test_change_branch_tests_only_commit_needs_no_refs(self):
        self.repo.git("switch", "-q", "-c", "change/demo")
        self.repo.stage("tests/ATest.php", "<?php\n")
        r = self.repo.run("commit-msg", str(self.msg("test(demo): тесты\n")))
        self.assertEqual(r.returncode, 0, r.stderr)

    def test_chore_branch_code_commit_needs_no_refs(self):
        self.repo.git("switch", "-q", "-c", "chore/demo")
        self.repo.stage("src/A.php", "<?php\n")
        r = self.repo.run("commit-msg", str(self.msg("chore: каркас\n")))
        self.assertEqual(r.returncode, 0, r.stderr)

    def test_malformed_refs_is_rejected_on_any_branch(self):
        self.repo.stage("docs/a.md", "x\n")
        r = self.repo.run("commit-msg", str(self.msg("docs: текст\n\nRefs: TASK-1\n")))
        self.assertEqual(r.returncode, 1)
        self.assertIn("TASK-1", r.stderr)


if __name__ == "__main__":
    unittest.main()
