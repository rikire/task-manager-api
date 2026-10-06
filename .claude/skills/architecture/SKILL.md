---
name: architecture
description: Make an architectural decision the disciplined way - drivers, known patterns and tactics, options with trade-offs, ADR draft, updated architecture description. Use when the significance checklist says yes, when choosing stack or library that is hard to replace, or for the architecture kickoff.
when_to_use: design.md significance checklist has a yes; new module or boundary; data schema; API contract; hard-to-replace dependency; new cross-cutting concern; affects a Q- scenario; hard to reverse; "how should we structure"; stack choice.
---

# Architecture

Sources: `docs/pre-init/11-architecture-design.md`, `12-adr.md`. Do not rely on memory of theory: use the
definitions and questions below and name what you apply.

## 1. Significance checklist

New module or boundary · data schema · API contract · new dependency that is hard to replace · new
cross-cutting concern · affects a `Q-` scenario · hard to reverse. Any yes → this procedure. All no →
local decision in `design.md` with `Scope: local`.

## 2. Drivers

List the drivers of this decision with IDs: functional requirements `R-`, quality scenarios `Q-`
(source → stimulus → artifact → environment → response → **measurable** response measure), constraints
`C-`, cross-cutting concerns. A decision without a driver is not taken.

## 3. Known solutions first

Name the problem. Check, in order: the framework and its conventions; tactics by quality attribute
(availability: retries, timeouts, health checks; performance: caching, batching, indexing, pagination;
modifiability: encapsulation, dependency inversion, ports and adapters; security: input validation,
authorization at the boundary, least privilege); styles (layered, hexagonal / ports and adapters,
clean architecture); patterns (PoEAA: repository, service layer, data mapper, DTO; DDD: aggregate,
value object, bounded context; EIP for messaging). Record what fits and what was rejected and why.

## 4. Options and trade-offs

At least two real options. Table: option × quality attributes that matter here (+/−). Mark sensitivity
points (a decision strongly affects one attribute) and trade-off points (improves one attribute at the
expense of another). Recommend one, citing the principle, tactic or pattern applied.

Principle checks for each option:
- Single responsibility: does each unit have one reason to change?
- Coupling: how many modules change when this changes? Cohesion: do the unit's parts change together?
- Dependency rule: do dependencies point toward the domain? Any cycles?
- Information hiding: what implementation detail leaks through the interface?
- YAGNI: is any part there "for later"? Prefer reversible decisions now; defer irreversible ones to the
  last responsible moment.

Agent failure modes to avoid: wrappers around the framework; an abstraction without a second
implementation; unrequested functionality; a decision without a driver.

## 5. The human decides

Present drivers, options, trade-offs and the recommendation; wait for the decision.

## 6. ADR and updates (same change)

Draft `docs/adr/ADR-NNNN-<slug>.md` (English) with: Status `active`; Kind `architecture` / `process`;
Decided by: project owner; Drafted by: agent; Context and drivers; Considered options (≥2); Trade-offs
table; Decision and rationale (the only normative section); Consequences; Confirmation (test or
dependency rule that enforces it, or "review"); Retires (rules or wording it cancels); Revisit-when.
When asking to commit, say "this commit accepts ADR-NNNN-<slug>".

Update in the same change: `docs/architecture/README.md` (strategy, building blocks, diagrams in
Mermaid with a caption saying why the diagram exists), dependency rules, `C-ARCH-…` rules if any.
