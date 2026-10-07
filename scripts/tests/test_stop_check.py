"""Tests for scripts/stop_check.py: in TDD phase `tests` only new or changed test files may fail
(decision record 04, Stop hook)."""
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

SCRIPT = Path(__file__).resolve().parents[1] / "stop_check.py"


def junit(*cases: tuple[str, str]) -> str:
    """cases: (file, outcome) with outcome in pass | failure | error."""
    rows = []
    for i, (file, outcome) in enumerate(cases):
        child = {"pass": "", "failure": "<failure>boom</failure>", "error": "<error>boom</error>"}[outcome]
        rows.append(f'<testcase name="t{i}" file="/app/{file}" class="C{i}">{child}</testcase>')
    return f'<?xml version="1.0"?><testsuites><testsuite name="s">{"".join(rows)}</testsuite></testsuites>'


class StopCheckTest(unittest.TestCase):
    def setUp(self) -> None:
        self._tmp = tempfile.TemporaryDirectory()
        self.root = Path(self._tmp.name)
        for args in (["init", "-q"], ["config", "user.email", "t@e"], ["config", "user.name", "t"]):
            subprocess.run(["git", "-C", str(self.root), *args], check=True)
        self.write("tests/OldTest.php", "<?php\n")
        subprocess.run(["git", "-C", str(self.root), "add", "-A"], check=True)
        subprocess.run(["git", "-C", str(self.root), "commit", "-q", "-m", "init"], check=True)

    def tearDown(self) -> None:
        self._tmp.cleanup()

    def write(self, rel: str, content: str) -> None:
        path = self.root / rel
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(content)

    def run_check(self, report: str | None) -> subprocess.CompletedProcess:
        path = self.root / "junit.xml"
        if report is not None:
            path.write_text(report)
        return subprocess.run([sys.executable, str(SCRIPT), str(path)], cwd=self.root,
                              capture_output=True, text=True)

    def test_new_failing_test_is_allowed(self):
        self.write("tests/NewTest.php", "<?php\n")
        r = self.run_check(junit(("tests/OldTest.php", "pass"), ("tests/NewTest.php", "failure")))
        self.assertEqual(r.returncode, 0, r.stderr)

    def test_modified_failing_test_is_allowed(self):
        self.write("tests/OldTest.php", "<?php\n// changed\n")
        r = self.run_check(junit(("tests/OldTest.php", "error")))
        self.assertEqual(r.returncode, 0, r.stderr)

    def test_unchanged_failing_test_blocks(self):
        self.write("tests/NewTest.php", "<?php\n")
        r = self.run_check(junit(("tests/OldTest.php", "failure"), ("tests/NewTest.php", "failure")))
        self.assertEqual(r.returncode, 1)
        self.assertIn("tests/OldTest.php", r.stderr)

    def test_missing_report_blocks(self):
        r = self.run_check(None)
        self.assertEqual(r.returncode, 1)
        self.assertIn("report", r.stderr)


PASSING = "import unittest\n\n\nclass T(unittest.TestCase):\n    def test_ok(self):\n        pass\n"
FAILING = "import unittest\n\n\nclass T(unittest.TestCase):\n    def test_red_marker(self):\n        self.fail('red')\n"


class UnittestModeTest(unittest.TestCase):
    """`stop_check.py unittest <dir>...`: Python test files under the same rule as PHPUnit ones — in phase
    `tests` only new or changed files may fail (decision record 04; owner, 2026-10-07)."""

    def setUp(self) -> None:
        self._tmp = tempfile.TemporaryDirectory()
        self.root = Path(self._tmp.name)
        for args in (["init", "-q"], ["config", "user.email", "t@e"], ["config", "user.name", "t"]):
            subprocess.run(["git", "-C", str(self.root), *args], check=True)

    def tearDown(self) -> None:
        self._tmp.cleanup()

    def write(self, rel: str, content: str) -> None:
        path = self.root / rel
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(content)

    def commit(self) -> None:
        subprocess.run(["git", "-C", str(self.root), "add", "-A"], check=True)
        subprocess.run(["git", "-C", str(self.root), "commit", "-q", "-m", "c"], check=True)

    def run_check(self, *dirs: str) -> subprocess.CompletedProcess:
        return subprocess.run([sys.executable, str(SCRIPT), "unittest", *dirs], cwd=self.root,
                              capture_output=True, text=True)

    def test_new_failing_file_is_allowed_and_its_failure_is_shown(self):
        self.write("a/tests/test_old.py", PASSING)
        self.commit()
        self.write("a/tests/test_new.py", FAILING)
        r = self.run_check("a/tests")
        self.assertEqual(r.returncode, 0, r.stderr)
        self.assertIn("test_red_marker", r.stdout + r.stderr)

    def test_modified_failing_file_is_allowed(self):
        self.write("a/tests/test_old.py", PASSING)
        self.commit()
        self.write("a/tests/test_old.py", FAILING)
        r = self.run_check("a/tests")
        self.assertEqual(r.returncode, 0, r.stderr)

    def test_unchanged_failing_file_blocks_in_any_listed_dir(self):
        self.write("a/tests/test_ok.py", PASSING)
        self.write("b/tests/test_broken.py", FAILING)
        self.commit()
        r = self.run_check("a/tests", "b/tests")
        self.assertEqual(r.returncode, 1)
        self.assertIn("b/tests/test_broken.py", r.stderr)

    def test_missing_directory_blocks(self):
        # A typo in the Makefile must not turn the check into a silent pass.
        self.write("a/tests/test_ok.py", PASSING)
        self.commit()
        r = self.run_check("a/tests", "no/such/dir")
        self.assertEqual(r.returncode, 1)
        self.assertIn("no/such/dir", r.stderr)

    def test_all_green_passes(self):
        self.write("a/tests/test_ok.py", PASSING)
        self.commit()
        r = self.run_check("a/tests")
        self.assertEqual(r.returncode, 0, r.stderr)


if __name__ == "__main__":
    unittest.main()
