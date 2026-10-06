# Блок 1. Ландшафт моделей агентной разработки и кандидаты на таксономию «process areas» (2025–2026)

Дата сбора: 2026-10-03. Контекст брифа: один разработчик + AI-агент, новый небольшой backend-сервис; человек владеет результатом и должен понимать и уметь объяснить весь код без AI. Язык и фреймворк не важны.

Маркировка: [П] — сверено с первоисточником (страница открыта мной); [В] — вторичный источник (пересказ, СМИ, поисковый сниппет); [И] — моя интерпретация. (вендор) — источник написан вендором инструмента, его данные и рекомендации заинтересованы. (старше 2025) — источник опубликован до 2025 года.

Ограничение метода: около 30 вызовов поиска и чтения. Пост OpenAI о harness engineering напрямую не открылся (HTTP 403), поэтому всё о нём помечено [В]. Пересказы страниц делала модель-суммаризатор инструмента WebFetch, так что «точные цитаты» ниже взяты из её вывода; дословность сверена только там, где страница пришла целиком (документация Claude Code).

---

## Вопрос 1. Какие модели и практики существуют (context engineering, harness engineering, SDD, AI-native SDLC, vibe coding и agentic engineering, compound engineering и др.)

### Takeaway
За 2025–2026 годы сложился быстро меняющийся словарь из нескольких перекрывающихся «рамок». Почти все они сходятся в одном: человек перестаёт писать код и переходит к управлению средой агента — контекстом, ограничениями, проверками — и к приёмке результата. Различаются они единицей анализа: окно контекста, обвязка (harness) вокруг агента, артефакт спецификации, жизненный цикл, петля накопления знаний. Ни одна рамка не стала стандартом; термины плавают, и Thoughtworks прямо называет это «semantic diffusion».

### Cited Findings

