---
paths:
  - "tests/**"
---

# Test rules

- **Phase.** Tests are written in phase `tests` only. In `impl` and `refactor` a hook blocks test edits;
  if a test looks wrong, stop and ask. Source: `docs/pre-init/05-tdd.md`.
- **Input.** Derive tests from the spec scenarios and the input contract (what may arrive, from whom,
  what is promised back), not from the implementation. Source: `docs/pre-init/06-test-quality.md`.
- **Corner cases by method.** For each input walk: emptiness and absence; size (0, 1, boundary,
  boundary ±1, max); type and format; structure (nesting, duplicates, order); state (repeat call, illegal
  transition, partial failure); trust (foreign resource, injection). Source: `docs/pre-init/06-test-quality.md`.
- **One test per behaviour class.** Two inputs with the same path and result are one test; one happy-path
  test per scenario, the rest cover boundaries and failures.
- **A case the spec does not answer is a question,** not a guess: the answer goes into the spec first.
- **Assert behaviour:** response and stored state, not call order. Do not mock the code under test;
  mock only external boundaries (clock, randomness, external services).
- **Trace.** Each test carries `#[Group('R-<CAP>-<slug>.<scenario>')]`; a test without a requirement
  carries `#[Group('internal')]` and a comment on why the component exists. Source:
  `docs/pre-init/09-traceability.md`.
- **List endpoints** get a query-count test: 1 vs N records, the number of queries must not grow.
  Source: `docs/pre-init/14-code-security.md`.
