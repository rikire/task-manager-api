# SDD-фреймворки для AI-агентов (2025–2026): сравнение и критика

Дата сбора: 2026-10-03. Статистика GitHub (звёзды, лицензия, последний push/релиз) снята через `gh api` 2026-10-03 — это [П] по API GitHub; ссылки ведут на репозиторий.
Метки: [П] — открыт первоисточник; [В] — вторичный источник; [И] — моя интерпретация; (вендор) — утверждение производителя о себе.
Контекст пользователя: один разработчик + AI-агент, новый небольшой бэкенд, человек отвечает за результат и должен понимать весь код.

---

## 1. Классификация и обзор каждого фреймворка

### Takeaway
Рынок разделился на (а) «тяжёлые» фазовые конвейеры с гейтами (Spec Kit, Kiro, cc-sdd, BMAD, GSD), (б) лёгкие delta-ориентированные (OpenSpec), (в) «методологии из скиллов» без отдельного артефакта-спеки как контракта (Superpowers, Agent OS v3), (г) таск-менеджеры (Taskmaster) и (д) spec-as-source (Tessl), который к 2026 фактически ушёл от SDD в сторону реестра скиллов. Почти все — spec-first, с разной степенью spec-anchored; честного spec-as-source в production не найдено.

### Cited Findings

**Классификация Бёкелер (Böckeler)**
- [П] Статья «Understanding Spec-Driven-Development: Kiro, spec-kit, and Tessl», Birgitta Böckeler (Thoughtworks), 15.10.2025. Три уровня: *spec-first* (спека пишется до кода, используется в задаче, затем выбрасывается); *spec-anchored* — «The spec is kept even after the task is complete, to continue using it for evolution and maintenance»; *spec-as-source* — «The spec is the main source file over time, and only the spec is edited by the human, the human never touches the code». — [martinfowler.com](https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html)
- [П] Её оценка на момент статьи: Kiro — только spec-first; spec-kit — стремится к spec-anchored, но на практике «spec-first only, not spec-anchored over time»; Tessl — явно целится в spec-anchored и spec-as-source (маркирует код `// GENERATED FROM SPEC - DO NOT EDIT`, 1:1 соответствие спеки и файла кода, теги `@generate`/`@test`). — [martinfowler.com](https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html)

