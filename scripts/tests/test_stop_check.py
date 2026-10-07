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


if __name__ == "__main__":
    unittest.main()
