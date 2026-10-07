# Design: finish-init

Significance checklist (`openspec/config.yaml`): new module or boundary — no; data schema — no; API
contract — no; hard-to-replace dependency — no (items 1, 2, 4 are dev tools, removable; each gets a
candidate card below); new cross-cutting concern — no (process tooling, not application); affects a
`QAS-` scenario — `QAS-MAINT-readability` only through the reporting step promised by `IMP-001`; hard to
reverse — no. No ADR.

## Decisions

### D1. `scripts/roadmap.py` — Python standard library, same form as `scripts/git_checks.py`

Scope: local.

- Drivers: decision record 24 §3–4 (statuses from facts; two-way link), proposal "What Changes".
- Python 3 standard library only, tests with `unittest` in `scripts/tests/`, run by `make hooks-test`
  like the existing git-hook scripts: no new dependency, one language for project scripts.
- `scripts/roadmap.py` rewrites the status cells in place; `scripts/roadmap.py --check` only reads.
  The script is run from the repository root; paths `docs/roadmap.md` and `openspec/changes/` are
  constants. Tests run it in a throwaway directory (as `test_git_checks.py` does).
- Exit codes as in `git_checks.py`: 0 pass, 1 violation; each violation on its own stderr line naming the
  change or row.
- `make roadmap-check` joins `check` and the phase-`tests` subset of `stop-check`, so pre-commit, the
  Stop hook and CI all run it.

Known solutions: Backlog.md and Beads (task trackers in git) were considered in decision record 24 and
rejected for one developer; OpenSpec 1.14.0 `openspec list` shows task counts of active changes but
knows nothing of the roadmap, so it cannot check the link. The script reads the same files OpenSpec
does.

### D2. Order gate (`FAIL-005` remedy) lives in the same `--check`

Scope: local. Driver: owner's answer "4a"; `docs/pre-init/01-process-frame.md` (highest enforceable
level). One script owns the roadmap table, so the order rule reads the same parsed rows.

### D3. Phase `tests` tolerates new or changed Python test files (task 1.0)

Scope: local. Driver: decision record 04 (only tests of the active change may be red in phase `tests`);
owner, 2026-10-07, after test-first work on `scripts/roadmap.py` turned the Stop hook red: the rule
covered only PHPUnit (`junit.xml`). `scripts/stop_check.py unittest <dir>…` runs each `test_*.py` file
separately; files new or changed in `git status` may fail (their output is printed), any other failure
blocks. Outside phase `tests` nothing changes: `make check` runs `hooks-test` strictly.

### D4. `scripts/forms.py --check`: form of changes, review briefs and ADRs (tasks 5.1, 5.5)

Scope: local. Drivers: decision records 07 §2–4 and 12 §2, §6 (form checks in pre-commit and CI);
`FAIL-006` (review brief per task group); owner, 2026-10-07 (answer "a" to the list below). Run by
`make forms-check`, part of `make check` and of the phase-`tests` subset of `stop-check`.

Change form, for every active change in `openspec/changes/`:

1. `proposal.md` has the `##` sections of `openspec/config.yaml` in that order.
2. Coverage lists the 7 categories, each `clear`, `partial` or `missing`.
3. Once any box in `tasks.md` is ticked, Assumptions and Open questions hold no open item (only "None").
4. Every decision (`###` under `## Decisions`) in `design.md` carries `Scope: local` or `ADR: ADR-NNNN-…`.
5. The last task group of `tasks.md` is Polish.
6. Requirement headers in delta and main specs carry an ID (`REQ-…` or `QAS-…`); scenario headers carry
   `<requirement ID>.<slug>`.

Review brief:

1. Every task group whose boxes are all ticked has a section in `review.md` (`## Group N` or
   `## Groups N–M`) containing "Simplifications", "Debt" and "Maturity".

ADR form, for every `docs/adr/ADR-*.md`:
8. Fields Status (`active` or `deprecated`), Kind (`architecture` or `process`), Decided by, Drafted
   by; `deprecated` requires `Replaced by:`.
9. The eight sections of decision record 12 §2, Context and drivers … Revisit-when.
10. Considered options has at least 2 options.
11. Drivers cite an ID: `REQ-`, `QAS-` or `CON-` for `architecture`; `FAIL-`, `DEBT-` or a decision
    record for `process`.

Not checked by machine (review): that a scenario of unwanted behaviour exists; `Retires:` search and
`Confirmation` existence (decision record 34 §4, roadmap).

### D5. `Assisted-by` only on agent commits (task 5.2)

Scope: local. Drivers: decision record 21 §3; checklist 35 item "attribution" (agent commands run with
`CLAUDECODE=1`); owner, 2026-10-07. `scripts/git_checks.py commit-msg` requires the trailer
`Assisted-by: Claude Code` when `CLAUDECODE=1` is set; without it the message passes unchanged.

### D6. markdownlint and lychee (task 5.3)

Scope: local. Drivers: decision records 19 and 23 (markdownlint in pre-commit, links checked); owner,
2026-10-07 (answer "3a": lychee `--offline` locally, external links in CI only; `FAIL-007`).

- `make md` (part of `make check`): `markdownlint-cli2` with `.markdownlint-cli2.jsonc` and
  `lychee --offline`. `make md-fix` auto-fixes everything except `AGENTS.md`, `CLAUDE.md`, `.claude/**`.
