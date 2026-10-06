---
name: test-writing
description: Write tests that catch defects - corner cases derived by method, one test per behaviour class, behaviour assertions, traceability groups. Use in TDD phase tests, when adding or reviewing tests, or when test coverage of a scenario is questioned.
when_to_use: phase tests of a change; "write tests for"; reviewing a test suite; a scenario without a test.
---

# Test writing

Sources: `docs/pre-init/06-test-quality.md`, `05-tdd.md`, `09-traceability.md`, `14-code-security.md`.

## Steps

1. **Inputs:** the scenarios of the task group and the input contract — what may arrive, from whom, what
   is promised back. Do not read the implementation to decide expectations.
2. **Matrix:** for each input walk the dimensions — emptiness and absence; size (0, 1, boundary,
   boundary ±1, max); type and format; structure (nesting, duplicates, order); state (repeat call,
   illegal transition, partial failure); trust (foreign resource, injection). Each cell: expected
   behaviour from the spec, "not applicable" with a reason, or a question to the human.
3. **Merge equivalent cases:** same path and same result → one test. One happy-path test per scenario.
4. **Write tests** (PHPUnit):
   - `#[Group('R-<CAP>-<slug>.<scenario>')]` on each test; `#[Group('internal')]` plus a comment on why
     the component exists for tests without a requirement;
   - names state the behaviour in PHP camelCase (`testRejectsTaskWithEmptyTitle`);
   - assert the response **and** the stored state; literal expected values, not values computed by the
     code under test;
   - mock only external boundaries (clock, randomness, external services), never the code under test;
   - list endpoints: query-count test with 1 and N records — the count must not grow;
   - include tests that try to break: hostile input, illegal state transitions.
5. **Run** and confirm each new test fails on its assertion.
6. **Present** the matrix with a "covered by" column for the human's acceptance.

## Do not

- Do not write a second happy-path test that only varies data.
- Do not weaken, skip or delete an existing test; that is the human's decision.
- Do not answer a spec question inside a test.

If unsure the tests are strong enough, say so in the review brief and propose property-based tests or a
mutation run (optional; the human decides). The `breaker` subagent is on the roadmap
(`docs/pre-init/34-core-vs-roadmap.md`), not available yet.