**Vibe coding → vibe engineering → agentic engineering (эволюция терминов)**
- Термин «vibe coding» ввёл Андрей Карпати в феврале 2025 года. Уиллисон определяет его как «unreviewed, prototype-quality LLM-generated code» [П] — [Willison, What is agentic engineering](https://simonwillison.net/guides/agentic-engineering-patterns/what-is-agentic-engineering).
- Kent Beck, «Augmented coding» (25.06.2025). Противопоставление: при vibe coding «you don't care about the code, just the behavior of the system. If there's an error, you feed it back into the genie»; при augmented coding «you care about the code, its complexity, the tests, & their coverage» [П] — [Beck, Augmented Coding: Beyond the Vibes](https://newsletter.kentbeck.com/p/augmented-coding-beyond-the-vibes).
  - Что Бек делает на практике: TDD в цикле Red → Green → Refactor; файл `plan.md`, по которому агент реализует по одному непомеченному тесту за раз; человек следит за промежуточными результатами. Сигналы того, что агент сбился [П]: (1) зацикливание; (2) функциональность, о которой не просили; (3) манипуляции с тестами — их отключают или удаляют. Агент недооценивал простоту и структурное качество кода, хотя ему об этом говорили [П] (там же).
- Simon Willison, «Vibe engineering» (07.10.2025). Определение: как «seasoned professionals accelerate their work with LLMs while staying proudly and confidently accountable for the software they produce» [П] — [Willison, Vibe engineering](https://simonwillison.net/2025/Oct/7/vibe-engineering/). Он перечисляет 11 практик, которые «вознаграждаются» агентами [П] (там же):
  1. автотесты;
  2. предварительное планирование;
  3. документация;
  4. привычки работы с git;
  5. CI и линтеры;
  6. культура code review;
  7. навык ручного QA;
  8. исследование вариантов решения до реализации;
  9. preview-окружения;
  10. интуиция о том, что можно отдать агенту;
  11. пересмотр оценок сроков.
- Karpathy, февраль 2026: «agentic engineering». Цитата: «Agentic, because the new default is that you are not writing the code directly 99% of the time, you are orchestrating agents who do and acting as oversight — engineering, to emphasize that there is an art and science and expertise to it» [В] — [The New Stack](https://thenewstack.io/vibe-coding-is-passe-karpathy-has-a-new-name-for-the-future-of-software/), [AOL/BI](https://www.aol.com/articles/man-coined-vibe-coding-says-203029543.html). Первоисточник (пост в X) я не открывал.
- Willison ведёт гайд «Agentic Engineering Patterns» с февраля 2026 года — [анонс 23.02.2026](https://simonwillison.net/2026/feb/23/agentic-engineering-patterns) [В]. Определение: «the practice of developing software with the assistance of coding agents»; «Writing code has never been the sole activity of a software engineer» [П] — [гайд](https://simonwillison.net/guides/agentic-engineering-patterns/what-is-agentic-engineering).
  - Разделы гайда: «Writing code is cheap now»; анти-паттерны, в том числе непросмотренный код; Git с агентами; Subagents; Red/green TDD; «First run the tests»; Agentic manual testing; **«Understanding code»: Linear walkthroughs, Interactive explanations** [В] — [оглавление гайда](https://simonwillison.net/guides/agentic-engineering-patterns/).
  - [И] Раздел «Understanding code» прямо отвечает на требование брифа «человек должен понимать и объяснять код».

**Context engineering**
- Anthropic (вендор), «Effective context engineering for AI agents», 29.09.2025; авторы Rajasekaran, Dixon, Ryan, Hadfield [П] — [Anthropic Engineering](https://www.anthropic.com/engineering/effective-context-engineering-for-ai-agents).
  - Определение: «curating and maintaining the optimal set of tokens (information) during LLM inference», в отличие от prompt engineering [П].
  - Понятия «context rot» (с ростом числа токенов падает точность извлечения информации) и «attention budget» [П].
  - Техники [П]:
    - compaction;
    - structured note-taking (внешняя память агента);
    - sub-agent architectures, которые возвращают «condensed, distilled summary»;
    - just-in-time retrieval по лёгким идентификаторам (пути к файлам, ссылки);
    - дизайн инструментов: «self-contained, robust to error, and extremely clear».
- Birgitta Böckeler (Thoughtworks), «Context Engineering for Coding Agents», martinfowler.com, 05.02.2026 [П] — [martinfowler.com](https://martinfowler.com/articles/exploring-gen-ai/context-engineering-coding-agents.html).
  - Определение (цитирует Bharani Subramaniam): «Context engineering is curating what the model sees so that you get a better result» [П].
  - Классификация конфигурации контекста [П]:
    - reusable prompts — instructions и guidance/rules;
    - context interfaces — tools, MCP-серверы, skills;
    - файлы рабочей области.
  - Ось «кто решает загрузить контекст» [П]: LLM (меньше контроля), человек (больше контроля), детерминированное ПО агента (самое предсказуемое — hooks).
  - Оговорка: «As long as LLMs are involved, we can never be certain of anything» [П].
- Thoughtworks Radar vol. 34 (апрель 2026): «Context engineering» в кольце **Adopt** [П] — [thoughtworks.com/radar](https://www.thoughtworks.com/radar).
- DORA (Google, вендор) в описании capability «AI-accessible internal data» называет переход «от prompt engineering к context engineering» [В] — [Techstrong.ai](https://techstrong.ai/features/ai-doesnt-fix-whats-already-broken-what-doras-new-model-tells-us-about-getting-ai-right/).

**Harness engineering**
- OpenAI (вендор), «Harness engineering: leveraging Codex in an agent-first world», Ryan Lopopolo, 11.02.2026 [В] — [openai.com/index/harness-engineering](https://www.openai.com/index/harness-engineering) (HTTP 403 при открытии); пересказы: [LetsDataScience](https://letsdatascience.com/news/openai-introduces-harness-engineering-for-automated-developm-703d90b6), [InfoQ JP](https://www.infoq.com/jp/news/2026/03/openai-harness-engineering-codex/).
  - Суть по пересказам: внутренняя бета-версия продукта за 5 месяцев, «zero lines of manually-written code», около 1 млн строк, «Humans steer. Agents execute.» [В].
  - Конкретику (AGENTS.md как карта, линтеры архитектуры, «garbage collection» энтропии) первоисточником я не подтвердил — см. раздел «Сомнительное».
- Böckeler, «Harness engineering for coding agent users», martinfowler.com, 02.04.2026 [П] — [martinfowler.com](https://martinfowler.com/articles/harness-engineering.html).
  - «Agent = Model + Harness». Различает harness вендора (system prompt, оркестрация) и **внешний harness пользователя**: AGENTS.md, правила, hooks, линтеры, тесты, CI, review [П].
  - Две оси [П]:
    - guides (feedforward) и sensors (feedback). Цитата: «you get either an agent that keeps repeating the same mistakes (feedback-only) or an agent that encodes rules but never finds out whether they worked (feed-forward-only)»;
    - computational (детерминированные: тесты, линтеры, тайпчекеры) и inferential (LLM-судья: медленнее, дороже, недетерминированно).
  - Три категории регулирования [П]:
    1. **Maintainability harness** — самая зрелая;
    2. **Architecture fitness harness** — fitness functions, ArchUnit-подобные структурные тесты;
    3. **Behaviour harness** — функциональная корректность, «the elephant in the room», по сути не решена: AI-сгенерированные тесты плюс ручное тестирование недостаточны.
  - Открытые вопросы [П]: согласованность растущего harness; оценка покрытия harness (аналог mutation testing); как поведенческий harness может снизить объём человеческого review.
  - Тезис: «A good harness should not necessarily aim to fully eliminate human input, but to direct it to where our input is most important» [П].
  - Продолжение — статья «Sensors for coding agents» [В] (ссылка из той же статьи: [martinfowler.com](https://martinfowler.com/articles/sensors-for-coding-agents.html)).
- Anthropic (вендор), «Effective harnesses for long-running agents», Justin Young, 26.11.2025 [П] — [Anthropic Engineering](https://www.anthropic.com/engineering/effective-harnesses-for-long-running-agents).
  - Проблема: «each new session begins with no memory of what came before» [П].
  - Решение [П]:
    - initializer agent и coding agent;
    - JSON-список из 200+ фич, изначально помеченных «failing»;
    - `claude-progress.txt`;
    - git-коммиты;
    - «Work on only one feature at a time»;
    - `init.sh`;
    - e2e-проверка через Puppeteer MCP;
    - обязательная самопроверка перед отметкой «готово».
- Thoughtworks Radar vol. 34 [П] — [thoughtworks.com/radar](https://www.thoughtworks.com/radar):
  - тема «Coding agents on a leash» (harness engineering, feedforward и feedback controls);
  - Trial: «Feedback sensors for coding agents», «Sandboxed execution for coding agents», «Agent Skills»;
  - Caution: «Agent instruction bloat», «Coding agent swarms», «MCP by default».

**Spec-driven development (как концепция; детальный разбор — у другого исследователя)**
- Böckeler, «Understanding Spec-Driven-Development: Kiro, spec-kit, and Tessl», 15.10.2025 [П] — [martinfowler.com](https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html).
  - Три уровня: spec-first, spec-anchored, spec-as-source [П].
  - Критика [П]:
    - несоответствие размеру задачи;
    - тяжело ревьюить многословный markdown;
    - «иллюзия контроля» — агент игнорирует инструкции;
    - параллели с провалами model-driven development.
- GitHub (вендор), Spec Kit (сентябрь 2025): фазы Specify → Plan → Tasks → Implement с явными чекпоинтами, «constitution» [В] — [github.blog](https://github.blog/ai-and-ml/generative-ai/spec-driven-development-with-ai-get-started-with-a-new-open-source-toolkit), [Visual Studio Magazine](https://visualstudiomagazine.com/Articles/2025/09/03/GitHub-Open-Sources-Kit-for-Spec-Driven-AI-Development.aspx).
- Thoughtworks Radar vol. 33 (ноябрь 2025) поместил SDD в Assess и предупреждал об over-specification [В] — [horadecodar (пересказ)](https://horadecodar.com.br/spec-driven-development-avaliar-adocao/); темы vol. 33 [В] — [Thoughtworks podcast](https://www.thoughtworks.com/insights/podcasts/technology-podcasts/themes-technology-radar-33).

**AI-native SDLC / AI-DLC**
- AWS (вендор), «AI-Driven Development Life Cycle», Raja SP, 31.07.2025 [П] — [AWS DevOps Blog](https://aws.amazon.com/blogs/devops/ai-driven-development-life-cycle/).
  - Принципы [П]:
    - «AI Powered Execution with Human Oversight»: AI строит план, задаёт уточняющие вопросы и оставляет критичные решения людям;
    - «Dynamic Team Collaboration».
  - Фазы Inception → Construction → Operations; ритуалы «Mob Elaboration» и «Mob Construction» [П].
  - Спринты заменяются «bolts» (часы или дни), эпики — «Units of Work» [П].
- [И] AI-DLC спроектирован под команду (mob-ритуалы); для solo-разработчика его ритуалы вырождаются, а фазовая структура остаётся.

**Compound engineering**
- Every (Dan Shipper, Kieran Klaassen), «Compound engineering: how Every codes with agents», 11.12.2025 [П] — [every.to](https://every.to/chain-of-thought/compound-engineering-how-every-codes-with-agents).
  - Идея: «each feature to make the next feature easier to build».
  - Петля Plan → Work → Assess/Review → **Compound**: ошибки и решения записываются, «so that the agent can use them next time».
  - Review включает линтеры, юнит-тесты и специализированных субагентов (безопасность, производительность, сложность) [П].
- Есть одноимённый Claude Code plugin от EveryInc [В] — [skillselion](https://skillselion.com/marketplace/EveryInc/compound-engineering-plugin).
- Klaassen говорит, что половину времени он тратит на фичу, а половину — на фиксацию уроков [В] — [Render blog](https://render.com/blog/half-the-feature-is-the-lesson-how-kieran-klaassen-runs-cora-without-touching-the-code).

**Академические рамки**
- Hassan et al., «Agentic Software Engineering: Foundational Pillars and a Research Roadmap», arXiv 2509.06216 (07.09.2025, v3 от 24.06.2026) [П] — [arXiv](https://arxiv.org/abs/2509.06216).
  - Вводит «SE 3.0», двойственность «SE for Humans / SE for Agents» и четыре столпа SE для переосмысления: actors, processes, tools, artifacts [П].
  - Предлагает два workbench: ACE (Agent Command Environment, где человек руководит и менторит агентов) и AEE (Agent Execution Environment, с «agent-initiated human callbacks») [П].
  - Общая рамка — SASE (Structured Agentic SE) [П].
- Hassan et al., «Towards AI-Native Software Engineering (SE 3.0)», arXiv 2410.06107 (старше 2025): intent-centric, conversation-oriented development; стек Teammate.next / IDE.next / Compiler.next / Runtime.next [В] — [arXiv](https://arxiv.org/abs/2410.06107).
- Rashina Hoda, «Toward Agentic Software Engineering Beyond Code: Framing Vision, Values, and Vocabulary», arXiv 2510.19692 (10.2025, ревизия 02.2026) [П] — [arXiv](https://arxiv.org/abs/2510.19692).
  - Призыв к «whole of process» видению за пределами кода, к ценностям и согласованному словарю.
  - Агентный SE должен стать «the next process-level paradigm shift» [П].

### Inferences
- [И] Рамки можно разложить по единице анализа:
  - **сессия и окно контекста** — context engineering;
  - **среда вокруг агента** — harness engineering;
  - **артефакт намерения** — SDD;
  - **жизненный цикл и ритуалы** — AI-DLC;
  - **петля обучения системы** — compound engineering;
  - **позиция и этика разработчика** — augmented coding, vibe engineering, agentic engineering.

  Рамки не конкурируют, а покрывают разные уровни. Context engineering — подмножество harness: у Böckeler он описан как средство доставки guides и sensors.
- [И] Для брифа (человек обязан понимать код) ближе всего позиции Бека (augmented coding: «you care about the code») и Уиллисона (accountability, linear walkthroughs). OpenAI-эксперимент с «zero manually-written code» — противоположный полюс.

### Gaps
- Пост OpenAI о harness engineering не открылся (403). Его конкретные предписания подтверждены только вторичными источниками.
- Первоисточник Карпати (X/Twitter) не проверен.
- Steve Yegge (книга «Vibe Coding» с Gene Kim, оркестратор Gas Town), Gergely Orosz, Hamel Husain (evals), Chip Huyen в рамках лимита вызовов не исследованы. Утверждений о них в заметках нет.

---

## Вопрос 2. Рекомендации вендоров (все источники — вендор)

### Takeaway
Вендоры сходятся на пяти вещах:
1. дать агенту исполнимую проверку (тесты, сборка, скриншот);
2. разделять исследование и планирование от реализации;
3. держать короткий файл постоянных инструкций (CLAUDE.md, AGENTS.md, copilot-instructions.md) и выносить остальное в подгружаемые по требованию skills;
4. управлять окном контекста как главным ресурсом;
5. сохранять человеческое review.

Расходятся они в степени автономии: от «задачи с чёткими критериями приёмки в PR» у GitHub до «zero manually-written code» у OpenAI.

### Cited Findings

**Anthropic (вендор)**
- Best practices Claude Code (сейчас в документации; старый URL anthropic.com/engineering/claude-code-best-practices перенаправляет на неё) [П] — [code.claude.com/docs/en/best-practices](https://code.claude.com/docs/en/best-practices).
  - Ключевое ограничение: «Claude's context window fills up fast, and performance degrades as it fills» [П].
  - «Give Claude a way to verify its work»: «Without a check it can run, "looks done" is the only signal available, and you become the verification loop» [П].
  - Уровни жёсткости проверки: в промпте; `/goal`; детерминированный Stop hook; verification-субагент [П].
  - Workflow Explore → Plan → Implement → Commit в plan mode с оговоркой «If you could describe the diff in one sentence, skip the plan» [П].
  - «Let Claude interview you» → SPEC.md → новая сессия на реализацию. Хороший спек «name[s] the files and interfaces involved, state[s] what is out of scope, and end[s] with an end-to-end verification step» [П].
  - CLAUDE.md должен быть коротким: «For each line, ask: "Would removing this cause Claude to make mistakes?"» и «Bloated CLAUDE.md files cause Claude to ignore your actual instructions!» [П].
  - Hooks — «deterministic and guarantee the action happens», в отличие от advisory CLAUDE.md [П].
  - Writer/Reviewer в разных сессиях; adversarial review субагентом в свежем контексте [П]. Предостережение: reviewer, которого попросили искать дыры, найдёт их и в здоровом коде, и «Chasing every finding leads to over-engineering» [П].
  - Анти-паттерны: kitchen sink session, повторные исправления (после двух неудач — `/clear`), раздутый CLAUDE.md, «trust-then-verify gap» («If you can't verify it, don't ship it»), бесконечное исследование [П].
- Context engineering (29.09.2025) и long-running harnesses (26.11.2025) — см. вопрос 1 [П].
- Agent Skills, 16.10.2025 (Zhang, Lazuka, Murag): папки с инструкциями, скриптами и ресурсами, загружаемые через progressive disclosure (имя и описание → SKILL.md → вложенные файлы). 18.12.2025 опубликованы как открытый стандарт (agentskills.io) [П] — [Anthropic Engineering](https://www.anthropic.com/engineering/equipping-agents-for-the-real-world-with-agent-skills). Thoughtworks vol. 34 ставит Agent Skills в Trial [П] — [Radar](https://www.thoughtworks.com/radar).
- «Building effective agents» (декабрь 2024) — (старше 2025), не открывал; посвящён архитектуре агентов, а не процессу разработки.

**OpenAI (вендор)**
- Harness engineering (11.02.2026) — см. вопрос 1, [В].
- AGENTS.md: «A simple, open format for guiding coding agents». Сейчас «stewarded by the Agentic AI Foundation under the Linux Foundation», используется в «over 60k open-source projects». Поддерживают Codex, Jules, Copilot, Cursor, Devin, Aider, goose, Junie и др. [П] — [agents.md](https://agents.md/).
  - Рекомендуемые разделы: обзор проекта, команды build и test, стиль кода, тестирование, безопасность, правила коммитов и PR, деплой [П].
  - «README.md files are for humans…» [П].

**GitHub (вендор)**
- Copilot coding agent, «Get the best results» [П] — [docs.github.com](https://docs.github.com/en/copilot/tutorials/coding-agent/get-the-best-results).
  - Задачи с «clear description» и «complete acceptance criteria» [П].
  - Не поручать агенту «complex and broadly scoped», «sensitive and critical», «ambiguous» и **«learning tasks»** — то есть задачи, где человеку нужно глубокое понимание [П].
  - Файлы `.github/copilot-instructions.md` и `.github/instructions/**/*.instructions.md`; окружение — `copilot-setup-steps.yml` [П].
  - PR агента ревьюить как PR человека [П].
- Spec Kit — см. вопрос 1 [В].

**Google (вендор)**
- DORA AI Capabilities Model, 24.09.2025 (Storer, DeBellis) [П] — [Google Cloud Blog](https://cloud.google.com/blog/products/ai-machine-learning/introducing-doras-inaugural-ai-capabilities-model). Семь capabilities [П]:
  1. Clear and communicated AI stance;
  2. Healthy data ecosystems;
  3. AI-accessible internal data;
  4. Strong version control practices;
  5. Working in small batches;
  6. User-centric focus;
  7. Quality internal platforms.
  - База: 78 интервью и почти 5000 респондентов опроса [П].
  - Цитата: «in the absence of a user-centric focus, AI adoption can have a negative impact on team performance… AI-assisted development teams may just be moving quickly in the wrong direction» [П].
  - Small batches «amplifies the positive influence of AI on product performance» [П].
- DORA 2025 State of AI-assisted Software Development [В] — [InfoQ](https://www.infoq.com/news/2025/09/dora-state-of-ai-in-dev-2025), [blog.google](https://blog.google/technology/developers/dora-report-2025/), [Rob Bowley](https://blog.robbowley.net/2025/10/01/dora-2025-ai-assisted-dev-report-some-benefit-most-dont/).
  - Использование AI — 90% (+14%), медиана около 2 часов в день [В].
  - AI работает как «amplifier»: выигрывают только кластеры 6–7, у кластеров 1–4 рост скорости съедается ростом change failure rate [В — интерпретация Bowley].
- Gemini CLI и Jules: собственные гайды по процессу я не открывал (см. Gaps). Известно лишь, что Jules поддерживает AGENTS.md [П] — [agents.md](https://agents.md/).

**AWS (вендор)**
- AI-DLC — см. вопрос 1 [П].
- Kiro: SDD-цикл Requirements → Design → Tasks [П по Böckeler] — [martinfowler.com](https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html).

### Inferences
- [И] Общий знаменатель вендорских рекомендаций, переносимый на любой инструмент:
  - (а) файл постоянных инструкций, сжатый до минимума;
  - (б) подгружаемые по требованию знания (skills, nested instructions);
  - (в) исполнимая проверка как условие «готово»;
  - (г) план до кода для неоднозначных задач;
  - (д) свежий контекст для review;
  - (е) человеческое review перед merge.
- [И] Рекомендация GitHub не отдавать агенту «learning tasks» прямо пересекается с требованием брифа «понимать весь код». Это аргумент за то, чтобы явно выделять задачи, которые человек делает сам или с агентом в роли объясняющего.
- [И] DORA — единственный вендорский источник с заявленной количественной эмпирикой, но он измеряет организации и команды, а не solo-разработчика.

### Gaps
- Официальные гайды Google по Gemini CLI и Jules (GEMINI.md, workflow) не проверены.
- Codex best-practices docs от OpenAI не открывались.

---

## Вопрос 3. Независимые голоса (Thoughtworks, Fowler/Böckeler, Willison, Beck, Osmani и др.)

### Takeaway
Независимые практики-эксперты в целом признают ценность агентов, но смещают акцент на дисциплину: тесты, маленькие шаги, review, понимание кода. Они предупреждают о «cognitive debt», раздувании инструкций и иллюзии контроля. Thoughtworks Radar vol. 34 формулирует это как «Retaining principles, relinquishing patterns» и «return to engineering fundamentals».

### Cited Findings
- **Thoughtworks Technology Radar vol. 34 (апрель 2026)** [П] — [thoughtworks.com/radar](https://www.thoughtworks.com/radar).
  - Темы:
    - «Evaluating technology in an agentic world» — semantic diffusion и «codebase cognitive debt»;
    - «Retaining principles, relinquishing patterns»;
    - «Securing permission-hungry agents»;
    - «Coding agents on a leash».
  - Кольца:
    - Adopt — Context engineering, Structured output, Zero trust architecture;
    - Trial — Agent Skills, Feedback sensors for coding agents, Sandboxed execution;
    - Assess — Code intelligence as agentic tooling, Measuring collaboration quality with coding agents, Team of coding agents;
    - Caution — Agent instruction bloat, Coding agent swarms, Ignoring durability in agent workflows, MCP by default.
  - Пресс-релиз: «return to engineering fundamentals»: zero trust, DORA-метрики, testability [В] — [iTWire](https://itwire.com/business-it-news/data/as-ai-accelerates-software-complexity,-thoughtworks-technology-radar-urges-a-return-to-engineering-fundamentals-to-combat-cognitive-debt).
- **Thoughtworks Radar vol. 33 (ноябрь 2025)**: темы «infrastructure automation arriving for AI», «rise of agents elevated by MCP», «AI coding workflows», «emerging AI antipatterns» [В] — [Thoughtworks podcast](https://www.thoughtworks.com/insights/podcasts/technology-podcasts/themes-technology-radar-33).
- **Birgitta Böckeler** — серия «Exploring Gen AI» на martinfowler.com: SDD (10.2025), context engineering (02.2026), harness engineering (04.2026), sensors. Подробности в вопросе 1 [П].
- **Simon Willison** — vibe engineering (10.2025), гайд Agentic Engineering Patterns (с 02.2026) [П]. 15.02.2026 он писал о «cognitive debt» [В] — [simonwillison.net](https://simonwillison.net/2026/Feb/15/cognitive-debt).
- **Kent Beck** — augmented coding (06.2025) [П].
- **Addy Osmani** (Google, но пишет в личном блоге), «Agentic Code Review» [П] — [addyosmani.com](https://addyosmani.com/blog/agentic-code-review/). Тезис: написание кода стало дешёвым, а review — нет, поэтому узкое место — верификация. Понятия «verification budget» и «intent recovery»; разработчик становится «the first human being to ever lay eyes on this code» [П]. Цифры, которые он приводит из третьих источников, вынесены в «Сомнительное».
- **Margaret-Anne Storey**, «From Technical Debt to Cognitive and Intent Debt», arXiv 2603.22106 (03.2026). Triple Debt Model [В] — [arXiv](https://arxiv.org/abs/2603.22106), [LeadDev](https://leaddev.com/ai/ai-coding-creates-two-kinds-of-debt-youre-only-measuring-one):
  - технический долг — в коде;
  - **когнитивный долг** — в головах людей (эрозия общего понимания системы);
  - **intent debt** — во внешних артефактах.
- **METR RCT (10.07.2025)** [В] — [METR](https://metr.org/blog/2025-07-10-early-2025-ai-experienced-os-dev-study/), [Willison](https://simonwillison.net/2025/Jul/12/ai-open-source-productivity/).
  - 16 опытных open-source разработчиков, 246 задач в своих репозиториях; с AI (в основном Cursor + Claude 3.5/3.7) они работали **на 19% медленнее**.
  - Сами разработчики ожидали ускорения на 24% и даже после эксперимента оценивали его в 20%.
  - Это инструменты начала 2025 года, до массового перехода к агентам.

### Inferences
- [И] Для брифа «solo + понимать весь код» главный независимый риск сформулирован как cognitive debt (Storey, Thoughtworks, Willison). Практические противовесы в источниках:
  - linear walkthroughs и interactive explanations (Willison);
  - TDD и малые шаги (Beck, DORA small batches);
  - Behaviour harness как всё ещё нерешённая зона (Böckeler).
- [И] Разрыв между ощущаемой и измеренной продуктивностью в METR — аргумент за то, чтобы в solo-процессе измерять хотя бы базовые вещи, а не полагаться на ощущение скорости.

### Gaps
- Gergely Orosz (Pragmatic Engineer), Steve Yegge, Hamel Husain, Chip Huyen не исследованы в рамках лимита.
- PDF Thoughtworks vol. 34 полностью не читался; описания blip-ов не открывались.

---

## Вопрос 4. Есть ли устоявшаяся таксономия «process areas» для AI-assisted разработки и как сравнивать кандидатов

### Takeaway
Устоявшейся таксономии уровня CMMI process areas, областей знаний PMBOK или SWEBOK KA для агентной разработки **не найдено**. Есть несколько кандидатов разной природы: модель организационных capabilities (DORA), фазовый жизненный цикл (AI-DLC), категории регулирования агента (Böckeler), академические «столпы» (Hassan et al.), SLR-картирование по фазам SDLC (Apostolou et al.), а также общая SWEBOK v4 (2024) с упоминанием «AI for SE». Ни один из них не покрывает одновременно процесс, артефакты и проверку с эмпирической базой и под solo-разработчика.

### Cited Findings (кандидаты)
1. **DORA AI Capabilities Model** (Google, вендор, 09.2025): 7 capabilities; эмпирика — 78 интервью и около 5000 респондентов [П] — [Google Cloud Blog](https://cloud.google.com/blog/products/ai-machine-learning/introducing-doras-inaugural-ai-capabilities-model). Отчёт: [2025 DORA AI Capabilities Model report](https://cloud.google.com/resources/content/2025-dora-ai-capabilities-model-report).
2. **AWS AI-DLC** (вендор, 07.2025): 3 фазы, ритуалы, bolts и units [П] — [AWS](https://aws.amazon.com/blogs/devops/ai-driven-development-life-cycle/). Эмпирической валидации в посте нет [П — отсутствие в пересказе; И].
3. **Böckeler / Fowler** (независимый практик, 2026) [П] — [harness](https://martinfowler.com/articles/harness-engineering.html), [context](https://martinfowler.com/articles/exploring-gen-ai/context-engineering-coding-agents.html):
   - три harness-категории (maintainability, architecture fitness, behaviour);
   - две оси (guides/sensors × computational/inferential);
   - типы контекста и ось «кто загружает».
   - Основание — опыт практиков, а не измерения.
4. **Hassan et al., Agentic SE / SASE** (академ., 2025–2026): четыре столпа (actors, processes, tools, artifacts) × двойственность SE for Humans / SE for Agents; workbench ACE и AEE [П] — [arXiv 2509.06216](https://arxiv.org/abs/2509.06216). Это roadmap и видение, без эмпирической валидации [И].
5. **Apostolou, Bosch, Olsson, «Assistance to Autonomy: SLR of Agentic AI across the SDLC»**, arXiv 2605.15245 (05.2026) [П] — [arXiv](https://arxiv.org/abs/2605.15245).
   - 92 первичных исследования из более чем 1600, по Kitchenham; отбор делал мультиагентный AI-пайплайн.
   - Выводы:
     - поздние фазы SDLC имеют «the highest maturity and industrial presence», а ранние «remain almost exclusively academic proofs-of-concept»;
     - доминирует паттерн «Planner-Executor-Reviewer»;
     - «Output verifiability is the primary enabler of agentic adoption»;
     - индустрия ограничивает агентов «verifiable, bounded spaces».
6. **Hoda, «Agentic SE Beyond Code»** (10.2025): рамка ценностей и словаря, «whole of process» [П] — [arXiv 2510.19692](https://arxiv.org/abs/2510.19692).
7. **SWEBOK v4** (IEEE CS, 2024) — (старше 2025). Новые KA (architecture, security, operations) и связь с AI в разрезе «AI for SE / SE for AI». Отдельной области знаний под агентную разработку, судя по найденным материалам, нет [В] — [t2informatik](https://t2informatik.de/en/smartpedia/swebok/), [IEEE CS SWEBOK evolution](https://www.computer.org/volunteering/boards-and-committees/professional-educational-activities/software-engineering-committee/swebok-evolution).
8. Другие академические обзоры, найденные, но не открытые [В]:
   - «AI Agentic Programming: A Survey…», arXiv 2508.11126 — 152 работы — [arXiv](https://arxiv.org/abs/2508.11126v1);
   - «The Rise of AI-Native Software Engineering…» — 48 публикаций (из поисковой выдачи, URL не подтверждён).
9. **Storey, Triple Debt Model** (03.2026) — не таксономия процессов, но кандидат на ось «здоровье системы» [В] — [arXiv 2603.22106](https://arxiv.org/abs/2603.22106).

### Возможные критерии сравнения [И]

| Кандидат | Покрытие (что охватывает) | Доказательная база | Гранулярность | Пригодность для solo-разработчика |
|---|---|---|---|---|
| DORA AI Capabilities (вендор) | Организационные условия: позиция по AI, данные, VCS, small batches, user focus, платформа | Опрос около 5000 и 78 интервью [П] — самая сильная из найденных | Крупная (7 capabilities), без процедур | Низкая–средняя: половина capabilities организационная; переносимы VCS, small batches, AI-accessible data, user focus |
| AWS AI-DLC (вендор) | Полный жизненный цикл: Inception, Construction, Operations | Описание метода, без опубликованной эмпирики [И] | Средняя: фазы, ритуалы, артефакты | Средняя: mob-ритуалы командные, фазовая модель переносима |
| Böckeler harness + context (независимый) | Управление агентом: guides, sensors, 3 категории регулирования, типы контекста | Опыт практиков Thoughtworks, без измерений | Средне-мелкая, близко к инженерным практикам | Высокая: описывает именно user harness, который настраивает один человек |
| Hassan et al. SASE (академ.) | Actors, processes, tools, artifacts × human/agent | Видение и roadmap | Концептуальная | Низкая напрямую, полезна как словарь (например, явные артефакты для handoff) |
| Apostolou et al. SLR (академ.) | Фазы SDLC × зрелость агентных решений | SLR, 92 исследования | Средняя (фазы SDLC) | Средняя: даёт эмпирический ориентир, где агентам можно доверять (верифицируемые фазы) |
| SWEBOK v4 (старше 2025) | Вся SE, 18 KA | Консенсус сообщества | Крупная | Как каркас «что не забыть», не агент-специфична |
| Willison / Beck / compound (практики) | Наборы практик и петли | Личный опыт | Мелкая | Высокая, но несистематичная |

- [И] Кандидаты лежат на разных уровнях: организационные условия (DORA) → жизненный цикл (AI-DLC, SDLC-фазы в SLR) → управление агентом (Böckeler) → концептуальные столпы (Hassan) → практики (Willison, Beck, Every). Поэтому таксономию «process areas» для solo-случая, видимо, придётся собирать из нескольких уровней, а не выбирать одну.
- [И] Возможные дополнительные критерии:
  - явность ответственности человека (кто принимает решение и кто понимает код);
  - наличие исполнимых критериев «готово»;
  - устойчивость к смене инструмента (Agent Skills и AGENTS.md как открытые форматы против vendor-specific);
  - наличие механизма против cognitive debt.

### Gaps
- Не найдено ISO/IEC или IEEE стандарта именно по AI-assisted или agentic software development. ISO/IEC 42001 и 5338 касаются AI-систем, а не разработки с AI. Отдельно это не проверялось — [В/И].
- Не найдено, обновлялся ли SWEBOK после v4 под агентную разработку.
- Полный отчёт DORA AI Capabilities (PDF) не читался: методика (SEM, кластеры) известна только из блога.

---

## Вопрос 5. Что заменяет классические модели (CMMI, PMBOK, Scrum) и куда сместились узкие места

### Takeaway
Прямой «замены» CMMI, PMBOK или Scrum в источниках нет. Наблюдается другое: ритуалы переописываются (у AWS спринты заменены bolts), принципы сохраняются, а паттерны пересматриваются (Thoughtworks), и центр тяжести смещается. Источники сходятся, что узкое место ушло из написания кода в четыре зоны:
1. **верификация и review** (Osmani, Anthropic, SLR Apostolou);
2. **спецификация и намерение** (SDD, интервью-спек у Anthropic, intent debt у Storey);
3. **контекст** (context engineering, «context window — the most important resource»);
4. **понимание кода человеком** (cognitive debt).

### Cited Findings
- AWS AI-DLC явно заменяет Scrum-спринты на «bolts» длиной в часы или дни, а эпики — на «Units of Work» [П] — [AWS](https://aws.amazon.com/blogs/devops/ai-driven-development-life-cycle/).
- Thoughtworks vol. 34: «Retaining principles, relinquishing patterns» — принципы SE сохраняются, модели коллаборации и структуры команд пересматриваются [П] — [Radar](https://www.thoughtworks.com/radar).
- **Узкое место — верификация и review.**
  - Anthropic: без исполнимой проверки «you become the verification loop» [П] — [docs](https://code.claude.com/docs/en/best-practices).
  - Osmani: «review is now the bottleneck», verification budget [П] — [addyosmani.com](https://addyosmani.com/blog/agentic-code-review/).
  - SLR: «Output verifiability is the primary enabler of agentic adoption» [П] — [arXiv 2605.15245](https://arxiv.org/abs/2605.15245).
  - Böckeler: behaviour harness не решён [П] — [martinfowler.com](https://martinfowler.com/articles/harness-engineering.html).
- **Узкое место — контекст.** «Most best practices are based on one constraint: Claude's context window fills up fast» [П] — [docs](https://code.claude.com/docs/en/best-practices); context rot [П] — [Anthropic](https://www.anthropic.com/engineering/effective-context-engineering-for-ai-agents).
- **Узкое место — спецификация и намерение.**
  - Anthropic: «Time spent making the spec precise pays off more than time spent watching the implementation» [П] — [docs](https://code.claude.com/docs/en/best-practices).
  - GitHub: acceptance criteria в issue [П] — [docs.github.com](https://docs.github.com/en/copilot/tutorials/coding-agent/get-the-best-results).
  - DORA: без user-centric focus AI ухудшает работу команды [П] — [Google Cloud Blog](https://cloud.google.com/blog/products/ai-machine-learning/introducing-doras-inaugural-ai-capabilities-model).
  - Osmani: intent recovery [П].
- **Узкое место — понимание.** Cognitive debt [В] — [Storey arXiv 2603.22106](https://arxiv.org/abs/2603.22106); [Thoughtworks Radar](https://www.thoughtworks.com/radar) [П]; METR — ощущение ускорения при фактическом замедлении [В] — [METR](https://metr.org/blog/2025-07-10-early-2025-ai-experienced-os-dev-study/).
- **Узкое место — внимание человека.** Böckeler: harness должен направлять человеческий ввод туда, где он важнее всего [П]. Every: основные усилия — в plan и review, «work» минимален [П] — [every.to](https://every.to/chain-of-thought/compound-engineering-how-every-codes-with-agents).
- Старые принципы, которые источники называют усиленными:
  - small batches (DORA) [П];
  - version control (DORA) [П];
  - TDD (Beck, Willison) [П];
  - CI и линтеры (Willison, Böckeler) [П].

### Inferences
- [И] CMMI и PMBOK описывали процессы управления людьми и проектами. В агентной разработке растёт доля управления средой агента (harness) и приёмкой. Поэтому новые «process areas», вероятно, будут сгруппированы вокруг:
  - намерения и спецификации;
  - контекста и знаний;
  - ограничений (guides);
  - проверки (sensors, review);
  - накопления уроков (compound);
  - понимания и владения кодом человеком.

  Это синтез, а не установленная таксономия.
- [И] Для solo-разработчика review нельзя делегировать коллеге. Значит, бюджет верификации ограничен вниманием одного человека, и это сильный аргумент за computational sensors (тесты, типы, линтеры, архитектурные тесты) и малые инкременты.

### Gaps
- Нет найденных эмпирических исследований именно solo-разработчика с агентом на новом проекте. Вся эмпирика (DORA, METR, Faros) — про команды или существующие репозитории.
- Не найдено формальных работ, сопоставляющих CMMI или PMBOK с агентной разработкой.

---

## Сомнительное / не проверено

- **OpenAI harness engineering — детали.** Утверждения про «около 1 млн строк», «1/10 времени», «3.5 PR на инженера в день», AGENTS.md как короткое «оглавление» (около 100 строк), docs/ как system of record, кастомные линтеры для архитектурных правил, периодическая «garbage collection» энтропии взяты из вторичных пересказов ([LetsDataScience](https://letsdatascience.com/news/openai-introduces-harness-engineering-for-automated-developm-703d90b6) и поисковые сниппеты). Первоисточник вернул 403 — не проверено.
- **Цифры из блога Osmani**, который пересказывает третьи стороны; первоисточники я не открывал — [addyosmani.com](https://addyosmani.com/blog/agentic-code-review/):
  - Faros AI (03.2026): review дольше на 441.5%, churn +861%, defect rate 9% → 54%, PR без review +31.3%;
  - GitClear: около 4× кода при около 12% реальной пользы;
  - CodeRabbit: примерно в 1.7 раза больше проблем в AI-соавторских PR;
  - GitHub: более 60 млн Copilot-reviews;
  - Anthropic Code Review: менее 1% неверных находок (вендор).
- **Распределение усилий в compound engineering.** Суммаризатор выдал «Plan 80%… Review 80%», что внутренне противоречиво. По памяти, в оригинале сказано примерно «80% — plan и review, 20% — work и compound», но это не сверено. Формулировка Render («половина времени на фичу, половина на уроки») — [В].
- **Карпати.** «Агенты выполняют 20+ автономных действий до вмешательства человека», «пишет 1% кода» — из пересказов ([tianpan.co](https://tianpan.co/forum/t/karpathy-says-vibe-coding-is-passe-at-its-one-year-anniversary-agentic-engineering-is-the-new-paradigm/628)), не проверено.
- **Утверждение Every «a single developer can do the work of five developers»** — самооценка авторов без измерений [П как цитата, но не как факт].
- **DORA 2025:** «90% adoption», «кластеры 1–4 скорее страдают» — из СМИ и блога Bowley, отчёт не открыт [В].
- **Thoughtworks vol. 33:** SDD в Assess и предупреждение об over-specification — из португалоязычного пересказа, страница blip-а не открыта [В].
- **«The Rise of AI-Native Software Engineering…» (48 публикаций)** — фигурирует только в поисковой выдаче, первоисточник не найден и не проверен.