- Config choices (agent, reported to the owner): line length 120 without tables and code; MD024 only for
  sibling headings; MD036 (bold labels), MD060 (table alignment), MD041 (frontmatter files) off; not
  linted: `docs/pre-init/**` (frozen history), the archive, files generated by `openspec init`, the
  unchanged `doc-coauthoring` skill.
- CI step "External links": everything except `docs/pre-init` and `localhost`; 403 and 429 count as
  reachable. A local online run on 2026-10-07 found 41 errors, all in `docs/pre-init` (bot blocks, one
  404) and the README's `localhost` link; 0 elsewhere.

## Simplifications

### S1. `--check` ignores the task count

- Source: owner, 2026-10-07 (answer "2a"); proposal, Confirmed.
- What: `в работе: N/M` may be stale; only the status word is compared, so ticking a task never turns
  the Stop hook red. The count is as fresh as the last `scripts/roadmap.py` run.
- Revisit-when: a stale count misleads the owner once, or a hook can regenerate the roadmap after a
  write to `tasks.md` without blocking.

### S2. Any active change folder counts for the order gate

- Source: owner, 2026-10-07 (answer "4a"; archived folders excluded after the group-1 review); `FAIL-005`.
- What: the next change cannot even be drafted (`/opsx:new` creates the folder) while an earlier
  mandatory row is open; changes never overlap. Archived folders never trip the gate, so a new mandatory
  row may be inserted above archived ones.
- Revisit-when: the owner wants to draft or interview the next change in parallel.

### S3. Abandoning a change is not supported

- Source: proposal, Out of scope.
- What: deleting a change folder whose row says `в работе` fails `--check` and the generator alike (the
  generator leaves the row unchanged, so regenerating never hides it); the owner edits the row by hand
  (for example back to `запланировано`, or a new priority `не делаем`).
- Revisit-when: a change is abandoned for real.

### S4. Python test files run one process each in phase `tests`

- Source: D3.
- What: per-file runs make the phase-`tests` check slower than one `unittest discover`; by how much is
  not measured.
- Revisit-when: the Stop-hook time measured in task 2.2 exceeds the budget of decision record 04.

## Candidate cards

Filled in by tasks 3.x (step 13) and 6.x (items 1, 2, 4); each card: what, source, version and its
support for PHP 8.4 / Symfony 8.1, what it adds, what else it needs, the owner's decision.

### Step 13 — style guides and skills (search by the `researcher` subagent, 2026-10-07)

| Candidate | Source, version | Adds | Needs, risks | Decision (owner, 2026-10-07) |
|---|---|---|---|---|
| Symfony Coding Standards + PER-CS 3.0 | symfony.com/doc/current/contributing/code/standards.html; php-fig.org/per/coding-style (3.1 exists, fixer has sets up to 3.0) | already covered by `@Symfony` (extends `@PER-CS3x0`, checked in `vendor/…/SymfonySet.php`) | — | keep |
| `@Symfony:risky` + `@PHP8x4Migration` + `ordered_class_elements` | PHP-CS-Fixer 3.95.27 (installed) | risky modernisations (`void_return`, `native_function_invocation`, …), PHP 8.4 syntax, member order of the standard | `@Symfony:risky` removes `declare(strict_types=1)`; an explicit `declare_strict_types` rule overrides it (dry run: adds it to 3 files) | adopt, with `strict_types` mandatory (answer 1a) |
| Rules the config cannot express | Symfony Coding Standards, Best Practices | `RUL-CODE-style`, `-exception-messages`, `-naming`, `-phpdoc`, `-di`, `-config` | — | adopt (2a) |
| Deviations from Symfony Best Practices | symfony.com/doc/current/best_practices.html | documented once, linked to ADRs | — | `docs/architecture/README.md` §9 (3a) |
| `php-lsp` plugin (official Anthropic) | claude-plugins-official, 1.0.0 | Intelephense LSP: definitions, references | global `npm install -g intelephense` outside the containers; freemium licence (not verified); overlaps PHPStan max and reading `vendor/` | skip (4a) |
| `superpowers-symfony` (community) | github.com/dev-toolings/superpowers-symfony, 0.4.0, 2026-10-05 | 44 skills, 7 agents, SessionStart hook | own TDD workflow overlaps OpenSpec and phase hooks; targets PHPUnit 10/11, attributes, Foundry | skip (4a) |
| `symfony-hexagonal-skill` (community) | github.com/aligundogdu/symfony-hexagonal-skill, 2026-03-30 | hexagonal skills, XML mapping | layer-first layout and CQRS buses contradict ADR-0006; no licence file | skip (4a) |
| Symfony UX skills (official Symfony) | github.com/smnandre/symfony-ux-skills | Stimulus, Turbo, Twig components | frontend only | skip (4a) |
| Symfony AI Mate (official Symfony AI) | github.com/symfony/ai-mate, 0.13 | profiler, logs, services via MCP | pre-1.0; `mate discover` writes into AGENTS.md and CLAUDE.md; a new dependency | skip (4a) |
| Generic PHP skills | jeffallan/claude-skills (`php-pro`), efficience-it/claude-skills-php | generic PHP advice | nothing over PHPStan max and project rules; one has no licence | skip (4a) |

Not found: `llms.txt` at symfony.com and doctrine-project.org (404); official Doctrine guidance for
agents.