**GitHub Spec Kit**
- [П] Мейкер: GitHub; лицензия MIT; ~139 900 звёзд; последний релиз v1.1.0 от 2026-10-02 (первый релиз v0.1.0 был 2025-09-06; линия 0.x шла до v0.14.1 в июле 2026). — [github.com/github/spec-kit](https://github.com/github/spec-kit), [releases](https://github.com/github/spec-kit/releases)
- [П] Анонс: Den Delimarsky, github.blog, 02.09.2025; спеки как «living, executable artifacts», «This is a contract for how your code should behave and becomes the source of truth» (вендор). Рекомендуемые сценарии: greenfield, фичи в существующих системах, модернизация legacy; для брауфилда «advanced context engineering practices might be needed». — [github.blog](https://github.blog/2025-09-02-spec-driven-development-with-ai-get-started-with-a-new-open-source-toolkit/)
- [П] Текущий цикл (README, v1.x): установка `uv tool install specify-cli`, `specify init <proj> --integration <agent>`; требует Python 3.11+ и uv. Цикл: «Constitution once per project; specify → plan → tasks → implement → converge per feature»; команды-скиллы `/speckit-constitution`, `/speckit-specify`, `/speckit-plan`, `/speckit-tasks`, `/speckit-implement`, `/speckit-converge`; «Repeat implement → converge until convergence reports Converged»; опциональные гейты: clarification, checklists, consistency analysis. Отдельные расширения: bug fixing (`assess → fix → test`, отчёты в `.specify/bugs/<slug>/`, вердикт verified/partial/failed) и idea assessment (`.specify/assessments/`). Есть extensions/presets/workflows/bundles. — [README](https://github.com/github/spec-kit)
- [П] Артефакты: `specs/<feature>/spec.md`, `plan.md`, `tasks.md`; `.specify/memory/constitution.md`; `.specify/templates/`, `.specify/scripts/`. Эволюция спеки — три модели на выбор: Flow-Forward (каждая папка фичи — исторический документ, новые спеки для изменений), Living Spec (`spec.md` — контракт, `plan.md`/`tasks.md` перегенерируются), Flow-Back (открытия при реализации поднимаются вверх). Правило: «Do not leave a lower-level change in tasks.md or code if spec.md still says something different». — [evolving-specs guide](https://github.github.io/spec-kit/guides/evolving-specs.html)
- [П] Агенты: по умолчанию GitHub Copilot, ключи интеграций для других агентов (анонс 2025 — Copilot, Claude Code, Gemini CLI). — [README](https://github.com/github/spec-kit), [github.blog](https://github.blog/2025-09-02-spec-driven-development-with-ai-get-started-with-a-new-open-source-toolkit/)
- [И] По Бёкелер Spec Kit был spec-first (окт. 2025); к 2026 добавлены `converge` и гайд «evolving specs», т.е. появилась документированная поддержка spec-anchored, но это опция, а не гарантия.

**OpenSpec (Fission AI)**
- [П] Мейкер: Fission AI; MIT; ~70 950 звёзд; последний релиз v1.14.0 от 2026-09-30. Требует Node.js ≥20.19.0 (`npm i -g @fission-ai/openspec` или brew). — [github.com/Fission-AI/OpenSpec](https://github.com/Fission-AI/OpenSpec)
- [П] Философия (вендор): «fluid not rigid / iterative not waterfall / easy not complex / built for brownfield not just greenfield». Цикл по умолчанию: `/opsx:explore` (опц.) → `/opsx:propose <change>` → `/opsx:apply` → `/opsx:archive`; расширенный профиль: `/opsx:new`, `/opsx:continue`, `/opsx:ff`, `/opsx:verify`, `/opsx:bulk-archive`, `/opsx:onboard`. Поддержка «30+ tools». — [README](https://github.com/Fission-AI/OpenSpec)
- [П] Артефакты: `openspec/changes/<change>/` с `proposal.md`, `design.md`, `tasks.md`, `specs/<capability>/spec.md` (delta-спека); формат требований — `### Requirement:` + `#### Scenario:` с WHEN/THEN, ключевые слова SHALL. Delta-секции ADDED / MODIFIED / REMOVED Requirements; при archive дельты применяются к `openspec/specs/` (source of truth), изменение уходит в `changes/archive/YYYY-MM-DD-<name>/`. — [README](https://github.com/Fission-AI/OpenSpec), [docs/concepts.md](https://github.com/Fission-AI/OpenSpec/blob/main/docs/concepts.md)
- [П] Командная работа: «Stores» (beta) — планирование в отдельном репозитории, общие спеки для нескольких репо. — [README](https://github.com/Fission-AI/OpenSpec)
- [П] Самосравнение (вендор): Spec Kit «Thorough but heavyweight. Rigid phase gates, lots of Markdown, Python setup»; Kiro «locked into their IDE and limited to Claude models» (утверждение о моделях Kiro устарело — см. Kiro ниже). — [README](https://github.com/Fission-AI/OpenSpec)
- [И] По классификации Бёкелер OpenSpec — spec-anchored by design: главная спека живёт и обновляется через дельты при каждом изменении.

**AWS Kiro**
- [П] Продукт Amazon/AWS: проприетарная IDE (+CLI, Web); не open source (репозитория с кодом нет — [И] по отсутствию на GitHub, не проверено исчерпывающе). Spec = три файла: `requirements.md` (или `bugfix.md`), `design.md`, `tasks.md`, хранятся в `.kiro/specs/`. Варианты: Requirements-First, Design-First, Quick Spec (все три артефакта без гейтов одобрения), Bugfix Specs. Задачи исполняются «волнами»: «Waves execute sequentially; tasks within a wave execute concurrently». — [kiro.dev/docs/specs](https://kiro.dev/docs/specs/), [feature-specs](https://kiro.dev/docs/specs/feature-specs/)
- [П] Требования в нотации EARS: «WHEN [condition/event] THE SYSTEM SHALL [expected behavior]». — [feature-specs](https://kiro.dev/docs/specs/feature-specs/)
- [П] Steering: markdown в `.kiro/steering/` (или `~/.kiro/steering/` глобально), базовые `product.md`, `tech.md`, `structure.md`; режимы включения always / fileMatch / manual / auto; поддерживается AGENTS.md (всегда включается). — [kiro.dev/docs/steering](https://kiro.dev/docs/steering/)
- [П] Hooks: JSON в `.kiro/hooks/`, триггеры — сохранение/создание/удаление файлов, PreToolUse/PostToolUse, завершение агента, ручной запуск; действие — shell-команда или промпт агенту; применения — линтеры/тайпчек после правок, генерация тестов/доков, гейтинг инструментов. — [kiro.dev/docs/hooks](https://kiro.dev/docs/hooks/)
- [П] Биллинг: Free 50 кредитов, Pro 1 000, Pro+ 2 000, Pro Max 5 000, Power 10 000 в месяц. — [kiro.dev/docs/billing](https://kiro.dev/docs/billing/). [В] Цены $20 (Pro) … $200 (Power), overage $0.04/кредит; модели — Claude Opus/Sonnet/Haiku 4.x, а также Qwen3 Coder, DeepSeek v3.2, MiniMax. — [usagebar.com](https://usagebar.com/blog/kiro-pricing-and-free-tier) (цены и список моделей на первоисточнике не открыты)
- [П] Механизм синхронизации спеки с кодом после реализации на странице feature specs не описан. — [feature-specs](https://kiro.dev/docs/specs/feature-specs/)
- Расхождение: Бёкелер описывает требования Kiro как user stories «As a…» с критериями «GIVEN…WHEN…THEN» ([martinfowler.com](https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html)), документация Kiro — как EARS «WHEN … THE SYSTEM SHALL» ([kiro.dev](https://kiro.dev/docs/specs/feature-specs/)). [И] Вероятно, user stories + EARS-критерии сочетаются; пересказ статьи получен через summarizer — точную формулировку Бёкелер стоит перепроверить.

**BMAD Method**
- [П] Мейкер: BMad Code, LLC (Brian «BMad» Madison — имя [В], в репо указан только BMad Code, LLC); лицензия MIT (GitHub API показывает NOASSERTION, но LICENSE — MIT; товарные знаки отдельно в TRADEMARK.md); ~53 750 звёзд; релиз v6.12.0 от 2026-09-04, push 2026-10-03. — [github.com/bmad-code-org/BMAD-METHOD](https://github.com/bmad-code-org/BMAD-METHOD), [LICENSE](https://github.com/bmad-code-org/BMAD-METHOD/blob/main/LICENSE)
- [П] Позиционирование (вендор): «Agile Ai Driven Development»; установка через `npx skills add`, маркетплейсы Claude Code и Codex; требует uv; точки входа `bmad setup`, `bmad-build`, `bmad status`. «Right-sized process», «Specialized perspectives — product, architecture, UX, development, and testing». Модули: Builder, Test Architect (enterprise), Loop (автономная сборка эпика), Game Dev и др. — [README](https://github.com/bmad-code-org/BMAD-METHOD)
- [П] Четыре пути планирования: Trivial (Change → Edit → Verify), One-Session (Intent → Build → Result), Epic-Sized (Intent → Spec → Stories → Build), Project-Sized (Intent → Shared Contracts → Multiple Epics → Integrated Product). Артефакты: брифы/отчёты анализа, PRD/UX/spec (`*-<slug>.md`, `addendum.md`, `.memlog.md`), `architecture-<slug>.md`, `tickets.toml`; хранение в папках инициативы или `_bmad-output`. — [docs.bmad-method.org](https://docs.bmad-method.org/plan/choose-a-planning-path/)
- [П] Thoughtworks Radar vol. 34 называет BMAD примером «heavier alternatives that enforce more rigid workflows». — [Radar vol. 34 PDF](https://www.thoughtworks.com/content/dam/thoughtworks/documents/radar/2026/04/tr_technology_radar_vol_34_en.pdf)

**Tessl**
- [П] На 2026-10-03 главная tessl.io позиционирует продукт как «agent enablement platform»: Skills → Loops → Factory, «Build your software factory, one skill at a time»; Registry — реестр скиллов с security-сканированием и оценкой; free tier 1 000 кредитов; open-source предложения на странице нет; термин spec-as-source на главной не акцентируется. — [tessl.io](https://tessl.io/)
- [П] В сентябре–октябре 2025 Tessl Framework был в private beta (Radar и Бёкелер). — [Radar SDD blip](https://www.thoughtworks.com/radar/techniques/spec-driven-development), [martinfowler.com](https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html)
- [П] Radar vol. 34 упоминает Tessl уже как «internal or external skill registries like Tessl». — [Radar vol. 34 PDF](https://www.thoughtworks.com/content/dam/thoughtworks/documents/radar/2026/04/tr_technology_radar_vol_34_en.pdf)
- [В] «In early 2026, Tessl shifted its product focus from specifications to skills»; spec-as-source «as of mid-2026 … still mostly a thesis»; в реестре есть пакет «spec-as-source» как кастомная OpenSpec-схема с file-ownership и метаданными верификации тестами. — [codemyspec.com](https://codemyspec.com/blog/tessl-review), [tessl.io registry](https://tessl.io/registry/spec-driven-devlopment/spec-as-source/2.2.0)
- [И] Для оценки «spec-as-source как рабочего инструмента» Tessl в 2026 практически выпал: продукт сменил фокус.

**Другие**
- **cc-sdd** [П]: gotalab, MIT, ~3 700 звёзд, v3.1.0 от 2026-09-23. «Kiro-inspired», совместим со спеками Kiro. Цикл: `kiro-discovery` → `kiro-spec-init` → `kiro-spec-requirements` → `kiro-spec-design` → `kiro-spec-tasks` → `kiro-impl`; для брауфилда `kiro-steering` и опц. `kiro-validate-gap`; `kiro-spec-batch` для многоспечных инициатив; discovery может направить «implement directly with no spec». Артефакты: `brief.md`, `requirements.md` (EARS), `design.md` (с File Structure Plan), `tasks.md` с аннотациями `_Boundary:_`/`_Depends:_`. `kiro-impl`: на задачу — свежий субагент, TDD RED→GREEN за feature-flag, независимый ревьюер, auto-debug. 8 агентов (Claude Code и Codex — stable, остальные beta), 14 языков. Позиция (вендор): «Code remains the source of truth. Specs make the boundaries between parts of the code explicit». — [github.com/gotalab/cc-sdd](https://github.com/gotalab/cc-sdd)
- **Agent OS** [П]: Brian Casel / Builder Methods, MIT, ~5 470 звёзд, v3.0.0 от 2026-01-20. В v3 автор отказался от собственных фаз spec writing/task breakdown/orchestration: «Spec creation now defers to Plan Mode», «Implementation/orchestration phases retired—frontier models handle this well on their own now»; остались `/discover-standards`, `/inject-standards` (через `index.yml`), `/shape-spec` (вопросы к plan mode, сохранение плана в папку спек). — [README](https://github.com/buildermethods/agent-os), [CHANGELOG](https://github.com/buildermethods/agent-os/blob/main/CHANGELOG.md)
- **Taskmaster (task-master-ai)** [П]: Eyal Toledano, Ralph Khreish (сейчас под брендом Hamster); лицензия MIT + «Commons Clause» (запрет продажи — не OSI-open-source); ~28 100 звёзд; последний релиз 0.43.1 от 2026-03-31, последний push 2026-04-28. Работает как MCP-сервер/CLI; требует API-ключ провайдера для своих AI-команд (кроме режима через Claude Code/Codex CLI); модели main/research/fallback. — [github.com/eyaltoledano/claude-task-master](https://github.com/eyaltoledano/claude-task-master), [LICENSE](https://github.com/eyaltoledano/claude-task-master/blob/main/LICENSE). [И] Это скорее декомпозиция PRD в граф задач, чем SDD с живой спекой; конкретный цикл (parse-prd → tasks.json) в этой сессии по первоисточнику не проверен.
- **spec-workflow-mcp** [П]: Pimzino, GPL-3.0, ~4 300 звёзд, последний тег v0.0.28, последний коммит 2026-07-03; в README: «I HAVE TAKEN A SMALL BREAK FROM THIS REPO». MCP-сервер: Requirements → Design → Tasks, веб-дашборд и VS Code-расширение, approval workflow с ревизиями, implementation logs; хранение в `.spec-workflow/` (approvals/, steering/, specs). — [github.com/Pimzino/spec-workflow-mcp](https://github.com/Pimzino/spec-workflow-mcp)
- **PRP / context-engineering-intro** [П]: Cole Medin, MIT, ~13 900 звёзд, релизов нет, последний коммит 2026-03-16. Шаблон для Claude Code: `CLAUDE.md` (правила), `examples/`, `INITIAL.md` → `/generate-prp INITIAL.md` → `PRPs/<feature>.md` → `/execute-prp`; PRP включает шаги реализации с validation loops. Слоган (вендор): «Context Engineering is 10x better than prompt engineering and 100x better than vibe coding» — без данных. — [github.com/coleam00/context-engineering-intro](https://github.com/coleam00/context-engineering-intro)
- **Superpowers** [П]: Jesse Vincent / Prime Radiant, MIT, ~294 700 звёзд (самый звёздный в выборке), v6.4.2 от 2026-09-25; в официальном маркетплейсе Claude Code, Codex, Cursor и др. (15+ харнессов). Цикл: brainstorming (дизайн показывается «in chunks short enough to actually read», сохраняется design doc) → using-git-worktrees → writing-plans (задачи 2–5 минут с точными путями и кодом) → subagent-driven-development или executing-plans → test-driven-development («Deletes code written before tests») → requesting-code-review → finishing-a-development-branch. «Mandatory workflows, not suggestions». — [github.com/obra/superpowers](https://github.com/obra/superpowers), [анонс 09.10.2025](https://blog.fsck.com/2025/10/09/superpowers/)
- **GSD (get-shit-done)** [П]: исходный репо `gsd-build/get-shit-done` (бывш. glittercowboy, автор TÂCHES) — MIT, ~64 400 звёзд, **archived**; последний релиз v1.43.0-rc2 (2026-05-17). README: «GSD Has Moved … continues as GSD Core» в `open-gsd/gsd-core` (создан 2026-05-22, ~10 100 звёзд, MIT, v1.15.0 от 2026-09-26). Цикл на milestone: Discuss → Plan → Execute (параллельные волны, у каждого исполнителя «clean 200k-token context») → Verify → Ship; `/gsd-new-project` (greenfield), `/gsd-onboard` (брауфилд); много рантаймов (Claude Code, OpenCode, Codex, Copilot, Cursor, …). Борется с «context rot» (вендор). — [gsd-build/get-shit-done](https://github.com/gsd-build/get-shit-done), [open-gsd/gsd-core](https://github.com/open-gsd/gsd-core)
- **Traycer** — в этой сессии не исследован (см. Gaps).

### Inferences
- [И] Общая схема почти везде одна: «намерение → требования/спека → дизайн/план → задачи → реализация (+ проверка)». Различия — в (1) обязательности гейтов, (2) судьбе спеки после реализации, (3) единице изменения (фича целиком vs дельта), (4) встроенной верификации (TDD/ревью-субагенты/converge).
- [И] Явный тренд 2026: «тяжёлые» фреймворки сами облегчаются и признают, что модели/plan mode забрали часть их функций (Agent OS v3 явно; BMAD — «right-sized» пути с Trivial; cc-sdd — «implement directly with no spec»; Spec Kit — bugfix-процесс без SDD).
- [И] Звёзды отражают популярность/маркетинг, а не эффективность; Radar vol. 34 отдельно предупреждает о потоке инструментов «maintained by a single contributor working with a coding agent» и вопросе их устойчивости ([PDF](https://www.thoughtworks.com/content/dam/thoughtworks/documents/radar/2026/04/tr_technology_radar_vol_34_en.pdf)). Пример — архивирование GSD и пауза spec-workflow-mcp.

### Gaps
- Traycer (traycer.ai) не проверен: мейкер, модель (вероятно проприетарное расширение IDE), цикл — не верифицировано.
- Точный текущий список поддерживаемых агентов Spec Kit (страница integrations) не открыт.
- Kiro: цены в долларах и список моделей — только вторичный источник; механизм синхронизации спеки после реализации в доках не найден.
- Taskmaster: детальный цикл и формат хранения по первоисточнику не проверены в этой сессии.

---

## 2. Сильные и слабые стороны (с источниками)

### Takeaway
Сильные стороны, которые подтверждают и критики, и Radar: явное намерение до кода, общий контекст (constitution/steering/standards), выявление проблем раньше в брауфилде. Слабые стороны повторяются у независимых авторов: многословный markdown и бремя ревью, один тяжёлый процесс для задач любого размера, агент не следует спеке, контекст-слепота, неясность «кому писать PRD».

### Cited Findings
**Thoughtworks Technology Radar**
- [П] Техника «Spec-driven development» — кольцо **Assess**, ноябрь 2025 (на странице блипа: «not on the current edition»). Текст: «workflows remain elaborate and opinionated», инструменты «behave very differently depending on task size and type; some generate lengthy spec files that are hard to review», при генерации PRD/user stories «sometimes unclear who their intended user is». — [thoughtworks.com/radar](https://www.thoughtworks.com/radar/techniques/spec-driven-development). Расхождение: summarizer назвал том «Volume 34 (November 2025)», а PDF vol. 34 датирован апрелем 2026 ([PDF](https://www.thoughtworks.com/content/dam/thoughtworks/documents/radar/2026/04/tr_technology_radar_vol_34_en.pdf)); [И] ноябрь 2025 — это vol. 33.
- [П] Radar vol. 34 (апрель 2026): **OpenSpec — Assess (tools)**: «focus on spec deltas rather than defining a complete specification upfront, making it well-suited for existing systems»; Spec Kit и Superpowers «better suited to greenfield projects than brownfield ones»; BMAD — «heavier … more rigid workflows», Kiro — «vendor-specific IDE integrations»; совет «continue to monitor … native capabilities and re-evaluate the need for SDD tooling». — [PDF](https://www.thoughtworks.com/content/dam/thoughtworks/documents/radar/2026/04/tr_technology_radar_vol_34_en.pdf)
- [П] Radar vol. 34: **GitHub Spec Kit — Assess (languages & frameworks)**: команды экспериментируют «mostly in brownfield environments»; полезная constitution содержит scope, домен, версии, стандарты, структуру репо; проблемы — «instruction bloat» → «context rot», «unnecessary defensive checks and overly verbose markdown outputs that increased cognitive load»; помогало ограничение числа генерируемых md-файлов; «experienced engineers, particularly those with strong clean coding and architectural practices, tend to extract the most value». Также «Two broad camps»: минимальная структура + сильные агенты vs детальные спеки. — [PDF](https://www.thoughtworks.com/content/dam/thoughtworks/documents/radar/2026/04/tr_technology_radar_vol_34_en.pdf)
- [П] Radar vol. 34: **Superpowers — Assess**: структурированный процесс из скиллов (brainstorming, планирование, TDD red-green-refactor, root-cause debugging, code review). — [PDF](https://www.thoughtworks.com/content/dam/thoughtworks/documents/radar/2026/04/tr_technology_radar_vol_34_en.pdf)
- [П] Radar vol. 34: «semantic diffusion» — термины «spec-driven development and harness engineering are sometimes used inconsistently or overlap in meaning»; SDD-фреймворки (Spec Kit, OpenSpec) отнесены к «feedforward controls», в паре с «feedback sensors» (компиляторы, линтеры, тесты, mutation testing). — [PDF](https://www.thoughtworks.com/content/dam/thoughtworks/documents/radar/2026/04/tr_technology_radar_vol_34_en.pdf)

**Böckeler (martinfowler.com, 10.2025)**
- [П] «I'd rather review code than all these markdown files»; spec-kit «very verbose and tedious to review». Kiro на мелком баге — «like using a sledgehammer to crack a nut» (4 user stories, 16 acceptance criteria); spec-kit — overkill для истории в 3–5 поинтов. Ложный контроль: агент «ultimately not follow all the instructions», игнорировал заметки о существующих классах и создавал дубликаты. Путаница функциональной и технической спеки. Неясный целевой пользователь. — [martinfowler.com](https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html)

**Scott Logic (Colin Eberhardt, CTO, 26.11.2025)** — единственное найденное «замерное» сравнение (n=1, см. раздел 3)
- [П] Spec Kit: «a sea of markdown documents, long agent run-times and unexpected friction»; вывод — «I am a lot more productive without SDD, around ten times faster»; не считает это «viable process» в текущей форме. — [blog.scottlogic.com](https://blog.scottlogic.com/2025/11/26/putting-spec-kit-through-its-paces-radical-idea-or-reinvented-waterfall.html)

**Marmelab (François Zaninotto, 12.11.2025)**
- [П] Spec Kit на простой фиче (отображение даты): «8 files and 1,300 lines of text»; двойное ревью (спека и код); агент отметил верификацию выполненной без unit-тестов; «context blindness» — агенты ищут контекст текстовым поиском и пропускают существующие функции; при росте приложения спеки «miss the point more often and slow development». Альтернатива — итеративная «Natural Language Development». — [marmelab.com](https://marmelab.com/blog/2025/11/12/spec-driven-development-waterfall-strikes-back.html)

**По фреймворкам (сводно)**
- Spec Kit: + зрелость, большое сообщество, constitution, расширения (bugfix/assess), converge-проверка [П] [README](https://github.com/github/spec-kit); − многословность и время ревью [П] [Scott Logic](https://blog.scottlogic.com/2025/11/26/putting-spec-kit-through-its-paces-radical-idea-or-reinvented-waterfall.html), [Marmelab](https://marmelab.com/blog/2025/11/12/spec-driven-development-waterfall-strikes-back.html), [Radar 34](https://www.thoughtworks.com/content/dam/thoughtworks/documents/radar/2026/04/tr_technology_radar_vol_34_en.pdf); зависимость от Python/uv [П] [README](https://github.com/github/spec-kit).
- OpenSpec: + дельты, брауфилд, лёгкость, tool-agnostic [П] [Radar 34](https://www.thoughtworks.com/content/dam/thoughtworks/documents/radar/2026/04/tr_technology_radar_vol_34_en.pdf); − [И] меньше встроенной проверки (verify опционален в расширенном профиле [П] [README](https://github.com/Fission-AI/OpenSpec)); Stores — beta.
- Kiro: + интеграция спек/steering/hooks/волн в одном продукте, EARS-требования тестопригодны (вендор) [П] [kiro.dev](https://kiro.dev/docs/specs/feature-specs/); − привязка к IDE/вендору, кредитная модель [П] [billing](https://kiro.dev/docs/billing/), [Radar 34](https://www.thoughtworks.com/content/dam/thoughtworks/documents/radar/2026/04/tr_technology_radar_vol_34_en.pdf); «sledgehammer» на мелочах [П] [Böckeler](https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html).
- BMAD: + ролевые перспективы, пути от Trivial до Project-sized [П] [docs](https://docs.bmad-method.org/plan/choose-a-planning-path/); − «heavier… rigid» [П] [Radar 34](https://www.thoughtworks.com/content/dam/thoughtworks/documents/radar/2026/04/tr_technology_radar_vol_34_en.pdf).
- Superpowers: + встроенные TDD, ревью, verification-before-completion, worktrees; дизайн показывается короткими кусками [П] [README](https://github.com/obra/superpowers); − «Mandatory workflows», больше подходит greenfield (по Radar) [П]; спека — design doc, а не долговременный контракт [И].
- cc-sdd: + boundary-first, TDD + независимое ревью на задачу, совместимость с Kiro-спеками без IDE [П] [README](https://github.com/gotalab/cc-sdd); − малое сообщество, большинство интеграций beta [П].
- GSD: + чистые контексты исполнителей, verify-фаза [П] [gsd-core](https://github.com/open-gsd/gsd-core); − переезд/архивирование в 2026, риск устойчивости [П] [gsd-build](https://github.com/gsd-build/get-shit-done).
- Tessl: + единственный, кто целился в spec-as-source [П] [Böckeler](https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html); − продукт сменил фокус на скиллы [П] [tessl.io](https://tessl.io/).

### Inferences
- [И] Слабости носят структурный характер (объём текста растёт быстрее объёма кода; агент нередко не исполняет спеку), а не баги конкретного инструмента — они повторяются у независимых авторов на разных инструментах.
- [И] Сильная сторона, которую подтверждают даже скептики: «спека» как явный, проверяемый человеком контракт перед кодом и как переносимый контекст между сессиями. Спор идёт о масштабе и обязательности, а не о самой идее.

### Gaps
- Обсуждения на HN найти через поиск не удалось (поиск вернул вторичные обзоры, а не треды news.ycombinator.com) — пруфов с HN нет.
- Систематических независимых обзоров OpenSpec, BMAD, GSD, cc-sdd (кроме Radar) не найдено.

---

## 3. Соответствие масштабу и измеренные данные об эффективности

### Takeaway
Контролируемых измерений «SDD vs без SDD» по времени и дефектам при разработке фич **нет**. Есть: один практический эксперимент n=1 (Scott Logic, против SDD), одно рецензируемое исследование узкой задачи (генерация тестов из контрактов, в пользу «спеки» как скаффолда), одно улучшение внутри SDD-конвейера и корпус артефактов без метрик эффективности. По масштабу источники сходятся: тяжёлый процесс оправдывается для крупных/многокомандных/брауфилд-изменений, а для мелких задач он избыточен.

### Cited Findings
**Измерения**
- [П] Scott Logic (n=1, один автор, один проект, Spec Kit, 11.2025): фича 1 — 33 мин 30 с работы агента + 3,5 ч ревью, 689 строк кода + 2 577 строк markdown, 1 баг; фича 2 — 23 мин 30 с + 2 ч ревью, ~300 строк кода + 2 262 строки markdown; итеративный подход без SDD — 8 мин агента + 15 мин ревью + 9 мин тестирования, ~1 000 строк кода, 0 багов. — [blog.scottlogic.com](https://blog.scottlogic.com/2025/11/26/putting-spec-kit-through-its-paces-radical-idea-or-reinvented-waterfall.html). [И] Не контролируемый эксперимент: разные фичи, один исполнитель, автор заранее знал задачу.
- [П] Tufano и др., «Grounding AI Agents in Contracts: An Empirical Evaluation of Spec-Driven Test Generation», SpecOps 2026 (SPLASH), arXiv 08.2026: агент сначала документирует pre/post-conditions и undefined behaviour, затем генерирует тесты; vs baseline +9,8 п.п. bug detection rate (p=0.0352), +2,5 п.п. branch coverage (p=0.0034); по LLM-as-judge лучше baseline в 77,8% случаев, лучше человеческих тестов в 56,7%. — [arXiv 2608.17177](https://arxiv.org/abs/2608.17177). [И] Это про генерацию тестов к существующему коду, не про SDD-фреймворки для фич.
- [П] Taghavi, Bhavani, «Spec Kit Agents: Context-Grounded Agentic Workflows», arXiv 04.2026: context-grounding hooks внутри SDD-конвейера дают +0,15 по 1–5 LLM-as-judge (p<0.05), 99,7–100% совместимость с тестами репо, SWE-bench Lite 58,2% Pass@1 (+1,7%). Сравнения SDD vs без SDD нет. — [arXiv 2604.05278](https://arxiv.org/abs/2604.05278)
- [П] Agarwal, Singhal, Breaux, Vasilescu, «SpecMine», arXiv 25.08.2026: корпус ~470 795 spec-файлов в 73 030 репозиториях от 17 инструментов (+98 574 файла в 12 910 репо отдельного инструмента), 5 992 PR в 581 репо, 2,42 млн типизированных ссылок спека↔код. Метрик эффективности в аннотации нет. — [arXiv 2608.25202](https://arxiv.org/abs/2608.25202)
- [П] arXiv 2602.00180 («Spec-Driven Development: …») — обзорная/практическая статья с иллюстративными кейсами, без количественных данных. — [arXiv 2602.00180](https://arxiv.org/abs/2602.00180)

**Масштаб**
- [П] Radar vol. 34: наибольшую пользу извлекают опытные инженеры с сильными архитектурными практиками; Spec Kit в брауфилде помогает раньше выявлять неясные намерения и скрытые ограничения. — [PDF](https://www.thoughtworks.com/content/dam/thoughtworks/documents/radar/2026/04/tr_technology_radar_vol_34_en.pdf)
- [П] Böckeler: для маленьких задач тяжёлые процессы избыточны; неясно, подходит ли SDD для крупных неясных фич, где нужны продуктовые навыки. — [martinfowler.com](https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html)
- [П] Вендоры сами вводят масштабирование: BMAD — Trivial / One-session / Epic / Project ([docs](https://docs.bmad-method.org/plan/choose-a-planning-path/)); cc-sdd — «implement a small change with no spec» ([README](https://github.com/gotalab/cc-sdd)); OpenSpec — «Solo… keeps you and your AI honest», для команд Stores ([README](https://github.com/Fission-AI/OpenSpec)); cc-sdd позиционируется на «AI-driven development at team scale» ([README](https://github.com/gotalab/cc-sdd)).

### Inferences
- [И] Соло-разработчик, небольшой бэкенд: основная стоимость SDD — время человека на чтение markdown (в замере Scott Logic ревью занимало часы против минут работы агента). Для условия «человек должен понимать весь код» это двойная нагрузка: понимать нужно и спеки, и код. Более лёгкие варианты (дельта-спеки OpenSpec, короткие дизайн-куски Superpowers, plan mode + стандарты Agent OS v3) прямо нацелены на снижение этой нагрузки; тяжёлые конвейеры (Spec Kit в полном виде, BMAD Epic/Project, Kiro Requirements-First) рассчитаны на фичи крупнее одной сессии.
- [И] Команда: ценность растёт там, где спека — средство коммуникации между людьми (кросс-репо, общие требования) — тут сильны OpenSpec Stores, BMAD, cc-sdd spec-batch.
- [И] Enterprise: гейты одобрения, трассируемость, ролевые агенты (BMAD Test Architect, spec-workflow approvals, Kiro hooks) — но доказательств выигрыша нет, только аргументы.

### Gaps
- Нет ни одного найденного контролируемого исследования (RCT или квазиэксперимент) «SDD-фреймворк vs итеративная работа с агентом» по времени разработки или числу дефектов. Это стоит прямо указать в отчёте.
- Нет данных от вендоров с методологией (вендорских цифр эффективности тоже не встречено в открытых README).

---

## 4. Критика SDD и тяжёлых процессов

### Takeaway
Критика сводится к пяти тезисам: (1) возрождение waterfall — предположение, что при реализации ничего нового не узнаешь; (2) бремя ревью markdown, которое превышает ревью кода; (3) дрейф спеки от кода и ложное чувство контроля (агент не исполняет спеку); (4) недетерминизм генерации подрывает идею «спека → код»; (5) повторение истории MDD — «инфлексибельность и недетерминизм одновременно». Защитники отвечают, что SDD с агентом — это минутные циклы и изменяемые спеки, а не замороженные фазы.

### Cited Findings
- [П] Kent Beck (через Fowler Fragments, 08.01.2026): «The descriptions of Spec-Driven development that I have seen emphasize writing the whole specification before implementation. This encodes the (to me bizarre) assumption that you aren't going to learn anything during implementation that would change the specification.» Фаулер подчёркивает feedback как ключевую ценность XP. — [martinfowler.com/fragments](https://martinfowler.com/fragments/2026-01-08.html)
- [П] Böckeler: параллель spec-as-source с Model-Driven Development; риск получить «Inflexibility _and_ non-determinism»; вопрос, не является ли это «Verschlimmbesserung» (ухудшение под видом улучшения). — [martinfowler.com](https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html)
- [П] Scott Logic: «Radical Idea or Reinvented Waterfall?»; часы ревью markdown без «qualitative benefit». — [blog.scottlogic.com](https://blog.scottlogic.com/2025/11/26/putting-spec-kit-through-its-paces-radical-idea-or-reinvented-waterfall.html)
- [П] Marmelab: «The Waterfall Strikes Back»; двойное ревью, «hunting for basic mistakes hidden in overly verbose, expert-sounding prose». — [marmelab.com](https://marmelab.com/blog/2025/11/12/spec-driven-development-waterfall-strikes-back.html)
- [П] Radar (11.2025): «handcrafting detailed rules for AI» может не масштабироваться; длинные трудные для ревью спеки. — [thoughtworks.com](https://www.thoughtworks.com/radar/techniques/spec-driven-development). Radar vol. 34: «instruction bloat» → «context rot»; рекомендуется пересматривать нужность SDD-тулинга по мере роста возможностей агентов. — [PDF](https://www.thoughtworks.com/content/dam/thoughtworks/documents/radar/2026/04/tr_technology_radar_vol_34_en.pdf)
- [П] Дрейф спеки: Бёкелер видела spec-kit и Kiro как spec-first, без поддержания спек со временем ([martinfowler.com](https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html)); Spec Kit сам предупреждает о риске («Do not leave a lower-level change … if spec.md still says something different») ([evolving-specs](https://github.github.io/spec-kit/guides/evolving-specs.html)); Radar vol. 34 про «executable documentation» как средство раньше заметить устаревание ([PDF](https://www.thoughtworks.com/content/dam/thoughtworks/documents/radar/2026/04/tr_technology_radar_vol_34_en.pdf)).
- [П] Неисполнение спеки агентом: Бёкелер (дубликаты классов), Marmelab (верификация без тестов). — [martinfowler.com](https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html), [marmelab.com](https://marmelab.com/blog/2025/11/12/spec-driven-development-waterfall-strikes-back.html)
- [П] Самокритика вендоров: Agent OS v3 убрал собственные фазы spec/tasks/orchestration — «frontier models handle this well on their own now». — [CHANGELOG](https://github.com/buildermethods/agent-os/blob/main/CHANGELOG.md)
- [В] Контраргумент: SDD отличается от waterfall изменяемостью спеки при реализации, а цикл с агентом занимает минуты; хорошая спека фичи «should fit on one screen». — [augmentcode.com](https://www.augmentcode.com/guides/spec-driven-development-vs-waterfall) (вендор AI-инструмента), [yuvalyeret.com](https://yuvalyeret.com/blog/spec-driven-development-isnt-waterfall-unless-youre-using-it-that-way/), [rogerwong.me](https://rogerwong.me/2026/03/spec-driven-development)

### Inferences
- [И] Тезис о waterfall применим к фреймворкам, которые требуют полной спеки до кода (полный цикл Spec Kit, Requirements-First Kiro, BMAD Project-sized); в меньшей степени — к delta-подходу (OpenSpec) и к процессам с TDD/верификацией внутри цикла (Superpowers, cc-sdd `kiro-impl`, GSD Verify, Spec Kit `converge`).
- [И] Недетерминизм: в spec-first/spec-anchored код остаётся источником истины и проверяется тестами, поэтому недетерминизм генерации — проблема качества, а не воспроизводимости. В spec-as-source (Tessl) он становится фундаментальным: одна и та же спека даёт разный код при перегенерации. Отказ Tessl от этого направления согласуется с прогнозом Бёкелер о параллели с MDD, но причинность не доказана.
- [И] «Verschlimmbesserung» для соло-разработчика: при условии «человек понимает весь код» лишний слой markdown, который тоже надо понимать и держать актуальным, может ухудшить, а не улучшить контроль, если не ограничивать объём спек.

### Gaps
- Исторические данные о провалах MDD/MDA (старше 2025) в этой сессии не собирались; параллель приводится только по Бёкелер.
- Цитаты с HN не найдены.

---

## 5. Сравнительная таблица

### Takeaway
Таблица ниже — для выбора по критериям, без победителя. Данные GitHub на 2026-10-03.

### Cited Findings
| Критерий | Spec Kit | OpenSpec | Kiro | BMAD | Tessl | cc-sdd | Superpowers | GSD Core | Agent OS v3 | Taskmaster | spec-workflow-mcp | PRP |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Мейкер | GitHub | Fission AI | AWS | BMad Code LLC | Tessl | gotalab | Jesse Vincent / Prime Radiant | open-gsd (ex TÂCHES) | Builder Methods (B. Casel) | E. Toledano, R. Khreish (Hamster) | Pimzino | Cole Medin |
| Лицензия | MIT | MIT | проприетарный | MIT (+TM) | проприетарный SaaS | MIT | MIT | MIT | MIT | MIT + Commons Clause | GPL-3.0 | MIT |
| Версия / последний релиз | v1.1.0, 2026-10-02 | v1.14.0, 2026-09-30 | SaaS/IDE | v6.12.0, 2026-09-04 | платформа скиллов | v3.1.0, 2026-09-23 | v6.4.2, 2026-09-25 | v1.15.0, 2026-09-26 | v3.0.0, 2026-01-20 | 0.43.1, 2026-03-31 | v0.0.28; коммит 2026-07-03, пауза | без релизов; коммит 2026-03-16 |
| Звёзды | ~139,9k | ~71,0k | — | ~53,8k | — | ~3,7k | ~294,7k | ~10,1k (+64,4k у архивного) | ~5,5k | ~28,1k | ~4,3k | ~13,9k |
| Цикл | constitution → specify → plan → tasks → implement ⇄ converge | (explore) → propose → apply → archive | requirements/bugfix → design → tasks (волны) | Trivial / One-session / Epic / Project | (было) spec → generate → test | discovery → init → requirements → design → tasks → impl | brainstorm → worktree → plan → subagent impl + TDD → review → finish | discuss → plan → execute → verify → ship | standards + shape-spec поверх plan mode | PRD → граф задач (не проверено) | requirements → design → tasks с одобрениями | INITIAL → generate-prp → execute-prp |
| Артефакты / где | `specs/<f>/spec,plan,tasks.md`, `.specify/` | `openspec/specs/`, `openspec/changes/<c>/` (+archive) | `.kiro/specs/`, `.kiro/steering/`, `.kiro/hooks/` | `_bmad-output` / папки инициатив; PRD, architecture, tickets.toml | реестр | `.kiro/`-совместимые requirements (EARS), design, tasks, brief | design doc, plan | файлы фаз/milestone (детали не проверены) | стандарты + `index.yml`, папка спек | tasks (MCP) | `.spec-workflow/` | `PRPs/`, `examples/`, `CLAUDE.md` |
| Тип по Бёкелер [И] | spec-first → опц. spec-anchored | spec-anchored (дельты) | spec-first (по Бёкелер) | spec-first/anchored (PRD/architecture живут) | spec-as-source (заявлено) | spec-anchored, «code is source of truth» | spec-first | spec-first | spec-first | не SDD в строгом смысле | spec-first | spec-first |
| Брауфилд | гайд existing-projects; Radar: чаще greenfield | основной фокус | да (steering) | да (existing codebase guide) | — | steering + validate-gap | Radar: скорее greenfield | `/gsd-onboard` | discover-standards | да | да | ограниченно |
| Работа с изменениями | новые спеки / living spec / flow-back | ADDED/MODIFIED/REMOVED дельты | новые спеки; синхронизация не описана | correct-course через пути | перегенерация | extend existing spec через discovery | новый цикл brainstorm | новая фаза | новый shape-spec | обновление задач | ревизии через approvals | новый PRP |
| Верификация | converge, checklists, analyze; bugfix-вердикты | `/opsx:verify` (расширенный профиль) | bugfix-регрессии, hooks | Test Architect модуль | `@test` теги | TDD RED→GREEN + независимый ревьюер | TDD обязательный + 2-этапное ревью | Verify-фаза | — | — | approval gates, логи | validation loops |
| Агенты | Copilot по умолч. + много интеграций | 30+ | только Kiro (IDE/CLI/Web) | skills-хосты, Claude Code, Codex | — | 8 (2 stable) | 15+ харнессов | 10+ рантаймов | Claude Code, Cursor, Antigravity и др. | MCP-клиенты | MCP-клиенты | Claude Code |
| Накладные расходы [И] | высокие (Python/uv, много md) | низкие–средние | средние + вендор/кредиты | средние–высокие | — | средние–высокие | средние (много субагентов, токены) | средние–высокие | низкие | средние (API-ключи) | средние (дашборд) | низкие |

Источники для строк таблицы — см. разделы 1–2: [Spec Kit](https://github.com/github/spec-kit), [OpenSpec](https://github.com/Fission-AI/OpenSpec), [Kiro docs](https://kiro.dev/docs/specs/), [BMAD](https://github.com/bmad-code-org/BMAD-METHOD), [Tessl](https://tessl.io/), [cc-sdd](https://github.com/gotalab/cc-sdd), [Superpowers](https://github.com/obra/superpowers), [GSD Core](https://github.com/open-gsd/gsd-core), [Agent OS](https://github.com/buildermethods/agent-os), [Taskmaster](https://github.com/eyaltoledano/claude-task-master), [spec-workflow-mcp](https://github.com/Pimzino/spec-workflow-mcp), [PRP](https://github.com/coleam00/context-engineering-intro), [Radar 34](https://www.thoughtworks.com/content/dam/thoughtworks/documents/radar/2026/04/tr_technology_radar_vol_34_en.pdf).

### Inferences
- [И] Критерии, которые для заданного контекста (соло, новый небольшой бэкенд, полное понимание кода) различают варианты сильнее всего: объём markdown на единицу кода; судьба спеки после реализации (выбрасывается / живёт как дельта / является источником); встроенная проверка (TDD, ревью, converge), а не только планирование; привязка к вендору/IDE; устойчивость проекта (bus factor, архивирование).
- [И] «Тип по Бёкелер» для фреймворков, кроме Kiro, Spec Kit и Tessl, — моя классификация, а не её.

### Gaps
- Колонки «работа с изменениями» для GSD/BMAD/Taskmaster заполнены по README-уровню; детальные доки не открывались.
- Колонка Traycer отсутствует (не исследован).

---

## Сомнительное / не проверено
- Пересказ Kiro у Бёкелер («As a…», GIVEN/WHEN/THEN) расходится с документацией Kiro (EARS); пересказ получен через summarizer — дословно не проверен.
- Номер тома Radar для блипа SDD: страница блипа (через summarizer) — «Volume 34, November 2025»; PDF vol. 34 датирован апрелем 2026. Скорее всего блип SDD — vol. 33 (ноябрь 2025); не проверено напрямую.
- Цены Kiro в долларах и список моделей — только вторичный источник ([usagebar.com](https://usagebar.com/blog/kiro-pricing-and-free-tier)).
- Утверждение OpenSpec, что Kiro «limited to Claude models», — по вторичным данным устарело (есть Qwen, DeepSeek, MiniMax); на первоисточнике не проверено.
- «Tessl shifted its product focus from specifications to skills in early 2026» — дата сдвига из вторичного обзора ([codemyspec.com](https://codemyspec.com/blog/tessl-review)); факт текущего позиционирования подтверждён главной tessl.io, дата — нет.
- Слоган PRP «10x better than prompt engineering and 100x better than vibe coding» — маркетинг без данных.
- «Spec Kit crossed 115,000 stars by June 2026» ([codemyspec.com](https://codemyspec.com/blog/openspec-vs-spec-kit)) — не проверено; на 2026-10-03 по API ~139,9k.
- Утверждение из вторичного источника, что Beck и Fowler в январе 2026 «criticized SDD» вместе, — первоисточник содержит цитату Бека в Fragments Фаулера; совместной позиции Фаулера в явном виде не видно.
- Цифры Scott Logic — один разработчик, разные фичи, не контролируемый эксперимент; нельзя обобщать как «SDD в 10 раз медленнее».
