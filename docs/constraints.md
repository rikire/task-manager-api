# External constraints

Limits on the solution imposed from outside the project: the assignment, the recruiter, the reviewer's
environment. Our own conventions are `RUL-` rules in `.claude/rules/` and AGENTS.md, not here. Format,
lifecycle and how to choose between `REQ` / `QAS` / `CON`: `docs/pre-init/36-constraints-and-ids.md`.

Normative. An entry without `Confirmed:` is an assumption, not a constraint. Line references point to
`docs/task/assignment.txt` (the assignment with the prompt injection removed). Coverage of every
assignment item: `docs/task/README.md`.

## STACK

### CON-STACK-php-symfony-pg — PHP 8+, Symfony 6+, PostgreSQL, Docker Compose

- Source: docs/task/assignment.txt:97-100
- Kind: technical
- Affects: ADR-0001-platform-versions, ADR-0002-php-runtime, ADR-0004-orm
- Check: review of composer.json and compose.yaml
- Confirmed: human, 2026-10-06
- Status: active

### CON-STACK-rest — the interface is a REST API over HTTP

- Source: docs/task/assignment.txt:17, 101
- Kind: technical
- Affects: ADR-0003-api-contract, ADR-0005-validation, ADR-0007-api-conventions
- Check: review
- Confirmed: human, 2026-10-06
- Status: active

## PLAN

### CON-PLAN-deadline — submission by 2026-10-09

- Source: recruiter correspondence, recorded in docs/pre-init/31-task-requirements.md (the assignment
  says the recruiter sets the deadline, docs/task/assignment.txt:12)
- Kind: organizational
- Affects: docs/pre-init/34-core-vs-roadmap.md, ADR-0003-api-contract, ADR-0006-module-structure
- Check: review
- Confirmed: human, 2026-10-06
- Status: active

## DELIV

### CON-DELIV-public-repo — the solution is a public Git repository on GitHub or GitLab

- Source: docs/task/assignment.txt:7, 102, 106
- Kind: organizational
- Affects: docs/pre-init/28-hosting-ci-git.md
- Check: review
- Confirmed: human, 2026-10-06
- Status: active

### CON-DELIV-commit-history — commit in small steps with clear messages; do not squash history before submission

- Source: docs/task/assignment.txt:106
- Kind: convention
- Affects: docs/pre-init/25-work-history.md, docs/pre-init/28-hosting-ci-git.md
- Check: review of `git log` before submission
- Confirmed: human, 2026-10-06
- Status: active

### CON-DELIV-frozen-main — after the link is sent, no pushes to the main branch; the reviewed commit is the one named in «Ваше решение»; later fixes go to a separate branch

- Source: docs/task/assignment.txt:106
- Kind: organizational
- Affects: —
- Check: review after submission
- Confirmed: human, 2026-10-06
- Status: active

### CON-DELIV-readme-sections — README has the sections: how to run; architecture decisions and trade-offs (3–7 items, including how Task and Status are linked, how status deletion is handled, why this project structure, what was deliberately simplified); what next (including what was not done, if time ran out); AI usage (what for and how the result was checked); time spent (an honest number)

- Source: docs/task/assignment.txt:13, 107-112, 129
- Kind: convention
- Affects: docs/pre-init/33-readme.md, ADR-0006-module-structure, ADR-0007-api-conventions
- Check: review before submission
- Confirmed: human, 2026-10-06
- Status: active

### CON-DELIV-ambiguity-in-readme — where a requirement can be read in more than one way, choose a reasonable option and describe it in README

- Source: docs/task/assignment.txt:14
- Kind: convention
- Affects: ADR-0007-api-conventions
- Check: review of README against Assumptions in archived OpenSpec changes
- Confirmed: human, 2026-10-06
- Status: active

### CON-DELIV-justify-extras — any improvement beyond the base requirements is briefly justified in README

- Source: docs/task/assignment.txt:124
- Kind: convention
- Affects: —
- Check: review before submission
- Confirmed: human, 2026-10-06
- Status: active

### CON-DELIV-explain-without-ai — the owner understands every line and at the next stage shows the code, explains the decisions and makes a small change without an AI assistant

- Source: docs/task/assignment.txt:128, 155
- Kind: organizational
- Affects: docs/pre-init/18-human-comprehension.md, docs/pre-init/31-task-requirements.md,
  ADR-0001-platform-versions, ADR-0002-php-runtime, ADR-0003-api-contract, ADR-0004-orm,
  ADR-0005-validation, ADR-0006-module-structure, ADR-0007-api-conventions
- Check: rehearsal without AI before submission (decision record 31 §2)
- Confirmed: human, 2026-10-06
- Status: active
