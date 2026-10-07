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

## IMP-002-non-root-containers

- **What could be better:** the `app` and `migrate` containers run as `root`, the default of the
  `dunglas/frankenphp` image. A non-root user limits what a compromised process can do in the container.
- **Why not now:** nothing is deployed (deploying is out of scope, `docs/task/assignment.txt:141`); the
  reviewer runs the stack locally (owner, 2026-10-07; checklist step 8).
- **Trigger:** before any real deployment of the production image (`QAS-DEPLOY-prod-image`).
- **Size:** 1 `USER` line and ownership of `var/` in the `Dockerfile`; FrankenPHP's data and config
  directories made writable for that user; the clean-clone check re-run.
