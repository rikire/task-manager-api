# Debt and improvement register

`DEBT-` — done below the norm, must be fixed. `IMP-` — works, could be better, with a trigger.
Format and rules: `docs/pre-init/10-debt-polish-headroom.md` §2.

## IMP-001-readability-threshold

- **What could be better:** `QAS-MAINT-readability` has no cognitive complexity threshold yet, so the
  complexity check reports but does not fail CI.
- **Why not now:** decision record 32 sets the threshold after measurements, and there is no product
  code to measure before the first product change (owner, 2026-10-06; change `architecture-kickoff`).
- **Trigger:** the first product change is merged; its code is measured and the threshold set.
- **Size:** 1 config value (complexity check threshold), 1 CI step switched from report to fail, the
  `QAS-MAINT-readability` requirement text updated with the number.
