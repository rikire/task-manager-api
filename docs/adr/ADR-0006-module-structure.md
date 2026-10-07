# ADR-0006: Module structure — modules Task and Status, vertical slices on a hexagonal core

- **Status:** active, 2026-10-07
- **Errata:** 2026-10-07 — the dependency table forbade `Persistence` the `symfony/uid` library that the
  Identifiers point requires; the vendor rule now follows ports and adapters: the core uses no vendor
  code, adapters may use any (owner's decision; found by the Deptrac rule test, change `architecture-kickoff`
  group 2; decision record 12 §4).
  2026-10-07 — the table let `Http` reach the database through `Symfony\Bridge\Doctrine\` and `PDO`, and
  left classes outside the modules unchecked, while `QAS-MAINT-layering` forbids HTTP entry points any
  database access; added the `Database` and `Unassigned` layers (owner's decision; found by the `verifier`
  subagent).
- **Amended:** 2026-10-07 — layer `Shared` (`src/Shared/Infrastructure/`) for infrastructure that serves
  both modules and is wired only by configuration; first class: the error `detail` normalizer of ADR-0005
  (owner's decision, change `status-catalog`).
  2026-10-07 — a read model returned by several slices of one module lives in that module's `Application/`
  outside the slices (`Status\Application\StatusView` serves list, get and create); one per slice would be
  three identical classes (owner's decision; found by the `verifier` subagent, change `status-catalog`).
- **Kind:** architecture
- **Decided by:** project owner (proposed the style; chose full hexagonal and two modules over the agent's
  lighter recommendation)
- **Drafted by:** agent

> Reading notes: "decision record NN" is `docs/pre-init/NN-*.md`. `CON-…` IDs are defined in
> `docs/constraints.md`, `QAS-…` in the `quality` spec (`openspec/specs/quality/spec.md`), `RUL-…` in
> `.claude/rules/code.md`. "The skeleton" is
> step 8 of `docs/pre-init/35-init-checklist.md`: the empty Symfony application running in Docker.

## Context and drivers

- `QAS-MAINT-layering` — HTTP entry points do not depend on the persistence layer or the database
  connection; no dependency cycles between layers.
- `CON-DELIV-readme-sections` — README explains "why this project structure".
- `CON-DELIV-explain-without-ai` — the owner explains the structure without an assistant; the owner
  proposed this style, which weighs for it.
- `CON-PLAN-deadline` — submission by 2026-10-09; context: two entities (Task, Status), ten endpoints
  (`docs/task/assignment.txt:21-95`), 6–10 hours estimated (line 13).
- `RUL-CODE-yagni` — no abstraction without a second real implementation; this ADR makes an explicit
  exception to it (Decision).
- ADR-0004 (Doctrine ORM), ADR-0005 (request DTOs, framework error rendering).

Known solutions considered (decision record 11): layered architecture; hexagonal architecture (ports and
adapters, Alistair Cockburn: the core defines interfaces — ports — and the outside world plugs in through
adapters); vertical slice architecture (code grouped by use case instead of by technical type, Jimmy
Bogard); modular monolith; Service Layer and Repository from Fowler's *Patterns of Enterprise Application
Architecture* (PoEAA). YAGNI — "you aren't gonna need it": build only what a current driver needs.

Facts (checked 2026-10-07):

| Fact | Source |
|---|---|
| Doctrine ORM 3.7.4 supports XML mapping; no deprecation notice | <https://www.doctrine-project.org/projects/doctrine-orm/en/stable/reference/xml-mapping.html> |
| Deptrac collects layers by `directory` (file path regex), `classNameRegex` and other collectors | <https://deptrac.github.io/deptrac/collectors/> |

## Considered options

1. **Layers by type with an application layer** — `Controller/`, `Application/`, `Dto/`, `Entity/`,
   `Repository/`; Doctrine entities are the domain model (the previous recommendation).
2. **Vertical slices without ports** — a folder per use case holding its controller, request DTO and
   handler; shared Doctrine entities and repositories.
3. **Vertical slices on a hexagonal core** — a domain free of frameworks with repository ports;
   application slices (one per use case) use the ports; adapters for HTTP and persistence.
4. **As 3, with a separate persistence model** — Doctrine entity classes next to the domain classes and
   mappers between them. Rejected before comparison: XML mapping (fact above) keeps the domain free of
   Doctrine without a second set of classes.

Modules: one module (one bounded context) / **two modules, Task and Status**. Two modules start with a
cycle — a task references its status; deleting a status needs to know whether tasks use it — which a
port in Status, implemented by Task, breaks (owner's choice, 2026-10-07).

## Trade-offs

| Attribute | 1. Layers by type | 2. Slices, no ports | 3. Slices + hexagonal |
|---|---|---|---|
| `QAS-MAINT-layering` | + | + | + and the domain is free of Doctrine and Symfony |
| Explain without assistant (`CON-DELIV-explain-without-ai`) | + Symfony layout | + one folder per use case | ± more concepts (ports, adapters, mapping), but the owner's own choice |
| README "why this structure" | ± | + | + clear story: core, use cases, adapters |
| Effort (`CON-PLAN-deadline`) | + least | + | − interfaces, XML mapping, domain exceptions mapped to HTTP |
| `RUL-CODE-yagni` | + | + | − ports with one adapter each (explicit exception) |
| Use cases independent of the delivery format | − handlers take HTTP DTOs | − | + handlers take commands; HTTP DTOs stay in the adapter |

Sensitivity point: whether the domain depends on Doctrine decides how much of the code a persistence
change touches. Trade-off point: option 3 buys a framework-free core and use cases independent of HTTP at
the cost of interfaces with one implementation and more files.

## Decision and rationale

Use **vertical slices on a hexagonal core** (option 3), in **two modules, Task and Status**:

```text
src/
  Status/
    Domain/                   Status, value objects, domain exceptions; ports: StatusRepository,
                              StatusUsage ("is this status used by any task?")
    Application/<UseCase>/    CreateStatus/, ListStatuses/, GetStatus/, DeleteStatus/ — command or query + handler
    Infrastructure/Http/<UseCase>/   single-action controller + request DTO
    Infrastructure/Persistence/      Doctrine adapter for StatusRepository; XML mapping
  Task/
    Domain/                   Task (references a Status), value objects, domain exceptions; port TaskRepository
    Application/<UseCase>/    CreateTask/, ListTasks/, GetTask/, DeleteTask/, ChangeTaskStatus/
    Infrastructure/Http/<UseCase>/
    Infrastructure/Persistence/      Doctrine adapter for TaskRepository, adapter implementing
                                     Status\Domain\StatusUsage; XML mapping
