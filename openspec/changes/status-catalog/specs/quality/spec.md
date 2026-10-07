## MODIFIED Requirements

### Requirement: QAS-MAINT-readability — bounded cognitive complexity

Every method of the application code SHALL have a cognitive complexity of at most 5, and every class of at
most 20. The thresholds were set by the owner on 2026-10-07 from the code of the first product change
(method maximum 3, class maximum 6; `IMP-001-readability-threshold`). Artifact: application code (`src/`).
Environment: `make check` — pre-commit and CI on every push. Source: `docs/task/assignment.txt:137`,
decision record 32.

#### Scenario: QAS-MAINT-readability.within-threshold

- **WHEN** the complexity check runs
- **THEN** no method is above 5 and no class is above 20, and the check passes

#### Scenario: QAS-MAINT-readability.over-threshold

- **WHEN** a change adds a method above 5 or a class above 20
- **THEN** the complexity check fails `make check` and names the method or class
