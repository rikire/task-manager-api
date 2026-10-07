#!/usr/bin/env python3
"""Decide whether a test run passes the Stop hook in TDD phase `tests`.

    stop_check.py <junit.xml>              a PHPUnit report
    stop_check.py unittest <dir> [<dir>…]  runs the Python `test_*.py` files under each dir

In phase `tests`, failing tests are expected — but only in test files that are new or changed in the
working tree; every other test must stay green (decision record 04, Stop hook; Python tests under the
same rule: owner, 2026-10-07). Exit 0 — pass, 1 — a failure in an unchanged test file, or no report
(PHPUnit did not finish).
"""
import subprocess
import sys
import xml.etree.ElementTree as ET
from pathlib import Path


def changed_test_files(*dirs: str) -> set[str]:
    out = subprocess.run(["git", "status", "--porcelain=v1", "-uall", "--", *dirs],
                         check=True, capture_output=True, text=True).stdout
    return {line[3:].strip() for line in out.splitlines() if line.strip()}


def run_unittest(dirs: list[str]) -> int:
    missing = [d for d in dirs if not Path(d).is_dir()]
    for directory in missing:
        print(f"{directory}: no such test directory", file=sys.stderr)
    if missing:
        return 1
    changed = changed_test_files(*dirs)
    unexpected = []
    for directory in dirs:
        for test_file in sorted(Path(directory).glob("test_*.py")):
            result = subprocess.run([sys.executable, "-m", "unittest", "discover", "-q", "-s", directory,
                                     "-p", test_file.name], capture_output=True, text=True)
            if result.returncode == 0:
                continue
            path = test_file.as_posix()
            if path in changed:
                print(f"{path}: failing (new or changed — allowed in phase `tests`)\n{result.stderr}")
            else:
                unexpected.append(path)
                print(result.stderr, file=sys.stderr)
    for path in unexpected:
        print(f"{path}: failing, but not new or changed — only tests of the active change may be red "
              "in phase `tests`", file=sys.stderr)
    return 1 if unexpected else 0


def failing_files(report: Path) -> set[str]:
    files = set()
    for case in ET.parse(report).iter("testcase"):
        if case.find("failure") is not None or case.find("error") is not None:
            path = case.get("file", "")
            # PHPUnit runs in the container (/app); the hook compares repository-relative paths.
            files.add(path.split("/app/", 1)[1] if "/app/" in path else path)
    return files


def main(argv: list[str]) -> int:
    if len(argv) > 2 and argv[1] == "unittest":
        return run_unittest(argv[2:])
    report = Path(argv[1]) if len(argv) == 2 else None
    if report is None or not report.exists():
        print("no PHPUnit report: the test run did not finish", file=sys.stderr)
        return 1
    unexpected = sorted(failing_files(report) - changed_test_files("tests/"))
    for path in unexpected:
        print(f"{path}: failing, but not new or changed — only tests of the active change may be red "
              "in phase `tests`", file=sys.stderr)
    return 1 if unexpected else 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