```

Dependency rules. App layers per module are collected by directory; vendor code by class name
(`classNameRegex`), because a directory collector over `src/` never sees `Doctrine\…` or `Symfony\…`, and
the check does not report dependencies on classes outside every layer by default:

| Vendor layer | Classes |
|---|---|
| `Database` | `Doctrine\` (ORM, DBAL, Persistence), `Symfony\Bridge\Doctrine\` (for example `#[MapEntity]`, which loads an entity from the database), `PDO` |
| `Vendor` | any other class outside `App\` that is not a PHP built-in (a `bool` collector) |
| `Unassigned` | any class in `src/` outside the modules and `src/Shared/Infrastructure/`, except `src/Kernel.php` — so that, for example, a controller in `src/Controller/` is still checked |

| App layer (in each module) | May depend on |
|---|---|
| `Domain` | nothing but PHP built-ins; Task's `Domain` may use Status's `Domain` |
| `Application` | its module's `Domain`; Task's may use Status's `Domain` (ports and values) |
| `Http` | its module's `Application` and `Domain` (exceptions and values); `Vendor` — never `Database` or `Persistence` |
| `Persistence` | its module's `Domain`, `Database`, `Vendor`; Task's may use Status's `Domain` (to implement `StatusUsage`) |
| `Unassigned` | nothing: code belongs in a module |
| `Shared` (`src/Shared/Infrastructure/`, outside the modules) | `Vendor` only — never a module, never `Database`; no module depends on `Shared`: it is wired by configuration (amended 2026-10-07) |

The core (`Domain`, `Application`) depends on nothing outside PHP; the adapters (`Http`, `Persistence`) may
use any third-party library — that is where infrastructure belongs in ports and adapters — with the one
directed restriction the quality scenario needs: `Http` never reaches persistence.

- **Between modules:** Task may depend on Status's `Domain` only; **Status never depends on Task**. The
  cycle is broken by the `StatusUsage` port: Status asks it before deleting; Task's persistence implements
  it.
- `Http` never depends on `Persistence` or the database (Doctrine, its Symfony bridge, `PDO`)
  (`QAS-MAINT-layering`); `Domain` and `Application`
  never depend on any vendor code. No cycles.
- **Identifiers:** UUID v7 for tasks and statuses (owner, 2026-10-07; v7 values grow with time, so
  inserts into the PostgreSQL index stay ordered). Each repository port has `nextId()`; the `Persistence`
  adapter generates the value with `symfony/uid` (dependency approved at skeleton time); the domain keeps
  it as its own value object (`TaskId`, `StatusId`) over a string, so `Domain` still depends on nothing but
  PHP. The id is known before the entity is saved.
- Domain classes carry no Doctrine attributes; mapping is XML in each module's `Persistence` (ADR-0004).
  Entities are not `final`, so Doctrine can create lazy-loading proxies for the Task → Status relation.
- A handler receives a command or query object, not the HTTP request DTO; the controller maps one to the
  other. Controllers call handlers directly (no command bus). Query handlers return read models defined in
  their slice (for example a task view with the status name), so Task's `Http` never needs Status's
  `Domain`; a read model that several slices of the module return lives in the module's `Application/`
  (amended 2026-10-07).
- Writes go through port methods (`save()`, `remove()`); the adapter flushes (`flush()` — Doctrine writes
  the collected changes to the database).
- A slice does not use another slice's classes; shared behaviour goes to its module's `Domain`. Checked in
  review (a generic per-slice dependency rule is not expressed in this ADR).
- **Exception to `RUL-CODE-yagni`:** the repository ports and `StatusUsage` have one implementation each;
  they keep the domain free of persistence and break the module cycle, by the owner's decision. The rule
  text gets "except the ports of ADR-0006" (owner, 2026-10-07).

Rationale: the owner chose the style and the module split (`CON-DELIV-explain-without-ai` favours what
the owner understands); the hexagonal core makes `QAS-MAINT-layering` hold by construction and gives README
a clear structure story (`CON-DELIV-readme-sections`); slices keep each use case in one place; the module
boundary is machine-checked, and the one port that breaks the cycle is the textbook use of dependency
inversion.

## Consequences

- Plus: a framework-free domain; use cases independent of HTTP; the structure is explainable in three
  lines.
- Minus: two strongly coupled aggregates in separate modules — one port (`StatusUsage`) and one allowed
  direction (Task → Status) to keep them apart.
- Minus: more files per use case; XML mapping instead of attributes; ports with a single adapter
  (accepted exception to `RUL-CODE-yagni`); domain exceptions must be mapped to HTTP codes — done with
  `framework.exceptions` in configuration, so `Domain` stays free of Symfony (ADR-0005).
- Minus: one flush per port call; a use case that writes several entities in one transaction needs a port
  method for it.

## Confirmation

- Dependency-rule check (Deptrac) in CI with the layers and module rule above: 0 violations
  (`QAS-MAINT-layering`).
- Fixture tests in group `ADR-0006-module-structure`, written first (change `architecture-kickoff`
  group 2): a class in an `Http` layer that uses Doctrine, and a class in `Status` that uses `Task`, each
  make the check report a violation.
- Amended 2026-10-07: fixture tests where a `Shared` class uses a module class, and a module class uses a
  `Shared` class, each make the check report a violation (change `status-catalog`).

## Retires

Nothing (`RUL-CODE-yagni` is amended with the exception above, not retired).

## Revisit-when

- A third feature area appears → a module with the same structure and an explicit allowed direction.
- The same rule is duplicated in two slices → move it into `Domain`.
