# Блок 4а. Механизмы: требования, планирование, завершение работы (агентная разработка, 2025–2026)

Контекст: один разработчик + AI-агент, новый небольшой backend-сервис; человек отвечает за результат и должен понимать весь код. Не зависит от стека. Здесь собраны варианты и критерии, а не готовый процесс.

Обозначения: [П] — проверено по первоисточнику (я его открывал); [В] — из вторичного источника или по сниппету поиска; [И] — моя интерпретация. (вендор) — документация или блог производителя инструмента. (старше 2025) — источник старше рассматриваемого периода.
Типы механизмов: инструкция (CLAUDE.md/AGENTS.md, промпт), skill/команда, hook, CI, субагент, артефакт (файл в репозитории).
«Гарантия» = исполняется детерминированно, вне зависимости от того, как модель следует инструкциям. «Договорённость» = зависит от того, послушается ли модель или человек.

Общая опора для всех разделов: Anthropic прямо разделяет эти два класса. «Unlike CLAUDE.md instructions which are advisory, hooks are deterministic and guarantee the action happens» [П] — [Claude Code best practices (вендор)](https://code.claude.com/docs/en/best-practices). Там же задана шкала того, насколько жёстко проверка блокирует завершение: «in one prompt» → «/goal condition» (отдельный оценщик) → «Stop hook … blocks the turn from ending until it passes» → «verification subagent … so the agent doing the work isn't the one grading it» [П] — [там же](https://code.claude.com/docs/en/best-practices).

---

## 1. Требования и намерение: как убедиться, что агент понял задачу и всё уточнил

### Takeaway
Сами модели плохо распознают недоопределённость задачи и по умолчанию не спрашивают. Если же уточнения есть, результат заметно растёт: на Ambig-SWE до +74%. Поэтому фаза уточнения должна быть явной: команда или skill с ограниченным числом вопросов и артефактом, куда записываются ответы. Гарантии нет ни у одного варианта. Ближе всего к ней подходят approval-гейты между фазами (Kiro) и проверка артефакта на маркеры `[NEEDS CLARIFICATION]` через hook или CI.

### Cited Findings
**Данные об эффективности**
- Ambig-SWE (Vijayvargiya, Zhou, Yerukola, Sap, Neubig; v3 — 21.02.2026): недоопределённый вариант SWE-bench Verified. «performance improved up to 74% over the non-interactive settings»; «models struggle to distinguish between well-specified and underspecified instructions» [П] — [arXiv 2502.13069](https://arxiv.org/abs/2502.13069). Что модели по умолчанию не спрашивают сами, это [И]/[В] из резюме, а не прямая цитата.
- Есть и другие бенчмарки по теме уточнений у кодовых агентов: «Ask or Assume?» (uncertainty-aware scaffold, который отделяет обнаружение недоопределённости от выполнения кода), ClarEval, QuestBench [В] — [arXiv 2603.26233](https://www.alphaxiv.org/abs/2603.26233), [ClarEval 2603.00187](https://arxiv.org/html/2603.00187), [QuestBench 2503.22674](https://arxiv.org/html/2503.22674v2). Цифры из них я не проверял.

**Spec Kit `/clarify` (артефакт + команда)**
- Не больше 5 вопросов, задаются по одному. Пользователь может прервать словами «done/stop/proceed». Таксономия из 9 категорий: Functional Scope, Domain & Data Model, Interaction/UX, Non-Functional, Integration, Edge Cases & Failure Handling, Constraints & Tradeoffs, Terminology, Completion Signals. Каждая категория получает статус Clear/Partial/Missing [П] — [spec-kit clarify.md](https://raw.githubusercontent.com/github/spec-kit/main/templates/commands/clarify.md).
- Ответы записываются в спецификацию: секция `## Clarifications` → `### Session YYYY-MM-DD` → `- Q: … → A: …`, затем вносятся в нужные разделы (FR, Data Model, Edge Cases). Спецификация сохраняется после каждого ответа [П] — [там же](https://raw.githubusercontent.com/github/spec-kit/main/templates/commands/clarify.md).
- Предполагается, что `/clarify` выполняется до `/plan`, но его можно пропустить «with noted downstream rework risk» [П]. Фильтр вопросов: «Only include questions whose answers materially impact architecture, data modeling, task decomposition, test design, UX behavior, operational readiness, or compliance validation» [П] — [там же](https://raw.githubusercontent.com/github/spec-kit/main/templates/commands/clarify.md).
- В шаблоне спецификации неясности помечаются, а не додумываются: `[NEEDS CLARIFICATION: …]` (например, «auth method not specified»). Есть отдельная секция Assumptions для «reasonable defaults» [П] — [spec-template.md](https://raw.githubusercontent.com/github/spec-kit/main/templates/spec-template.md).
- `/analyze` — read-only аудит spec/plan/tasks/constitution. Среди проверок: Ambiguity (в том числе «TODO», «fast», «secure» без порога), Underspecification, Coverage Gaps. Severity CRITICAL ставится за нарушение MUST из constitution. Команда не изменяет файлы («Zero modifications to any file») [П] — [analyze.md](https://raw.githubusercontent.com/github/spec-kit/main/templates/commands/analyze.md).

**Kiro (вендор)**
- В Feature Specs между фазами requirements → design → tasks стоят approval-гейты. В Quick Spec их нет: он «runs all three phases automatically without approval gates» [П] — [kiro.dev feature-specs](https://kiro.dev/docs/specs/feature-specs/). Перед design можно запустить «Analyze Requirements», который ищет «logical inconsistencies, ambiguities, conflicting constraints, and gaps» [П] — [там же](https://kiro.dev/docs/specs/feature-specs/).

**Claude Code (вендор)**
- Plan mode отделяет исследование от изменений: «Claude reads files and answers questions without making changes». Ctrl+G открывает план в редакторе для правки. Совет пропускать план, «If you could describe the diff in one sentence» [П] — [best practices](https://code.claude.com/docs/en/best-practices).
- Шаблон интервью: «Interview me in detail using the AskUserQuestion tool … Don't ask obvious questions, dig into the hard parts … then write a complete spec to SPEC.md». После этого предлагается исполнять спецификацию в свежей сессии. «The most useful specs are self-contained: they name the files and interfaces involved, state what is out of scope, and end with an end-to-end verification step» [П] — [там же](https://code.claude.com/docs/en/best-practices).

**OpenAI Codex ExecPlans (вендор) — противоположная установка**
- PLANS.md: «Resolve ambiguities autonomously, and commit frequently». Агент не должен спрашивать на каждом шаге. Решения фиксируются в живой секции «Decision Log» [П] — [OpenAI cookbook: codex_exec_plans](https://developers.openai.com/cookbook/articles/codex_exec_plans). [И] Это модель «assume + log», а не «ask». Для длительных автономных прогонов она уместна, но человек видит допущения только постфактум.

**Факты, интерпретации и допущения**
- Специализированного механизма («fact / interpretation / assumption» с тегами) в изученных фреймворках я не нашёл. Ближайшие аналоги такие: секция Assumptions и маркеры `[NEEDS CLARIFICATION]` в Spec Kit [П]; Decision Log и «Surprises & Discoveries» в ExecPlan [П]; красные карточки «questions or assumptions» в Example Mapping [П] — [Cucumber, Wynne 2015 (старше 2025)](https://cucumber.io/blog/bdd/example-mapping-introduction/).

### Inferences
- [И] Механизмы по степени гарантии, от слабой к сильной:
  1) инструкция «задай вопросы» — договорённость;
  2) skill или команда `/clarify` с жёстким лимитом и таксономией — договорённость, но повторяемая;
  3) артефакт с секциями `Assumptions` / `Open questions` / `Clarifications` — договорённость о наполнении, зато его структуру можно проверить;
  4) hook или CI, который не пропускает переход к коду, пока в spec есть `[NEEDS CLARIFICATION]` или открытые пункты `Open questions` без ответа. Это гарантия наличия формы, а не её качества;
  5) человеческий approval-гейт (plan mode, Kiro): гарантия того, что человек видел план, но не того, что вчитался.
- [И] Чтобы отделить допущения от фактов, лучше всего работает связка «шаблон с тремя явными списками (Подтверждено / Допущение — ждёт решения / Открытый вопрос) + проверка, что в списке допущений нет пунктов без пометки решения». Эффективность такой связки нигде не измерена.
- [И] Ambig-SWE даёт два вывода сразу: уточнение даёт большой прирост, а само обнаружение недоопределённости у модели ненадёжно. Значит, решение «спрашивать или нет» не стоит отдавать на усмотрение агента. Фазу уточнения лучше делать обязательной и ограничивать по числу вопросов, как в Spec Kit.

### Gaps
- Нет данных, которые сравнивали бы `/clarify`, Kiro Analyze и plan mode по эффекту на итоговое качество. Есть только бенчмарки на уровне моделей.
- Не найдено исследований, где измерялось бы явное разделение «факт / интерпретация / допущение» в спецификациях для LLM.

---

## 2. Формат требований: EARS, Given-When-Then, user stories и другие

### Takeaway
Шаблоны EARS и GWT встроены в Kiro, Spec Kit и OpenSpec, но прямых данных, что EARS или Gherkin улучшают генерацию кода по сравнению со свободным текстом, я не нашёл. Есть косвенное: избыточная и структурированная постановка (описание + ограничения + примеры + формат I/O) делает модель устойчивее к недоопределённости. Есть и практическое наблюдение: формальные шаблоны раздувают небольшие задачи.

### Cited Findings
- EARS (Mavin и др., Rolls-Royce; впервые опубликован в 2009). Шесть шаблонов: Ubiquitous «The <system> shall…», State-driven «While…», Event-driven «When…», Optional «Where…», Unwanted «If… then…», Complex [П] — [alistairmavin.com/ears (старше 2025)](https://alistairmavin.com/ears/). На странице есть утверждения «reduces or even eliminates common problems», но эмпирических данных там не приводится [П] — [там же](https://alistairmavin.com/ears/).
- В Kiro используется шаблон «WHEN [condition/event] THE SYSTEM SHALL [expected behavior]» [П] — [kiro.dev feature-specs (вендор)](https://kiro.dev/docs/specs/feature-specs/). По наблюдению Böckeler, в Kiro это user stories «As a…» с критериями «GIVEN…WHEN…THEN» [П] — [martinfowler.com, Böckeler, 15.10.2025](https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html). [И] Мой вывод: на практике это гибрид EARS и GWT.
- В Spec Kit требования оформляются как FR-### с MUST, критерии успеха как SC-### («technology-agnostic»), сценарии как Given/When/Then с приоритетами P1/P2/P3. Каждая история должна быть «INDEPENDENTLY TESTABLE» [П] — [spec-template.md](https://raw.githubusercontent.com/github/spec-kit/main/templates/spec-template.md).
- OpenSpec: требования пишутся с SHALL/MUST, сценарии в виде WHEN/THEN, изменения оформляются дельтами ADDED/MODIFIED/REMOVED [П] — [OpenSpec README](https://github.com/Fission-AI/OpenSpec).
- Akli, Papadakis, Cordy, Le Traon (27.04.2026), 10 моделей, HumanEval и LiveCodeBench. Мутации, ухудшающие спецификацию, сильно портят результат на HumanEval, где спецификация минимальна. На LiveCodeBench эффект «near-zero net effect … due to redundancy across descriptions, constraints, examples, and I/O conventions». Иногда недоопределённость даже помогала, потому что ломала «misleading retrieval-based solution patterns» [П] — [arXiv 2604.24712](https://arxiv.org/abs/2604.24712).
- Böckeler: Kiro на баг-фиксе выдал «4 user stories with a total of 16 acceptance criteria» — «like using a sledgehammer to crack a nut». Её вывод: «I'd rather review code than all these markdown files». Агент при этом то игнорировал пометки, что это описания существующих классов, и создавал дубли, то «go[ing] way overboard because it was too eagerly following instructions» [П] — [martinfowler.com](https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html).
- Генерация Gherkin с помощью LLM в промышленном кейсе (AST 2025): 95% сценариев пользователи сочли полезными, 60% исполняемых скриптов оказались полностью валидны [В] — [arXiv 2504.07244](https://arxiv.org/pdf/2504.07244). Здесь речь о генерации тестов, а не кода по GWT.
- Регистрационный отчёт (registered report) о spec-driven генерации (CURRANTE: Specification → Tests → Function) пока содержит только дизайн исследования, результатов в нём нет [В] — [arXiv 2601.03878](https://arxiv.org/abs/2601.03878).

### Inferences
- [И] Для небольшого backend-сервиса оправдана такая пара: EARS-подобная однострочная формулировка требования с ID (кто и что SHALL при каком триггере) и 1–3 примера GWT как приёмочные критерии, которые напрямую превращаются в тесты. Главный выигрыш даже не в нотации. Он в том, что формулировку можно проверить, у неё есть ID для трассировки и в ней явно выделено «unwanted behaviour» (If… then…): именно эту ветку агент чаще всего забывает. Данных, подтверждающих последнее, нет.
- [И] По Akli и др. избыточность (описание + ограничения + примеры) важнее формы. Конкретные примеры входа и выхода ценнее, чем строгая грамматика.
- [И] Planguage (Gilb) и use cases (Cockburn) относятся к классическому RE (старше 2025). Применительно к LLM-агентам данных о них я не нашёл. Planguage полезен в одном месте: для нефункциональных требований с числами (Scale/Meter/Goal), например латентности или лимитов.

### Gaps
- Прямого эксперимента «EARS против свободного текста» для генерации кода LLM я не нашёл. Есть работа об автоматической генерации EARS лёгкими LLM (ICTMOD 2025), но она по сниппету, я её не открывал.
- Нет данных о том, что понятнее человеку при ревью: EARS или GWT.

---

## 3. Откуда берутся требования: сценарии, CJM, JTBD, event storming, example mapping

### Takeaway
SDD-фреймворки (Spec Kit, Kiro) начинают с пользовательских сценариев или историй и выводят из них FR. Работы, которые связывали бы JTBD, CJM или event storming с агентной разработкой и давали бы данные об эффективности, я не нашёл. Самый «агентно-совместимый» классический приём — Example Mapping: правила + примеры + вопросы, и примеры напрямую становятся тестами.

### Cited Findings
- Spec Kit: раздел «User Scenarios & Testing» с приоритизированными историями, каждая из которых — отдельный срез: «Developed independently / Tested independently / Deployed independently». Из историй выводятся FR и SC [П] — [spec-template.md](https://raw.githubusercontent.com/github/spec-kit/main/templates/spec-template.md). Задачи в tasks.md группируются по историям (US1, US2…) [П] — [tasks-template.md](https://raw.githubusercontent.com/github/spec-kit/main/templates/tasks-template.md).
- Example Mapping (Matt Wynne, 2015; старше 2025): жёлтая карточка — история, синяя — правило / критерий приёмки, зелёная — пример, красная — «unanswered questions or assumptions». Сессия длится около 25 минут. «The true purpose is to reach a shared understanding of what it will take for the story to be done» [П] — [cucumber.io](https://cucumber.io/blog/bdd/example-mapping-introduction/).
- Kiro Bugfix spec: «Bug analysis with current/expected/unchanged behavior» [П] — [kiro.dev/docs/specs (вендор)](https://kiro.dev/docs/specs/). [И] Часть «unchanged behavior» — это источник требований-инвариантов: что не должно сломаться.

### Inferences
- [И] Для API клиентский сценарий — это «потребитель X делает последовательность вызовов, чтобы достичь Y». Он удобно ложится на GWT и становится контрактным или e2e-тестом. CJM и JTBD уместны раньше, чтобы решить, какие сценарии вообще нужны и где граница scope. Event storming полезен, когда в домене много событий и состояний. Для одного разработчика это скорее упражнение «на салфетке», которое агент может помочь провести интервью-командой.
- [И] Цепочка «сценарий → правила (EARS-строки с ID) → примеры (GWT) → тесты» и есть Example Mapping, переложенный на артефакты Spec Kit. Это договорённость. Гарантию дают только тесты, привязанные к ID (см. раздел 5).

### Gaps
- Первичные источники по JTBD (Christensen/Ulwick), CJM и EventStorming (Brandolini) в этой сессии не открывались. Исследований об их связке с LLM-агентами не найдено.

---

## 4. Нормативный текст против описательного; происхождение ограничений

### Takeaway
Работающий приём — явная лексическая маркировка нормы (RFC 2119/8174: значение имеют только КАПСОМ) плюс отдельные секции «Constraints / Requirements», отделённые от «Context / Explanation». Это используют Spec Kit (FR с MUST; constitution с «MUST … non-negotiable») и OpenSpec (SHALL/MUST). Наблюдение Böckeler показывает и обратный риск: агент превращает описание в инструкцию, и наоборот. Готового инструмента для provenance ограничений (каждое правило ссылается на источник) в агентных фреймворках я не нашёл. Его можно собрать из ID и простой проверки в CI.

### Cited Findings
- RFC 8174: «The words have the meanings specified herein only when they are in all capitals … When these words are not capitalized, they have their normal English meanings» плюс обязательная boilerplate-ссылка на BCP 14 [П] — [RFC 8174 (старше 2025)](https://www.rfc-editor.org/rfc/rfc8174).
- Spec Kit `/analyze`: нарушение MUST-принципа constitution получает CRITICAL («MUST rules are non-negotiable») [П] — [analyze.md](https://raw.githubusercontent.com/github/spec-kit/main/templates/commands/analyze.md).
- Böckeler: агент «ignored the notes that these were descriptions of existing classes» и при этом в другом месте «too eagerly following instructions» [П] — [martinfowler.com](https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html). [И] Это прямой пример того, как описательный и нормативный текст смешиваются в агентной спецификации.
- Diátaxis: четыре типа документации (tutorial, how-to, reference, explanation) на осях action/cognition и acquisition/application [П] — [diataxis.fr (старше 2025 по происхождению)](https://diataxis.fr/). [И] В Diátaxis нет понятия «норма». Ограничения логичнее всего размещать в reference, а explanation — по определению ненормативный текст.
- Anthropic советует держать в CLAUDE.md только то, без чего Claude ошибается, а то, что должно соблюдаться всегда, переводить в hooks: «If Claude already does something correctly without the instruction, delete it or convert it to a hook» [П] — [best practices (вендор)](https://code.claude.com/docs/en/best-practices).

### Inferences
- [И] Варианты разделения:
  а) лексика BCP 14 (MUST/SHOULD/MAY только КАПСОМ) плюс отдельная секция `## Constraints`. Это договорённость, но её можно проверить грепом: CI-скрипт ищет MUST/SHALL вне секций Constraints/Requirements или находит ограничение без ID;
  б) раздельные файлы: нормы в `constraints.md` / ADR, описания в `docs/explanation/` и явная фраза «descriptive, not normative» в начале файлов-описаний;
  в) нормы, проверяемые машиной, переносятся в линтеры, тесты, архитектурные тесты и hooks. Только в этом случае ограничение становится гарантией.
- [И] Provenance: у каждого ограничения есть ID и поле `Source:` (REQ-…, ADR-…, «решение человека, дата»). CI-проверка простая: у каждой строки с MUST/SHALL в секции Constraints есть `Source:`, и он резолвится в существующий файл или ID. Spec Kit `/analyze` частично проверяет обратное направление (orphaned requirements/tasks), но не источник ограничения. Готового OSS-инструмента именно для provenance ограничений в агентном контексте я не нашёл. Doorstop и StrictDoc (раздел 5) умеют связи parent/child, их можно использовать и для этого.

### Gaps
- Нет данных о том, снижает ли КАПС-лексика RFC 2119 ошибочное «оправиливание» описаний у LLM.
- Нет инструмента provenance ограничений, рассчитанного на агентов.

---

## 5. Трассировка: требование → тест → код → коммит

### Takeaway
В SDD-фреймворках есть лёгкая трассировка: метки историй в задачах (Spec Kit [US1]), связь tasks → requirements (Kiro), папки изменений (OpenSpec). Есть и проверка покрытия: Spec Kit `/analyze` ловит требования без задач. Связка с тестами и коммитами в них не автоматизирована. Для одного разработчика самый дешёвый вариант гарантии — ID требования в именах тестов и в git-трейлерах, проверяемые скриптом в CI. Тяжёлые инструменты (StrictDoc, sphinx-needs, Doorstop) дают полноценные матрицы, но их стоимость оправдана скорее в регулируемых доменах.

### Cited Findings
- Spec Kit: формат задач `- [ ] [ID] [P?] [Story] Description`, где «[Story] labels map to user stories (US1, US2, US3) for traceability» [П] — [tasks-template.md](https://raw.githubusercontent.com/github/spec-kit/main/templates/tasks-template.md). `/analyze` ищет «Coverage Gaps: Requirement with zero tasks; task unmapped to any requirement» и строит «requirement–task coverage matrix» [П] — [analyze.md](https://raw.githubusercontent.com/github/spec-kit/main/templates/commands/analyze.md).
- Kiro: задачи «traced to requirements» [П] — [martinfowler.com](https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html). Сама документация Kiro упоминает «traceability», но точный синтаксис ссылки на требование на открытой странице не показан [П] — [kiro.dev feature-specs](https://kiro.dev/docs/specs/feature-specs/).
- OpenSpec: `openspec/changes/<change>/` с proposal.md, specs/ (дельты), design.md и tasks.md. `/opsx:archive` переносит изменение в `changes/archive/` с меткой времени [П] — [OpenSpec](https://github.com/Fission-AI/OpenSpec). [И] В результате у каждого изменения есть «папка-след», которую можно связать с коммитами и PR.
- StrictDoc: связи в обе стороны, «RELATION/File» из требования и маркеры `@relation(SDOC-SRS-147, scope=file)` в исходниках. Фича помечена как experimental [В] — [StrictDoc docs](https://strictdoc.readthedocs.io/en/stable/_source_files/tests/integration/features/source_code_traceability/_RELATION_FIELD/test.itest.html).
- Doorstop — CLI и Python API, по одному файлу на требование в Git. sphinx-needs и StrictDoc хранят по одному файлу на документ, sphinx-needs тяжелее [В] — [обзор pistack, 2026](https://www.pistack.xyz/posts/2026-06-15-self-hosted-requirements-management-rmtoo-doorstop-strictdoc/), [sphinx-graph docs](https://sphinx-graph.readthedocs.io/en/main/).

### Inferences
- [И] Лёгкая схема для одного разработчика, по степени гарантии:
  1) ID требований (REQ-012) в spec — артефакт;
  2) ID в имени теста или в аннотации (`test_REQ012_rejects_empty_payload`) — договорённость, которую проверяет CI-скрипт «каждый REQ из spec встречается хотя бы в одном тесте». Это гарантия покрытия по ID, но не по смыслу;
  3) git-трейлер `Refs: REQ-012` в коммитах (`git interpret-trailers`; commit-msg hook или CI отклоняет коммит без трейлера) — гарантия наличия ссылки;
  4) код ← требование через комментарий `@relation`/`REQ-` — обычно избыточно: связь «тест → код» уже даёт покрытие.
- [И] Обратная проверка (тест без требования, задача без требования) ловит scope creep агента. Это делает и Spec Kit `/analyze`, но он LLM-команда, то есть договорённость. Её можно продублировать grep-скриптом.

### Gaps
- Данных о том, что трассировка улучшает результат именно в агентной разработке, нет.
- Формат трейлеров не стандартизован ни в одном из изученных SDD-фреймворков.

---

## 6. Планирование: стратегическое и тактическое, scope, оценка без выдуманных сроков

### Takeaway
SDD-фреймворки разбивают работу на фазы и истории, у каждой есть checkpoint независимой проверки. Нормальной оценки трудоёмкости ни один из них не делает. Оценки на основе собственной истории (EBS: estimate/actual + Monte Carlo) существуют давно (2007), но в агентной разработке нет устойчивых данных даже о знаке эффекта ускорения: METR в 2025 измерил замедление на 19%, а в 2026 сам признал новые данные ненадёжными. Отсюда практичный вывод: считать задачи и замерять собственную пропускную способность по истории, а не просить агента назвать часы.

### Cited Findings
- EBS (Spolsky, 2007; старше 2025): задачи не длиннее 16 часов («Nothing longer than 16 hours»), velocity = estimate ÷ actual по собственной истории разработчика, Monte Carlo на 100 прогонов, на выходе распределение вероятностей даты [П] — [joelonsoftware.com](https://www.joelonsoftware.com/2007/10/26/evidence-based-scheduling/).
- METR, ранний 2025: «developers take 19% longer», хотя ожидали ускорения на 24% и даже после эксперимента считали, что ускорились на 20% [П] — [metr.org 2025-07-10](https://metr.org/blog/2025-07-10-early-2025-ai-experienced-os-dev-study/). В обновлении от февраля 2026 METR называет данные «unreliable signal» из-за selection bias. Участники, которые не хотели работать без AI, отказывались участвовать, а 30–50% не отдавали задачи, которые хотели делать с AI [П] — [metr.org 2026-02-24](https://metr.org/blog/2026-02-24-uplift-update/). [И] Важно для оценки сроков: субъективное ощущение скорости с AI систематически расходится с измерением.
- Spec Kit: фазы Setup → Foundational → User Story 1..N → Polish. После каждой истории — checkpoint «fully functional and testable independently», что позволяет поставить US1 как MVP [П] — [tasks-template.md](https://raw.githubusercontent.com/github/spec-kit/main/templates/tasks-template.md).
- Kiro строит граф зависимостей задач и «волны» параллельного исполнения [П] — [kiro.dev/docs/specs (вендор)](https://kiro.dev/docs/specs/).
- ExecPlan: milestones «independently verifiable», с наблюдаемыми результатами и командами для проверки. Допускаются явные «prototyping milestones», чтобы снять риск [П] — [OpenAI cookbook (вендор)](https://developers.openai.com/cookbook/articles/codex_exec_plans).
- Соразмерность: Anthropic рекомендует пропускать план для однострочных изменений [П] — [best practices](https://code.claude.com/docs/en/best-practices). Kiro даёт Quick Spec без гейтов [П] — [kiro.dev](https://kiro.dev/docs/specs/feature-specs/). Böckeler описывает избыточность полного SDD на мелкой задаче [П] — [martinfowler.com](https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html).

### Inferences
- [И] Варианты оценки без выдуманных сроков:
  а) количество задач или историй и пропускная способность в задачах за неделю, взятая из git-лога или журнала (по сути #NoEstimates);
  б) упрощённый EBS: в журнал записывается «оценка в задачах или часах → фактически», прогноз даётся диапазоном;
  в) запрет на календарные сроки от агента без опоры на историю. Это инструкция, то есть договорённость.
  Данных об эффективности ни одного из вариантов в агентной разработке нет.
- [И] Соразмерность плана ставкам можно задать матрицей «обратимость × радиус поражения». Однострочное изменение идёт без плана. Изменение схемы данных, публичного API или безопасности — через полный цикл spec → clarify → plan → tasks. Эта эвристика собрана из советов вендоров и не измерялась.
- [И] Стратегический план (роадмап, scope, out-of-scope) и тактический (tasks.md на одну фичу) стоит держать в разных артефактах. Out-of-scope записывается явно: этого требуют и Anthropic («state what is out of scope» [П]), и Spec Kit (Assumptions / scope boundaries).

### Gaps
- Нет исследований о точности оценок трудоёмкости, которые даёт сам LLM-агент.
- Источники по #NoEstimates (Zuill, Duarte) в этой сессии не открывались.

---

## 7. Трекинг: где мы относительно плана

### Takeaway
Спектр такой: чекбоксы в markdown (tasks.md в Spec Kit, Kiro, OpenSpec; Progress в ExecPlan) → markdown-задачи с полями (Backlog.md) → граф задач с зависимостями (Beads) → GitHub Issues. Всё это договорённость: агент должен сам отметить прогресс. Гарантию можно получить только внешней проверкой, например hook на Stop сверяет неотмеченные задачи. Данных об эффективности нет, есть только заявления авторов.

### Cited Findings
- ExecPlan: обязательные живые секции «Progress», «Surprises & Discoveries», «Decision Log», «Outcomes & Retrospective» [П] — [OpenAI cookbook (вендор)](https://developers.openai.com/cookbook/articles/codex_exec_plans).
- Beads (Steve Yegge): хранилище на Dolt (`.beads/embeddeddolt/`), `.beads/issues.jsonl` служит форматом обмена. Команды `bd ready` (задачи без открытых блокеров), `bd create`, `bd update --claim`, `bd dep add`, `bd prime`, `bd remember`. Позиционируется как замена markdown-списков, которые «would sprawl endlessly». Есть compaction закрытых задач [П] — [github.com/steveyegge/beads](https://github.com/steveyegge/beads). Типы связей для обнаруженной работы: relates-to, duplicates, supersedes [П]. Связь discovered-from из README подтвердить не удалось.
- Backlog.md: каждая задача — .md-файл в репозитории, с acceptance criteria и Definition of Done (переиспользуемый чеклист по умолчанию), зависимостями и milestones. Интеграция через CLI-инструкции или MCP (Claude Code, Codex, Gemini CLI, Kiro, Cursor). Есть канбан в терминале и веб-интерфейс. Рекомендуемые checkpoint'ы: «Review the spec, Review the plan, Review the code» [П] — [github.com/MrLesk/Backlog.md](https://github.com/MrLesk/Backlog.md).
- Kiro показывает «real-time status updates» задач [П] — [kiro.dev/docs/specs](https://kiro.dev/docs/specs/).
- Claude Code: `/goal` с отдельным оценщиком после каждого хода и Stop hook как детерминированный гейт [П] — [best practices](https://code.claude.com/docs/en/best-practices). Встроенный TodoWrite / список задач в этой сессии по документации я не проверял.

### Inferences
- [И] Критерии выбора для одного разработчика: читаемость человеком без инструмента (markdown выигрывает), зависимости (Beads, Backlog.md), переживание /clear и смены сессии (всё файловое переживает, внутрисессионный todo нет), стоимость установки (Beads требует Dolt).
- [И] Гарантия «план и факт не расходятся»: Stop hook или CI сравнивает чекбоксы в tasks.md с коммитами (трейлер `Task: T012`) или с тестами по ID. Без такой проверки отметки ставит агент на своё усмотрение.

### Gaps
- Нет независимых сравнений Beads, Backlog.md и чистого markdown.

---

## 8. Технический долг и полировка

### Takeaway
Чтобы остатки фиксировались не на усмотрение агента, нужны детерминированные линтеры: TODO без ссылки на задачу запрещён (Ruff TD003, todocheck, eslint-плагины). Такой линтер, запущенный в CI или pre-commit, даёт гарантию формы. Он не ловит долг, который агент не записал вовсе, и упрощения, о которых он промолчал. Это закрывается обязательной секцией «Simplifications / Deferred» в отчёте о задаче и независимым ревью. Полировка закладывается отдельной фазой (в Spec Kit есть фаза «Polish»).

### Cited Findings
- Ruff TD003 missing-todo-link: проверяет, что у TODO есть ссылка на issue [В] — [docs.astral.sh](https://docs.astral.sh/ruff/rules/missing-todo-link). todocheck: «Only TODOs with valid, open issues are allowed to exist in the codebase» [В] — [github.com/mehdy/todocheck](https://github.com/mehdy/todocheck). Аналоги для других стеков: eslint-plugin-todo-plz, jira-todo, todolint (Go) [В] — [todo-plz](https://redirect.github.com/sawyerh/eslint-plugin-todo-plz/blob/main/docs/rules/ticket-ref.md), [todolint](https://beta.pkg.go.dev/github.com/akupila/todolint).
- GIST (Mujahid, Imran, 12.01.2026): 6 540 комментариев со ссылками на LLM, из них 81 — SATD. Темы: отложенное тестирование, неполная адаптация, ограниченное понимание AI-кода [П] — [arXiv 2601.07786](https://arxiv.org/abs/2601.07786).
- По сниппету: SATD, написанный самими AI-агентами (525 комментариев), «slightly more technically detailed» и чаще касается требований и дизайна [В] — [поиск: Univ. Melbourne «An empirical study of SATD in AI agents»](https://findanexpert.unimelb.edu.au/scholarlywork/2345244-an%20empirical%20study%20of%20self-admitted%20technical%20debt%20in%20ai%20agents).
- Spec Kit: в шаблоне задач последняя фаза — «Polish: Cross-cutting improvements» [П] — [tasks-template.md](https://raw.githubusercontent.com/github/spec-kit/main/templates/tasks-template.md). `/analyze` помечает «TODO» и незакрытые placeholders как Ambiguity [П] — [analyze.md](https://raw.githubusercontent.com/github/spec-kit/main/templates/commands/analyze.md).
- Beads заявляет поддержку «discovered work», то есть записи найденного по ходу как новых задач [П] — [beads](https://github.com/steveyegge/beads).
- Anthropic предупреждает об обратном перекосе: ревьюер, которого попросили найти проблемы, их найдёт. «Chasing every finding leads to over-engineering… Tell the reviewer to flag only gaps that affect correctness or the stated requirements» [П] — [best practices](https://code.claude.com/docs/en/best-practices).

### Inferences
- [И] Варианты по силе:
  1) линтер «TODO/FIXME/HACK только с ID задачи» в CI или pre-commit — гарантия формы;
  2) реестр долга (`debt.md` или задачи с меткой debt) плюс CI-проверка, что каждый ID из TODO существует в реестре — гарантия связности;
  3) обязательный раздел отчёта о задаче «Упрощения (сознательные) / Долг (отложено) / Не сделано» — договорённость, которую проверяет Stop hook на наличие секции;
  4) независимый ревьюер-субагент с вопросом «что упрощено или опущено относительно spec» — договорённость, но с независимым взглядом.
- [И] Отличие долга от сознательного упрощения: упрощение — это решение, у которого есть источник (требование или решение человека, см. раздел 4) и условие пересмотра. Долг — отклонение от нормы без такого решения. Формально: у упрощения есть `Source:` и `Revisit-when:`, у долга — ID задачи.
- [И] Полировку лучше заложить в план фазой с фиксированным чеклистом (имена, ошибки, логи, документация API), чем ждать её от агента.

### Gaps
- Нет данных, что TODO-линтеры меняют поведение агентов: агент может просто не писать TODO.
- Автоматическое обнаружение SATD (ML-классификаторы) для агентного кода не оценивалось в найденных источниках.

---

## 9. Финальная самопроверка: работает ли чеклист, если его выполняет тот же агент

### Takeaway
Есть доказательства, что самопроверка той же моделью ненадёжна: blind spot в среднем 64,5% (Self-Correction Bench), 31,7% семантических ошибок, которые модель одобрила сама (модернизация кода, 2026). LLM-судьи ещё и склонны к ложным срабатываниям, причём «сложнее промпт — больше ошибок». Вендор (Anthropic) рекомендует проверку в свежем контексте, детерминированные проверки и показ доказательств вместо утверждений. Чеклист в том же контексте — слабая договорённость. Сильнее работают тесты, линтеры и Stop hook (гарантия формы) плюс ревью субагентом в свежем контексте.

### Cited Findings
- Self-Correction Bench (Ken Tsui, NeurIPS 2025): 14 моделей, «average 64.5% blind spot rate». Модели не исправляют свои ошибки, хотя исправляют такие же ошибки во входных данных пользователя. Если дописать «Wait», blind spot снижается на 89,3% [В] — [arXiv 2507.02778](https://arxiv.org/html/2507.02778v1), [NeurIPS 2025](https://neurips.cc/virtual/2025/122384).
- «Articulate but Wrong» (Reddy, Lolla, Sanku, 20.05.2026): 1 980 попыток, 11 моделей. 31,7% случаев семантического дрейфа (83 из 262) модель-автор пропустила при самопроверке. Распределение бимодальное: от 0% до 100% в зависимости от модели. Перекрёстная согласованность моделей умеренная (r=0,52), и есть ядро задач, на которых падают почти все модели [П] — [arXiv 2605.21537](https://arxiv.org/abs/2605.21537). [И] Перекрёстное ревью другой моделью тоже не панацея.
- Jin & Chen (ASE 2025 NIER): LLM часто ошибочно признают корректный код несоответствующим спецификации. Более сложные промпты (с объяснениями и предложенными исправлениями) повышают долю ошибочных вердиктов [В] — [arXiv 2508.12358](https://arxiv.org/abs/2508.12358). Похожий результат «Systematic Overcorrection in Requirement Conformance Judgement» [В] — [arXiv 2603.00539](https://arxiv.org/abs/2603.00539).
- Anthropic (вендор): «A fresh context improves code review since Claude won't be biased toward code it just wrote». Паттерн Writer/Reviewer. Рекомендация перед тем, как считать задачу готовой, «have a subagent review the diff in a fresh context and report gaps»: проверить diff против PLAN.md, все требования, тесты на edge cases, «nothing outside the task's scope changed». И ещё: «Have Claude show evidence rather than asserting success» [П] — [best practices](https://code.claude.com/docs/en/best-practices).
- Вторичные источники (Qodo, вендор) утверждают, что самоанализ даёт «8x more duplicated code, 39.9% fewer refactors, 37.6% increase in vulnerabilities» [В] — [qodo.ai blog](https://www.qodo.ai/blog/why-ai-self-review-fails-the-technical-case-for-independent-ai-systems). Первичное исследование не установлено, поэтому эти цифры вынесены в раздел «Сомнительное».

### Inferences
- [И] Пункты чеклиста из брифа (нужен ли этот код, остался ли отладочный код, что будет при ошибке или пустых данных, не сломан ли соседний код) разносятся по механизмам:
  - отладочный код, неиспользуемый код, TODO → линтеры и dead-code-анализ в CI (гарантия);
  - «не сломан ли соседний код» → полный прогон тестов в Stop hook или CI (гарантия в пределах покрытия);
  - ошибки и пустые данные → обязательные GWT-сценарии «unwanted behaviour» в spec и тесты на них (гарантия при наличии теста с ID);
  - «нужен ли этот код» и «нет ли изменений вне scope» → ревью субагентом в свежем контексте против spec и diff (договорённость с независимым взглядом) и итоговое ревью человеком.
- [И] Чеклист той же модели в том же контексте — самый слабый вариант (blind spot). Если он всё же нужен, его стоит запускать в свежей сессии или субагенте с узкими критериями (только correctness и требования), чтобы не провоцировать over-engineering.

### Gaps
- Нет контролируемого сравнения «тот же агент + чеклист» против «субагент со свежим контекстом» именно на задачах разработки сервисов.

---

## 10. Сначала поиск существующего решения

### Takeaway
Механизмы — исследовательская фаза в workflow (research.md в Spec Kit по Böckeler; Explore в Claude Code; prototyping milestones и «leveraging existing libraries» в ExecPlan), MCP с актуальной документацией (Context7) и явные инструкции. Все они — договорённости. Количественных данных о том, что они снижают изобретение велосипедов, нет. Наблюдательное исследование агентских PR показывает: агенты часто импортируют библиотеки (29,5% PR), но новые зависимости добавляют редко (1,3%).

### Cited Findings
- Агентские PR (AIDev, 26 760 PR, MSR 2026 Mining Challenge, декабрь 2025): импорт библиотек есть в 29,5% PR, новые зависимости — только в 1,3%. Когда зависимость добавляется, версия указана в 75% случаев [В] — [arXiv 2512.11589](https://arxiv.org/html/2512.11589v1).
- Context7 решает проблему «outdated» и «hallucinated APIs» через MCP-инструменты `resolve-library-id` и `query-docs` и триггер «use context7». В репозитории нет количественных данных об эффективности, только ссылки на YouTube-ролики [П] — [github.com/upstash/context7](https://github.com/upstash/context7).
- Spec Kit создаёт research-заметки как один из примерно 8 файлов спецификации [П] — [martinfowler.com](https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html).
- Anthropic: «Use subagents to investigate … whether we have any existing OAuth utilities I should reuse». Ещё совет указывать агенту источники и существующие паттерны [П] — [best practices](https://code.claude.com/docs/en/best-practices).
- ExecPlan: «leveraging existing libraries when feasible», prototyping milestones [П] — [OpenAI cookbook](https://developers.openai.com/cookbook/articles/codex_exec_plans).

### Inferences
- [И] Варианты:
  1) обязательная секция «Известные решения» в плане: как задача называется в литературе, какие есть библиотеки, стандарты и RFC, почему выбрано или отвергнуто. Это артефакт, и hook может проверить, что секция не пуста;
  2) исследовательский субагент до планирования;
  3) docs-MCP (Context7) для актуальных API;
  4) обратная мера — инструкция и CI-гейт на новые зависимости (лицензия, поддержка), чтобы поиск библиотек не превратился в бесконтрольное добавление пакетов.
- [И] Принцип «если у проблемы есть имя, её уже решили» операционализируется так: первым шагом агент обязан назвать проблему (rate limiting, idempotency keys, outbox pattern…) и найти канонические источники. Данных об эффективности нет.

### Gaps
- Нет исследований, сравнивающих долю самописных реализаций с исследовательской фазой и без неё.

---

## Сомнительное / не проверено

- Qodo: «8x more duplicated code, 39.9% fewer refactors, 37.6% increase in vulnerabilities» при самопроверке. Вендорский блог, первичное исследование не установлено — [qodo.ai](https://www.qodo.ai/blog/why-ai-self-review-fails-the-technical-case-for-independent-ai-systems).
- Self-Correction Bench: 64,5% и «Wait» −89,3% взяты из сниппета и аннотации. Полный текст я не открывал — [arXiv 2507.02778](https://arxiv.org/html/2507.02778v1).
- Ambig-SWE: «models do not proactively ask by default» — пересказ инструмента-резюме, дословной цитаты нет — [arXiv 2502.13069](https://arxiv.org/abs/2502.13069).
- Kiro: точный синтаксис ссылки задачи на требование (вида `_Requirements: 1.2_`) на открытой странице не подтверждён — [kiro.dev](https://kiro.dev/docs/specs/feature-specs/).
- Beads: тип связи «discovered-from» по README не подтверждён. Подтверждены relates-to, duplicates, supersedes — [beads](https://github.com/steveyegge/beads).
- StrictDoc `@relation(…, scope=…)` — по сниппету поиска, функция помечена как experimental.
- SATD в коде AI-агентов (525 комментариев, «slightly more technically detailed») — по сниппету, работу я не открывал.
- AIDev: 29,5% и 1,3% — по сниппету HTML-версии, PDF я не открывал.
- Промышленный кейс генерации Gherkin: 95% и 60% — по сниппету — [arXiv 2504.07244](https://arxiv.org/pdf/2504.07244).
- Встроенный TodoWrite / task list в Claude Code по документации в этой сессии я не проверял.
