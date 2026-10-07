"""Unit tests for Claude Code hooks (docs/pre-init/16-guardrails-harness.md §4).

Run: python3 -m unittest discover -s .claude/hooks/tests -v
Payloads follow the documented hook input format; replace with payloads recorded from a real session
(docs/pre-init/35-init-checklist.md).
"""

import json
import os
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

HOOKS = Path(__file__).resolve().parents[1]


class HookCase(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.root = Path(self.tmp.name)
        subprocess.run(["git", "init", "-q", str(self.root)], check=True)
        (self.root / ".agent-state").mkdir()

    def tearDown(self):
        self.tmp.cleanup()

    def set_phase(self, phase):
        (self.root / ".agent-state" / "phase").write_text(phase + "\n")

    def run_hook(self, name, payload):
        env = dict(os.environ, CLAUDE_PROJECT_DIR=str(self.root))
        return subprocess.run([sys.executable, str(HOOKS / name)], input=json.dumps(payload),
                              capture_output=True, text=True, env=env, cwd=self.root)


class ProtectTests(HookCase):
    def edit(self, path):
        return {"hook_event_name": "PreToolUse", "tool_name": "Edit",
                "tool_input": {"file_path": path, "old_string": "a", "new_string": "b"}}

    def bash(self, command):
        return {"hook_event_name": "PreToolUse", "tool_name": "Bash", "tool_input": {"command": command}}

    def test_edit_of_test_file_is_blocked_in_impl(self):
        self.set_phase("impl")
        r = self.run_hook("protect_tests.py", self.edit(str(self.root / "tests/Api/TaskTest.php")))
        self.assertEqual(r.returncode, 2)
        self.assertIn("locked", r.stderr)

    def test_edit_of_test_file_is_blocked_in_refactor_with_relative_path(self):
        self.set_phase("refactor")
        self.assertEqual(self.run_hook("protect_tests.py", self.edit("tests/Unit/X.php")).returncode, 2)

    def test_edit_of_test_file_is_allowed_in_tests_phase(self):
        self.set_phase("tests")
        self.assertEqual(self.run_hook("protect_tests.py", self.edit("tests/Unit/X.php")).returncode, 0)

    def test_edit_of_source_file_is_allowed_in_impl(self):
        self.set_phase("impl")
        self.assertEqual(self.run_hook("protect_tests.py", self.edit("src/Task.php")).returncode, 0)

    def test_without_phase_file_tests_are_editable(self):
        self.assertEqual(self.run_hook("protect_tests.py", self.edit("tests/Unit/X.php")).returncode, 0)

    def test_bash_write_into_tests_is_blocked_in_impl(self):
        self.set_phase("impl")
        for command in ("echo x > tests/A.php", "sed -i 's/a/b/' tests/A.php", "rm tests/A.php",
                        "python3 -c \"open('tests/A.php','w')\"", "git checkout -- tests/A.php"):
            with self.subTest(command=command):
                self.assertEqual(self.run_hook("protect_tests.py", self.bash(command)).returncode, 2)

    def test_bash_read_of_tests_is_allowed_in_impl(self):
        self.set_phase("impl")
        self.assertEqual(self.run_hook("protect_tests.py", self.bash("cat tests/A.php")).returncode, 0)

    def test_phase_file_cannot_be_written_directly(self):
        r = self.run_hook("protect_tests.py", self.bash("echo tests > .agent-state/phase"))
        self.assertEqual(r.returncode, 2)

    def test_running_a_test_file_with_redirected_output_is_allowed_in_impl(self):
        self.set_phase("impl")
        for command in ("make test FILE=tests/A.php 2>&1 | tail -20", "make test FILE=tests/A.php > /dev/null"):
            with self.subTest(command=command):
                self.assertEqual(self.run_hook("protect_tests.py", self.bash(command)).returncode, 0)

    def test_phase_file_cannot_be_written_after_phase_script(self):
        r = self.run_hook("protect_tests.py", self.bash("scripts/phase impl && echo tests > .agent-state/phase"))
        self.assertEqual(r.returncode, 2)

    def test_phase_script_is_allowed(self):
        self.set_phase("impl")
        self.assertEqual(self.run_hook("protect_tests.py", self.bash("scripts/phase refactor")).returncode, 0)

    def test_malformed_input_blocks(self):
        env = dict(os.environ, CLAUDE_PROJECT_DIR=str(self.root))
        r = subprocess.run([sys.executable, str(HOOKS / "protect_tests.py")], input="{not json",
                           capture_output=True, text=True, env=env)
        self.assertEqual(r.returncode, 2, "an internal error must block (fail closed)")


class ProtectAskPaths(HookCase):
    """Shell writes to files under an `ask` rule must not skip the owner's confirmation (FAIL-004)."""

    def setUp(self):
        super().setUp()
        (self.root / ".claude").mkdir()
        (self.root / ".claude" / "settings.json").write_text(json.dumps({"permissions": {"ask": [
            "Bash(git commit *)", "Edit(./Makefile)", "Edit(./deptrac.yaml)", "Edit(./.claude/**)",
        ]}}))

    def bash(self, command):
        return {"hook_event_name": "PreToolUse", "tool_name": "Bash", "tool_input": {"command": command}}

    def test_shell_writes_to_protected_files_are_blocked(self):
        for command in ("python3 -I - <<'EOF'\np='Makefile'; open(p,'w').write(s)\nEOF",
                        "echo x >> deptrac.yaml", "sed -i 's/a/b/' Makefile", "cp /tmp/d.yaml deptrac.yaml",
                        "printf x > .claude/rules/code.md", "git checkout -- Makefile",
                        "python3 -c \"import pathlib; pathlib.Path('deptrac.yaml').write_text('x')\""):
            with self.subTest(command=command):
                r = self.run_hook("protect_ask_paths.py", self.bash(command))
                self.assertEqual(r.returncode, 2)
                self.assertIn("ask", r.stderr)

    def test_reads_and_tool_runs_are_allowed(self):
        for command in ("cat Makefile", "make check", "grep -n deptrac Makefile", "git diff Makefile",
                        "vendor/bin/deptrac analyse --config-file=deptrac.yaml", "git add Makefile deptrac.yaml",
                        "ls .claude/rules", "grep -n deptrac Makefile 2>&1 | tail -3",
                        "make lint-file FILE=src/A.php > /dev/null 2>&1; cat deptrac.yaml"):
            with self.subTest(command=command):
                self.assertEqual(self.run_hook("protect_ask_paths.py", self.bash(command)).returncode, 0)

    def test_commit_and_pr_messages_mentioning_protected_files_are_allowed(self):
        for command in ("git commit -q -F - <<'EOF'\nchore: Makefile — cp и mv в тексте\nEOF",
                        "gh pr create --title t --body 'deptrac.yaml > old rules'"):
            with self.subTest(command=command):
                self.assertEqual(self.run_hook("protect_ask_paths.py", self.bash(command)).returncode, 0)

    def test_unprotected_files_are_writable(self):
        self.assertEqual(self.run_hook("protect_ask_paths.py", self.bash("echo x > docs/a.md")).returncode, 0)

    def test_edit_tools_are_not_its_business(self):
        payload = {"hook_event_name": "PreToolUse", "tool_name": "Edit",
                   "tool_input": {"file_path": "Makefile", "old_string": "a", "new_string": "b"}}
        self.assertEqual(self.run_hook("protect_ask_paths.py", payload).returncode, 0)

    def test_malformed_input_blocks(self):
        env = dict(os.environ, CLAUDE_PROJECT_DIR=str(self.root))
        r = subprocess.run([sys.executable, str(HOOKS / "protect_ask_paths.py")], input="{not json",
                           capture_output=True, text=True, env=env)
        self.assertEqual(r.returncode, 2, "an internal error must block (fail closed)")


class StopCheck(HookCase):
    def write_makefile(self, recipe):
        (self.root / "Makefile").write_text(f"stop-check:\n\t{recipe}\n")

    def test_passes_without_makefile(self):
        self.assertEqual(self.run_hook("stop_check.py", {"hook_event_name": "Stop"}).returncode, 0)

    def test_blocks_when_check_fails_after_changes(self):
        self.write_makefile("@echo boom; exit 1")
        r = self.run_hook("stop_check.py", {"hook_event_name": "Stop"})
        self.assertEqual(r.returncode, 2)
        self.assertIn("boom", r.stderr)

    def test_passes_when_check_succeeds(self):
        self.write_makefile("@true")
        self.assertEqual(self.run_hook("stop_check.py", {"hook_event_name": "Stop"}).returncode, 0)

    def test_skips_when_tree_unchanged_since_last_success(self):
        self.write_makefile("@true")
        self.run_hook("stop_check.py", {"hook_event_name": "Stop"})
        self.write_makefile("@exit 1")  # changes the tree, so the next run must check again
        self.assertEqual(self.run_hook("stop_check.py", {"hook_event_name": "Stop"}).returncode, 2)

    def test_releases_turn_after_max_blocks(self):
        self.write_makefile("@exit 1")
        codes = [self.run_hook("stop_check.py", {"hook_event_name": "Stop"}).returncode for _ in range(4)]
        self.assertEqual(codes, [2, 2, 2, 0])

    def test_passes_phase_to_make(self):
        self.set_phase("tests")
        self.write_makefile('@test "$(PHASE)" = tests')
        self.assertEqual(self.run_hook("stop_check.py", {"hook_event_name": "Stop"}).returncode, 0)


class PostEdit(HookCase):
    def payload(self, path, new):
        return {"hook_event_name": "PostToolUse", "tool_name": "Edit",
                "tool_input": {"file_path": path, "old_string": "x", "new_string": new}}

    def test_reminds_about_new_catch(self):
        r = self.run_hook("post_edit.py", self.payload("src/A.php", "} catch (Exception $e) { return null; }"))
        self.assertEqual(r.returncode, 0)
        self.assertIn("RUL-CODE-fail-fast", json.loads(r.stdout)["hookSpecificOutput"]["additionalContext"])

    def test_silent_for_plain_php(self):
        r = self.run_hook("post_edit.py", self.payload("src/A.php", "return $a + $b;"))
        self.assertEqual((r.returncode, r.stdout.strip()), (0, ""))

    def test_ignores_non_php(self):
        r = self.run_hook("post_edit.py", self.payload("README.md", "catch (x)"))
        self.assertEqual((r.returncode, r.stdout.strip()), (0, ""))

    def test_lint_failure_is_returned_to_agent(self):
        (self.root / "Makefile").write_text("lint-file:\n\t@echo 'lint: bad style in $(FILE)'; exit 1\n")
        r = self.run_hook("post_edit.py", self.payload("src/A.php", "return 1;"))
        self.assertEqual(r.returncode, 2)
        self.assertIn("bad style in src/A.php", r.stderr)


class SessionStart(HookCase):
    def test_reports_missing_hooks_as_warning(self):
        r = self.run_hook("session_start.py", {"hook_event_name": "SessionStart", "source": "startup"})
        context = json.loads(r.stdout)["hookSpecificOutput"]["additionalContext"]
        self.assertTrue(context.startswith("HARNESS WARNING"))
        self.assertIn("TDD phase: off", context)

    def test_reports_missing_sandbox_dependencies(self):
        env = dict(os.environ, CLAUDE_PROJECT_DIR=str(self.root), PATH=str(self.root))
        r = subprocess.run([sys.executable, str(HOOKS / "session_start.py")],
                           input=json.dumps({"hook_event_name": "SessionStart", "source": "startup"}),
                           capture_output=True, text=True, env=env, cwd=self.root)
        context = json.loads(r.stdout)["hookSpecificOutput"]["additionalContext"]
        self.assertIn("bwrap", context)
        self.assertIn("socat", context)


if __name__ == "__main__":
    unittest.main()
