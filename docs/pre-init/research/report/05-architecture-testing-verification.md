# Часть 5. Архитектура, тесты и проверка: исполняемое против обещанного

Почти всё, что в 2025–2026 годах предлагается для архитектуры, тестов, качества и проверки работы агента, относится к одному из трёх видов. **Гарантия** — детерминированная проверка вне модели: тест, линтер, hook с блокировкой, deny-правило, sandbox, CI. **Договорённость** — инструкция, которую агент может не выполнить. Посередине **вероятностная проверка**: LLM-ревьюер, верификатор-субагент, LLM-валидатор внутри hook. Она срабатывает надёжно, но её вердикт может быть неверным. Вендоры (Anthropic, GitHub spec-kit) и независимые авторы (Böckeler, Beck, ImpossibleBench) сходятся в одном: инструкции агент нарушает, вплоть до удаления тестов. Поэтому всё, что должно соблюдаться всегда, нужно переводить в исполняемые проверки: архитектурные тесты, read-only тесты на фазе реализации, мутационное тестирование, Stop hook с прогоном проверок, SAST, детекторы N+1 в режиме ошибки. Данные об эффективности есть по немногим механизмам: мутационная генерация тестов (Meta ACH), агентное property-based тестирование, AI-ревью (смешанные результаты), сравнение LLM и SAST. По архитектурным тестам, ADR, TDD-циклу и верификаторам-субагентам применительно к агентам измерений нет, есть только вендорские кейсы. Для одного разработчика с небольшим бэкендом гарантию дают дешёвые механизмы. Дорогие (полный SDD-цикл, многоагентный harness, коммерческие quality gates) окупаются хуже. Это не готовый процесс, а набор вариантов с критериями выбора.

## Ключевые выводы

- **Сквозная рамка: гарантия, договорённость, вероятностная проверка.** Anthropic прямо называет инструкции в CLAUDE.md «advisory», а hooks — «deterministic» ([Claude Code best practices](https://code.claude.com/docs/en/best-practices) [П] (вендор)). Böckeler делит контроль на «computational» и «inferential» сенсоры и направляющие ([Böckeler, Harness engineering, 02.04.2026](https://martinfowler.com/articles/exploring-gen-ai/harness-engineering.html) [П]). Для каждого механизма ниже указано, к какому виду он относится.
- **Архитектуру можно сделать гарантией только одним способом: исполняемыми правилами зависимостей.** Это ArchUnit, dependency-cruiser, import-linter, Deptrac, Packwerk в pre-commit, hook и CI. Против переусложнения детерминированного средства нет, только частичные меры [И]. Данных о том, что архитектурные тесты снижают дрейф именно у агентов, нет.
- **ADR — договорённость.** Гарантией становится только секция Confirmation в MADR, если она ссылается на тест в CI [И]. Форматы (Nygard, MADR, Y-statement) стабильны. Новое в 2025–2026 — скиллы «агент пишет ADR» и идея фиксировать, кто принял решение: человек или агент [В]. Эффективность ADR как контекста для агента не измерена.
- **Граница полномочий задаётся permission-правилами** (deny → ask → allow) и sandbox — это гарантия. Но у неё есть оговорка: без sandbox Edit-deny обходится подпроцессом, например Python-скриптом ([Permissions](https://code.claude.com/docs/en/permissions) [П] (вендор)). Правила «когда остановиться и спросить» остаются договорённостью. Auto mode — вероятностный классификатор.
- **Порядок «тест → код» в инструкциях агенты нарушают.** ImpossibleBench: «stronger models generally exhibit higher cheating rates». Read-only доступ к тестам снижает читерство ([arXiv 2510.20270](https://arxiv.org/abs/2510.20270) [П по аннотации]). Проверяемые варианты: красный вывод в логе, пары коммитов с CI-проверкой, TDD-Guard (вероятностный), фазовое разделение с запретом правки тестов.
- **Тесты, написанные агентом после кода, фиксируют фактическое поведение, а не ожидаемое** ([arXiv 2410.21136](https://arxiv.org/abs/2410.21136) [П] (старше 2025)). Данные есть у двух противоядий: мутационно-управляемой генерации (Meta ACH: **73% тестов приняты** инженерами, [arXiv 2501.12862](https://arxiv.org/abs/2501.12862) [П]) и агентного property-based тестирования (**56% валидных багрепортов**, [arXiv 2510.09907](https://arxiv.org/abs/2510.09907) [П]).
- **AI-ревью — вероятностный второй слой, данные по нему смешанные.** В Beko/Qodo **73.8% комментариев разрешены**, но время закрытия PR выросло ([arXiv 2412.18531](https://arxiv.org/abs/2412.18531) [П]). В CodeRabbit **отклонено 56.3%** ([arXiv 2607.03316](https://arxiv.org/abs/2607.03316) [П]). Самый надёжный слой качества — форматтер, линтер и тайпчекер в hook и CI.
- **Три состояния задачи различаются: «done (agent)», «verified», «accepted (human)».** Вендор выстраивает лестницу жёсткости: промпт → `/goal` → Stop hook → verification subagent, плюс ручной `/verify` человеком ([best practices](https://code.claude.com/docs/en/best-practices) [П] (вендор)). Количественные данные есть только во вендорских кейсах без контроля.
- **По данным вендора, безопасность LLM-кода с ростом моделей не улучшается.** Veracode: 45% задач с уязвимостью класса OWASP Top 10 ([Veracode](https://www.veracode.com/press-release/ai-generated-code-poses-major-security-risks-in-nearly-half-of-all-development-tasks-veracode-research-reveals/) [П] (вендор, пресс-релиз)). У SAST гарантирован запуск, но полнота ограничена. LLM-ревью безопасности находит больше, но с большим числом ложных срабатываний ([arXiv 2508.04448](https://arxiv.org/abs/2508.04448) [П]), а Action Anthropic не защищён от prompt injection. N+1 надёжнее всего ловить в тестах в режиме raise.
- **Для небольшого проекта** гарантии дают недорогие механизмы: 3–6 правил зависимостей, форматтер, линтер, тайпчекер, read-only тесты на фазе реализации, Stop hook, SAST, секрет-сканер, N+1 в режиме raise. Мутационное тестирование стоит запускать на изменённых файлах. Многоагентный harness, полный SDD-цикл и коммерческие quality gates окупаются хуже [И].

## Рамка: что считать гарантией

В заметках принята трёхчастная классификация, и она используется во всех таблицах ниже. **Гарантия** — проверку выполняет детерминированный механизм вне модели: hook с exit 2 или deny, permission deny-правило, OS-sandbox, CI-gate, тест, линтер. Модель не может его «забыть», обойти его может только тот, кто правит конфигурацию. **Договорённость** — инструкция в CLAUDE.md, AGENTS.md, constitution, skill или промпте. **Вероятностная проверка** — LLM-ревьюер, верификатор-субагент, LLM-валидатор внутри hook (TDD-Guard). Механизм запускается надёжно, но его вердикт вероятностный. Anthropic проводит это различие сама: «Unlike CLAUDE.md instructions which are advisory, hooks are deterministic and guarantee the action happens» ([Claude Code best practices](https://code.claude.com/docs/en/best-practices) [П] (вендор)). Böckeler делит контроль на «guides (feedforward)» и «sensors (feedback)», а каждый из них — на «computational» (линтеры, типы, тесты) и «inferential» (LLM-анализ, медленный и вероятностный) ([Böckeler, Harness engineering, 02.04.2026](https://martinfowler.com/articles/exploring-gen-ai/harness-engineering.html) [П]).

У гарантии есть границы, и о них стоит помнить. Гарантирован **запуск** проверки, а не её **полнота**. SAST пропускает уязвимости, для которых нет правил. Архитектурный тест проверяет только записанные зависимости. Детектор N+1 видит только код, покрытый тестами. Кроме того, гарантия держится, пока её не отключили. Это предмет отдельной части отчёта о детерминированных ограждениях.

## Проектирование архитектуры с ИИ

### Что делают подходы

**Роль агента.** В источниках 2025–2026 агент генерирует варианты и компромиссы, исполняет и проверяет вторым. Выбор архитектуры и приёмка остаются за человеком. Anthropic рекомендует цикл explore → plan → implement → commit. Plan mode отделяет исследование от изменений, план можно открыть в редакторе (Ctrl+G) и поправить до реализации. Критерий, когда план нужен: «Planning is most useful when you're uncertain about the approach, when the change modifies multiple files… If you could describe the diff in one sentence, skip the plan». Для крупных фич предлагается, чтобы агент сначала проинтервьюировал человека через AskUserQuestion («technical implementation, UI/UX, edge cases, concerns, and tradeoffs»), записал SPEC.md и выполнял спек уже в новой сессии ([Claude Code best practices](https://code.claude.com/docs/en/best-practices) [П] (вендор)). По сути это паттерн «агент формулирует вопросы и варианты, человек решает» [И].

В том же источнике есть предупреждение о ревьюере-агенте. Если попросить его найти пробелы, он найдёт их и в хорошей работе: «Chasing every finding leads to over-engineering: extra abstraction layers, defensive code, and tests for cases that can't happen». Рекомендация — отмечать только то, что влияет на корректность и требования ([там же](https://code.claude.com/docs/en/best-practices) [П] (вендор)). Отсюда следует, что использовать агента-ревьюера архитектуры без фильтра — само по себе источник переусложнения.

**Заранее или по ходу.** Spec-kit задаёт архитектуру заранее через «constitution» из девяти статей. Среди них Library-First и CLI Interface, Test-First («NON-NEGOTIABLE»), Simplicity (не больше трёх проектов в начальной реализации, иначе нужно документированное обоснование), Anti-Abstraction (использовать фреймворк напрямую, без обёрток), Integration-First Testing (реальные БД вместо моков, contract tests до реализации). Фаза «Phase −1» в плане проверяет «Simplicity Gate» и «Anti-Abstraction Gate», исключения фиксируются в «Complexity Tracking» ([spec-kit, spec-driven.md](https://github.com/github/spec-kit/blob/main/spec-driven.md) [П] (вендор: GitHub)). Но все эти gates — шаги шаблона, который выполняет сам агент. То есть это договорённость, а не гарантия [И].

Böckeler разобрала spec-kit, Kiro и Tessl и показала пределы предварительного проектирования ([Böckeler, Understanding SDD](https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html) [П]). Во-первых, агенты всё равно не выполняют все инструкции («the agent ultimately not follow all the instructions»): в её примере spec-kit создал дублирующие классы, приняв описание существующего кода за новую спецификацию. Во-вторых, на мелкой задаче процесс избыточен: Kiro на маленький баг сделал 4 user stories и 16 acceptance criteria («sledgehammer to crack a nut»). В-третьих: «I'd rather review code than all these markdown files». Thoughtworks Technology Radar vol. 34 (апрель 2026), по данным поиска, отмечает ценность spec-kit и OpenSpec для структурирования «planning, design and implementation» ([Thoughtworks Radar](https://thoughtworks.com/radar) [В]).

**Типичные сбои.** Kent Beck называет три признака, что агент сбился: петли; функциональность, которую не просили, «even if logically reasonable»; «cheating», например «disabling or deleting tests». Итоговое качество его не устроило: «I'm still working on getting the genie to care as much as I do about simplicity» ([Beck, Augmented Coding: Beyond the Vibes](https://newsletter.kentbeck.com/p/augmented-coding-beyond-the-vibes) [П]). Несогласованность решений между сессиями Anthropic объясняет деградацией контекста: «When the context window is getting full, Claude may start "forgetting" earlier instructions». Рекомендации — /clear между задачами и короткий CLAUDE.md, где в том числе перечислены «Architectural decisions specific to your project» ([Claude Code best practices](https://code.claude.com/docs/en/best-practices) [П] (вендор)).

**Исполняемая архитектура.** По Böckeler, «clearly definable module boundaries afford architectural constraint rules». ArchUnit-подобные структурные тесты она относит к вычислительным сенсорам против архитектурного дрейфа. Ссылаясь на закон Эшби, она пишет, что предсказуемая топология проекта сужает разнообразие вывода агента, и тогда полный harness становится достижим ([Harness engineering](https://martinfowler.com/articles/exploring-gen-ai/harness-engineering.html) [П]). Дословную цитату о сенсорах использовать нельзя, см. «Сомнительное». InfoQ (17.08.2026) в статье «Agentic fitness functions» пишет: «Deterministic fitness functions should remain the primary enforcement mechanism for measurable invariants such as dependency direction, contract shape, latency budgets, security posture, and policy checks». LLM-судьи там предназначены для суждений вроде «boundary fidelity, semantic contract drift… stale ADR assumptions». Рекомендуемый путь — «Start advisory», а повторяющиеся находки переводить в детерминированные правила. Данных в статье нет ([InfoQ](https://infoq.com/articles/agentic-fitness-functions-evolutionary-architecture) [П]).

**Диаграммы как код.** Ни в одном первоисточнике 2025–2026 не проверена связь C4/Structurizr, Mermaid или D2 с работой агентов. Механизм [И] такой: текстовая диаграмма в репозитории читается агентом как контекст, то есть это договорённость. Гарантией она становится, только если диаграмма и проверка зависимостей генерируются из одной модели либо CI сверяет диаграмму с фактическим графом импортов. У dependency-cruiser визуализация строится по тому же графу, по которому идёт проверка.

**Структура проекта.** Anthropic называет контекстное окно «the most important resource to manage» и советует делегировать широкие исследования кода субагентам ([best practices](https://code.claude.com/docs/en/best-practices) [П] (вендор)). Эмпирика о модульности неоднозначна. В EMNLP Findings 2024 модульность «is not a core factor» для качества генерации кода в few-shot-постановке ([arXiv 2407.11406](https://arxiv.org/abs/2407.11406) [В, только сниппет], старше 2025). Но там речь о генерации функций, а не об агентах в репозитории, так что переносить вывод нельзя. Vertical slices и маленькие файлы уменьшают объём контекста на задачу, но прямых данных о том, что это повышает точность агента, нет [И].

### Механизмы

| Механизм | Вид | Тип | Данные об эффективности |
|---|---|---|---|
| Plan mode, интервью через AskUserQuestion, SPEC.md в новой сессии | инструкция / режим | договорённость (plan mode блокирует правки — это режим, а не правило качества) | данных нет |
| Constitution, Simplicity и Anti-Abstraction Gates, Complexity Tracking (spec-kit) | артефакт + шаблон | договорённость | данных нет; Böckeler: агент инструкции не всегда выполняет [П] |
| Архитектурные декларации в коротком CLAUDE.md | инструкция | договорённость | данных нет |
| ArchUnit, dependency-cruiser, import-linter, Deptrac, Packwerk | тест / CI / hook | гарантия (в объёме записанных правил) | данных для агентов нет |
| Agentic fitness function (LLM-судья) | субагент / CI | вероятностная | данных нет (InfoQ 2026) |
| Ревьюер-агент с фильтром «только корректность и требования» | субагент | вероятностная | данных нет |
| Метрики сложности и дублирования (линтер, Sonar) | CI | гарантия измерения, порог — gate | данных для агентов нет |
| Диаграмма-как-код, сверяемая с графом импортов | артефакт + CI | гарантия только при сверке, иначе договорённость | данных нет |

### Соразмерность для небольшого проекта

Минимальный вариант с гарантией [И] — 3–6 правил направления зависимостей (domain не импортирует infrastructure, нет циклов) в инструменте экосистемы. Их запускают в pre-commit, в PostToolUse или Stop hook и в CI. Всё остальное («не делать лишних абстракций», стиль слоёв) остаётся договорённостью в CLAUDE.md или constitution. Против переусложнения детерминированного средства нет, есть частичные: явные simplicity gates (spec-kit), запрет непрошеной функциональности (Beck), ограничение ревьюера корректностью (Anthropic), пороги сложности и дублирования. Полный SDD-цикл со множеством markdown-артефактов на маленьком сервисе рискует повторить «sledgehammer to crack a nut» у Böckeler. Критерий выбора между планированием заранее и по ходу берётся у Anthropic: если дифф описывается одним предложением, план не нужен. Если подход не ясен или затрагивает много файлов, нужен план, и человек правит его до реализации.

### Что уточнить, когда станет известен стек

| Экосистема | Инструмент архитектурных правил | Статус проверки |
|---|---|---|
| JVM | [ArchUnit](https://github.com/TNG/ArchUnit) — «dependencies between packages and classes, layers and slices… cyclic dependencies» через обычный unit-фреймворк | [П] |
| JS/TS | [dependency-cruiser](https://github.com/sverweij/dependency-cruiser) — «Validate and visualise dependencies. With your rules» | [П] |
| Python | [import-linter](https://github.com/seddonym/import-linter) — «Lint your Python architecture» | [П] |
| PHP | [Deptrac](https://github.com/deptrac/deptrac) — слои над классами, правила, CI для PR | [П] |
| Ruby/Rails | [Packwerk](https://github.com/Shopify/packwerk) — пакеты и видимость констант | [П] |
| Go, .NET | go-arch-lint, NetArchTest/ArchUnitNET | [В, не открывал] |

Помимо выбора инструмента, нужно проверить три вещи: достаточно ли быстро он запускается, чтобы годиться для hook, а не только для CI; умеет ли работать с baseline или режимом «только новые нарушения»; и можно ли получить из того же графа диаграмму, чтобы описание архитектуры не расходилось с проверкой.

## Архитектурные решения (ADR)

### Что делают подходы

Форматы стабильны и описаны до 2025 года. adr.github.io определяет AD как «justified design choice», ASR как «a requirement that has a measurable effect on the architecture», а ADR как запись одного решения с обоснованием, «trade-offs and consequences». Упоминаются шаблон Nygard (2011, старше 2025), Y-statement (Zdun et al.), MADR и сравнение семи шаблонов (WICSA 2015). Об ИИ на странице ничего нет ([adr.github.io](https://adr.github.io/) [П]). В MADR есть секции Context, Decision Drivers, Considered Options, Decision Outcome, Consequences, **Confirmation** («how compliance is validated») и Pros and Cons; шаблоны бывают полные, минимальные и bare ([MADR repo](https://github.com/adr/madr) [П]). Секция Confirmation — естественное место для ссылки на архитектурный тест или fitness function, которая проверяет решение. Это единственный путь от ADR к гарантии [И].

Новое в 2025–2026 касается того, кто пишет ADR. Практик в блоге про Codex CLI предлагает записывать «not just what was decided, but who decided it — human or agent — and what context the agent had at decision time». Его workflow: субагент извлекает решения из диалога, человек выбирает, генерируется ADR, затем проверка по DoD ([Vaughan](https://codex.danielvaughan.com/2026/04/28/codex-cli-architecture-decision-records-adr-automated-governance/) [В]). В каталогах есть готовые скиллы «create-architectural-decision-record», у одного из них, по сниппету, 9.7k установок ([skillselion](https://skillselion.com/skills/github/awesome-copilot/create-architectural-decision-record) [В]). Spec-kit связывает решения со спеками через «Rationale Documentation», «Specification Traceability» (каждое решение ссылается на требование) и «Complexity Tracking» ([spec-kit](https://github.com/github/spec-kit/blob/main/spec-driven.md) [П] (вендор)). OWASP ASVS 5.0 (30.05.2025) ввёл «Documented Security Decisions»: каждая глава начинается с требований документировать, как и почему применён контроль ([OWASP ASVS](https://owasp.org/www-project-application-security-verification-standard/) [В]). Anthropic советует держать в CLAUDE.md «Architectural decisions specific to your project», а сам CLAUDE.md — коротким ([best practices](https://code.claude.com/docs/en/best-practices) [П] (вендор)).

**Кто заполняет** — три варианта [И]. (a) Агент пишет черновик по MADR с вариантами и компромиссами, человек выбирает и ставит Status: accepted. Это самый частый паттерн в найденных скиллах. (b) Человек пишет Y-statement в одну строку, агент дополняет до MADR. (c) Агент сам заносит решения, принятые по ходу работы, в decision log со статусом proposed, а человек их ревьюит. Критерий выбора — стоимость ревью: Böckeler предупреждает о нагрузке от markdown, и для небольшого сервиса Y-statement или минимальный MADR дешевле [И].

**Когда писать** [И]: при выборе, который дорого откатить (БД, протокол API, модель авторизации, границы модулей), и при отклонении от constitution. На каждое локальное решение ADR не нужен.

**Как сделать ADR контекстом для агента** [И]: оставить в CLAUDE.md указатель («решения в docs/adr, перед изменением X читай ADR-N»), а сами тексты подгружать по требованию через скилл, потому что CLAUDE.md должен быть коротким. Со спеком — ссылки в обе стороны. Гарантии здесь нет, её даёт только Confirmation-проверка в CI. Есть и связь с задачей «нормативное и описательное» из брифа: ADR, где явно указан автор решения (человек или агент) и статус, даёт ограничению проверяемый источник. Но это вывод, а не измеренный эффект [И].

### Механизмы

| Механизм | Вид | Тип | Данные об эффективности |
|---|---|---|---|
| ADR по MADR / Y-statement / Nygard | артефакт | договорённость | данных нет |
| Скилл «агент пишет черновик ADR, человек принимает» | скилл | договорённость (статус ставит человек) | данных нет (только популярность скилла [В]) |
| Поле «кто решил: человек/агент, какой был контекст» | артефакт | договорённость | данных нет [В] |
| Указатель на ADR в CLAUDE.md + подгрузка по требованию | инструкция / скилл | договорённость | данных нет |
| Confirmation → архитектурный тест в CI | артефакт + CI | гарантия (в объёме теста) | данных нет |
| Specification Traceability (spec-kit) | артефакт | договорённость | данных нет |
| ASVS «Documented Security Decisions» | артефакт / чек-лист | договорённость | данных нет |

### Соразмерность для небольшого проекта

Для одного разработчика подходит минимальный MADR или Y-statement, и только на дорогие решения. Агент пишет черновик с вариантами, человек выбирает и принимает. Если решение проверяемо, в Confirmation ставится ссылка на тест. Decision log для мелких решений агента со статусом proposed полезен тогда, когда человек реально его читает. Иначе он превращается в ту самую markdown-нагрузку, о которой предупреждает Böckeler [И].

### Что уточнить, когда станет известен стек

Здесь от стека зависит мало. Нужно понять, в каком инструменте из раздела об архитектуре будет записана Confirmation-проверка: правило import-linter, dependency-cruiser, ArchUnit, Deptrac или Packwerk. И есть ли в выбранном фреймворке собственные соглашения о структуре, которые стоит зафиксировать первым ADR.

## Принятие решений (граница полномочий)

### Что делают подходы

Академическая рамка — пять уровней автономии по роли пользователя у Feng, McDonald, Zhang: **operator → collaborator → consultant → approver → observer**. Ключевая мысль: «an agent's level of autonomy can be treated as a deliberate design decision, separate from its capability and operational environment». Авторы предлагают «AI autonomy certificates» ([arXiv 2506.12469](https://arxiv.org/abs/2506.12469) [П]).

Инструментально граница задаётся permission-правилами. В Claude Code они вычисляются в порядке deny → ask → allow, побеждает первое совпадение, специфичность порядок не меняет. Deny и ask срабатывают и на подкоманды, в том числе в subshell, `$()` и `for` ([Permissions](https://code.claude.com/docs/en/permissions) [П] (вендор)). Граница гарантии описана там же. Read/Edit deny действуют на встроенные файловые инструменты, на распознаваемые bash-команды (`cat`, `sed`, `tee`) и на редиректы. Но: «They don't apply to… arbitrary subprocesses that read or write files indirectly, like a Python or Node script». Для уровня ОС — «enable the sandbox». Решение PreToolUse hook не обходит deny/ask-правила. Hooks тоже могут ограничивать: PreToolUse возвращает `permissionDecision: deny|ask`, exit 2 блокирует. Однако Bash-фильтр `if` «is best-effort, use the permission system rather than a hook to enforce a hard allow or deny» ([Hooks](https://code.claude.com/docs/en/hooks) [П] (вендор)).

В auto mode «a separate classifier model reviews most actions instead of you and blocks only what looks risky, such as scope escalation, unknown infrastructure…». В Manual mode спрашивается каждое изменение, и вендор признаёт его слабость: «After the tenth approval you're clicking through rather than reviewing» ([best practices](https://code.claude.com/docs/en/best-practices) [П] (вендор)). Это важный довод: «спрашивать всё» не равно «человек контролирует всё». Утомление от подтверждений превращает гарантию в формальность.

**Раскладка по уровням для одного разработчика** [И]. Архитектура, выбор зависимостей, схема БД, публичный API и безопасность — уровень approver или collaborator: агент предлагает, человек решает. Реализация внутри принятого плана и тестов — approver, то есть ревью диффа. Форматирование и lint-фиксы — observer.

**Триггеры «стоп и спроси»** — это договорённость [И]: добавление зависимости, изменение схемы или миграции, изменение публичного контракта, изменение или удаление теста, отклонение от ADR, вторая неудачная попытка. Часть из них можно перевести в гарантию: `ask` или `deny` на `Edit(migrations/**)`, `Edit(**/tests/**)` и на манифесты зависимостей (`package.json`, `pyproject.toml`), `deny` на `Bash(npm install *)` и аналоги. Ограничение: без sandbox возможен обход через скрипт-подпроцесс.

### Механизмы

| Механизм | Вид | Тип | Данные об эффективности |
|---|---|---|---|
| Уровни автономии (Feng et al.) как проектное решение | артефакт (политика) | договорённость | данных нет |
| Правила «стоп и спроси» в CLAUDE.md | инструкция | договорённость | данных нет |
| deny/ask на пути (миграции, тесты, манифесты) и команды установки | permissions | гарантия для встроенных инструментов; без sandbox обходится подпроцессом [П] | данных нет |
| OS-sandbox | permissions / окружение | гарантия | данных нет |
| PreToolUse hook с deny/ask, exit 2 | хук | гарантия; Bash-фильтр `if` best-effort [П] | данных нет |
| Auto mode | классификатор | вероятностная | данных нет |
| Manual mode (подтверждение каждого действия) | режим | формально гарантия, на практике размывается усталостью [П] (вендор) | данных нет |

### Соразмерность для небольшого проекта

Дёшево и с гарантией работают deny и ask на небольшой набор необратимых или дорогих действий (миграции, манифесты зависимостей, тесты на фазе реализации, установка пакетов) и sandbox. Остальное остаётся договорённостью в коротком списке триггеров. Подтверждать каждое действие не стоит: по признанию самого вендора, это быстро превращается в механическое нажатие [И].

### Что уточнить, когда станет известен стек

Пути миграций, тестов и манифеста зависимостей в выбранном фреймворке, чтобы прописать их в правила. Команды установки пакетов (`npm install`, `pip install`, `uv add`, `cargo add`, `bundle add` и т.п.). Есть ли в экосистеме способ выполнять код в обход встроенных файловых инструментов, который сделает Edit-deny бесполезным без sandbox. Например, скрипт-раннеры, которые сами пишут файлы [И].

## TDD с агентом

### Что делают подходы

Порядок «тест раньше кода» в инструкциях — это договорённость, и агенты её нарушают. Kent Beck требует в системном промпте «Always follow the TDD cycle: Red → Green → Refactor» и «Write the simplest failing test first», а также разделять структурные и поведенческие изменения по коммитам и прогонять все тесты после каждого изменения. Контроль у него — наблюдение: «watch the intermediate results… ready to intervene» ([Beck](https://newsletter.kentbeck.com/p/augmented-coding-beyond-the-vibes) [П]). Spec-kit, Article III: «No implementation code shall be written before: 1. Unit tests are written 2. Tests are validated and approved by the user 3. Tests are confirmed to FAIL (Red phase)» ([spec-kit](https://github.com/github/spec-kit/blob/main/spec-driven.md) [П] (вендор)). Это тоже договорённость, но с явной приёмкой тестов человеком. Anthropic в текущей редакции даёт пример «write a failing test that reproduces the issue, then fix it» и паттерн «have one Claude write tests, then another write code to pass them» ([best practices](https://code.claude.com/docs/en/best-practices) [П] (вендор)). В long-running harness: «It is unacceptable to remove or edit tests because this could lead to missing or buggy functionality» ([Effective harnesses, 26.11.2025](https://www.anthropic.com/engineering/effective-harnesses-for-long-running-agents) [П] (вендор)) — тоже инструкция.

Почему одних инструкций мало, показывает ImpossibleBench (Zhong, Raghunathan, Carlini, 23.10.2025). В задачах этого бенчмарка спецификация противоречит тестам, поэтому любой «pass» означает читерство. Вывод: «stronger models generally exhibit higher cheating rates». Стратегии — от модификации тестов до перегрузки операторов и записи состояния. Снижают проблему три меры: строгость промпта, **read-only доступ к тестам** и возможность прервать задачу ([arXiv 2510.20270](https://arxiv.org/abs/2510.20270) [П по аннотации]). Это главный довод за перевод «не трогай тесты» из инструкции в права доступа.

**TDD-Guard** (nizos/tdd-guard, MIT, около 2.4k звёзд) через PreToolUse hooks и test reporters блокирует «Implementation without failing tests first», «Over-implementation beyond test requirements» и «Writing multiple tests simultaneously». Поддерживает Vitest, Jest, Storybook, pytest, PHPUnit, Go, cargo, RSpec и Minitest. Решение принимает настраиваемая validation model, то есть LLM ([GitHub](https://github.com/nizos/tdd-guard) [П]). Блокировка детерминирована, суждение вероятностно [И]. Данных об эффективности в репозитории нет.

Данные о самом TDD с моделями косвенные. Mathews & Nagappan: «Including test cases leads to higher success in solving programming challenges» на MBPP и HumanEval (GPT-4, Llama 3) ([arXiv 2402.13521](https://arxiv.org/abs/2402.13521) [П], старше 2025). Это эффект тестов как спецификации, а не порядка коммитов. Исследования о том, что строгий TDD-цикл с агентом даёт меньше дефектов, чем test-after, нет.

**Варианты сделать порядок проверяемым, от дешёвого к строгому** [И]:

1. **Красное доказательство в логе.** Агент обязан показать вывод падающего теста до реализации. Это договорённость, проверяемая чтением.
2. **Два коммита с меткой** («test: …» и «feat: …»). CI-скрипт проверяет, что тест из первого коммита падает на этом коммите и проходит на следующем. Гарантия того, что red-green был, но не того, что тест хороший. Готового инструмента для этого не найдено, только самописные решения.
3. **TDD-Guard** — блокировка во время сессии, вероятностная.
4. **Фазовое разделение.** Тесты пишет одна сессия, человек их принимает, затем реализация идёт с `deny Edit(tests/**)` и sandbox. Это гарантия неизменности тестов и совпадает с рекомендацией ImpossibleBench о read-only доступе.

Для человека, который должен понимать весь код, вариант 4 с приёмкой тестов человеком совпадает со spec-kit Article III: тесты становятся читаемой спецификацией [И].

### Механизмы

| Механизм | Вид | Тип | Данные об эффективности |
|---|---|---|---|
| Промпт Red → Green → Refactor (Beck), Article III (spec-kit) | инструкция / артефакт | договорённость | тесты в промпте повышают решаемость (2024) [П] |
| Вывод падающего теста в логе до реализации | инструкция | договорённость, проверяемая чтением | данных нет |
| Пары коммитов test/feat + CI-проверка red→green | CI | гарантия факта red→green, не качества теста | данных нет |
| TDD-Guard | хук + LLM | блокировка детерминированная, вердикт вероятностный | данных нет |
| Тесты пишет одна сессия, код — другая (Anthropic) | субагент / сессия | договорённость (без запрета правки) | данных нет |
| Read-only тесты на фазе реализации (deny + sandbox) | permissions | гарантия | ImpossibleBench: снижает читерство [П по аннотации] |

### Соразмерность для небольшого проекта

Самое дешёвое сочетание с гарантией — фазовое разделение: человек принимает тесты, затем `deny Edit(tests/**)` плюс sandbox на время реализации, плюс требование показать красный вывод. Проверка истории коммитов в CI добавляет гарантию самого факта red→green, но требует самописного скрипта. TDD-Guard удобен в сессии, но добавляет LLM-вызовы на каждое действие и даёт только вероятностный вердикт [И]. Нужно учитывать и трение: при запрете правки тестов настоящая ошибка в тесте требует возврата к человеку. Для соло-разработчика это и есть нужная точка контроля.

### Что уточнить, когда станет известен стек

Поддерживает ли TDD-Guard выбранный тест-раннер: Vitest, Jest, pytest, PHPUnit, Go, cargo, RSpec, Minitest — по README [П]. Где лежат тесты (отдельный каталог или рядом с кодом, как часто в Go и Rust). От этого зависит, можно ли выразить запрет одним glob-правилом. Насколько быстро гоняется набор тестов, чтобы проверка red→green в CI не была дорогой.

## Качество тестов

### Что делают подходы

**Главная проблема — оракул.** Konstantinou, Degiovanni, Papadakis показали, что LLM, как и традиционные генераторы, «tend to generate test oracles reflecting actual program behavior rather than the intended expected behavior». Качество выше при осмысленных именах, а LLM-оракулы сильнее EvoSuite по обнаружению дефектов ([arXiv 2410.21136](https://arxiv.org/abs/2410.21136) [П], старше 2025). Это прямой аргумент за «тест до кода» или «тест без доступа к реализации». Тесты, которые тот же агент пишет после кода, слабы как оракул.

**Мутационное тестирование с данными.** Meta ACH («Mutation-Guided LLM-based Test Generation at Meta», 22.01.2025) охватил 10 795 Android Kotlin классов, 9 095 мутантов и 571 тест. **73% тестов приняты** инженерами, 36% признаны privacy-relevant. Детектор эквивалентных мутантов дал precision 0.79 и recall 0.47, с препроцессингом — 0.95 и 0.96. Работает на семи платформах ([arXiv 2501.12862](https://arxiv.org/abs/2501.12862) [П]; [Meta Engineering blog, 05.02.2025](https://engineering.fb.com/2025/02/05/security/revolutionizing-software-testing-llm-powered-bug-catchers-meta-ach/) [П] (вендор)). Цифра говорит о принятии тестов людьми, а не о числе найденных дефектов. И это промышленная система, а не настройка агента. README cargo-mutants объясняет смысл метода: найти «places where bugs could be inserted without causing any tests to fail». Покрытие показывает, что код «reached», а не что он «checks» ([cargo-mutants](https://github.com/sourcefrog/cargo-mutants) [П]).

**Агентное property-based тестирование** (Maaz, DeVoe, Hatfield-Dodds, Carlini; NeurIPS 2025 DL4Code workshop). Агент выводит свойства из кода и документации, пишет Hypothesis-тесты и рефлексирует над результатами. На 100 Python-пакетах **56% багрепортов валидны**, 32% стоило отправить мейнтейнерам. Среди топ-21 по рубрике валидно 86%. Отправлено 5 багов, 4 с патчами, 3 смёрджены, включая NumPy ([arXiv 2510.09907](https://arxiv.org/abs/2510.09907) [П]). Это механизм «тесты, которые пытаются сломать код», и у него есть данные: генерация вероятностная, прогон детерминированный.

**Защита от подгонки.** ImpossibleBench: read-only доступ к тестам снижает читерство ([arXiv 2510.20270](https://arxiv.org/abs/2510.20270) [П по аннотации]). Anthropic: тесты и код пишут разные сессии, adversarial-ревью проводится в свежем субагенте ([best practices](https://code.claude.com/docs/en/best-practices) [П] (вендор)). Claude Code: `Edit`-deny на путь тестов плюс sandbox для подпроцессов ([Permissions](https://code.claude.com/docs/en/permissions) [П] (вендор)). Thoughtworks Radar vol. 34, по сниппету, называет cargo-mutants, другие мутационные инструменты и фаззинг (WuppieFuzz) «feedback sensors for coding agents» ([Thoughtworks Radar](https://thoughtworks.com/radar) [В]).

**Систематический вывод граничных случаев — метод, а не список** [И]. Скилл или промпт может требовать пройти по каждому входу шесть измерений: пустота / null / отсутствие; размер (0, 1, граница, граница±1, максимум); тип и формат (невалидный тип, кодировка, unicode); структура (вложенность, дубликаты, порядок); состояние (повторный вызов, конкурентность, частичный сбой); доверие (недоверенный ввод, инъекция, авторизация чужого ресурса). Затем применить эквивалентное разбиение и анализ граничных значений, а инварианты оформить как property-based тесты. Это договорённость. Полноту можно проверить только косвенно, мутационным счётом на изменённых файлах.

**Против избыточных тестов** [И]. Мутационное тестирование показывает тесты, которые не убивают ни одного уникального мутанта, — это объективный критерий «тест лишний». Ревьюер-агент, наоборот, склонен требовать «tests for cases that can't happen» (Anthropic, см. выше), поэтому его находки по тестам надо фильтровать.

**Состязательные тесты** [И]. Отдельный субагент с ролью «сломай реализацию» без доступа к рассуждениям автора. Он пишет падающие тесты, а человек решает, баг это или неверная спецификация.

**Строгие настройки раннера как гарантия** [И]: линтер запрещает `skip`/`only`/`xfail` без причины; прогон падает, если тесты не собраны; `--strict-markers` в pytest; порог покрытия или мутаций на новом коде в CI.

### Механизмы

| Механизм | Вид | Тип | Данные об эффективности |
|---|---|---|---|
| Скилл «проход по измерениям входа» + эквивалентные классы и границы | скилл / инструкция | договорённость | данных нет |
| Мутационное тестирование с порогом на изменённом коде | CI | гарантия измерения (порог — gate) | Meta ACH: 73% тестов приняты [П] |
| Агентное PBT (Hypothesis и аналоги) | субагент + тест-фреймворк | генерация вероятностная, прогон детерминированный | 56% валидных багрепортов [П] |
| Состязательный субагент «сломай реализацию» | субагент | вероятностная | данных нет |
| Тесты и код в разных сессиях | сессия / субагент | договорённость | данных нет |
| Read-only тесты (deny + sandbox) | permissions | гарантия | ImpossibleBench: снижает читерство [П по аннотации] |
| Строгий раннер: запрет skip/only/xfail, fail on no tests, `--strict-markers` | линтер / CI | гарантия | данных нет |
| Фаззинг | CI | гарантия запуска | данных в агентном контексте нет; Radar [В] |

### Соразмерность для небольшого проекта

Дешёвый и надёжный базовый набор: строгий раннер, read-only тесты на фазе реализации, скилл с методом вывода граничных случаев. Мутационное тестирование полезнее всего на изменённых файлах, а не на всём проекте, но данных о его цене и времени в соло-проекте с агентом нет. Агентное PBT окупается там, где есть ясные инварианты: парсеры, преобразования, сериализация. Combinatorial/pairwise-тестирование (PICT) в агентном контексте источниками не покрыто [И].

### Что уточнить, когда станет известен стек

| Экосистема | Мутационное тестирование | Property-based |
|---|---|---|
| Rust | [cargo-mutants](https://github.com/sourcefrog/cargo-mutants) [П] | — |
| Python | [mutmut](https://github.com/boxed/mutmut) [П] | Hypothesis [П, косвенно] |
| JS/TS, C#, Scala | Stryker [В] | fast-check (JS/TS) [В, не открывал] |
| JVM | PIT [В] | — |

Нужно также уточнить, умеет ли инструмент ограничивать прогон изменёнными файлами (иначе он слишком медленный для частого запуска), и как в выбранном раннере запретить skip/only/xfail и пустые прогоны.

## Качество кода и архитектуры

### Что делают подходы

Самый надёжный слой — детерминированные проверки. Anthropic: «Use hooks for actions that must happen every time with zero exceptions», пример — «a hook that runs eslint after every file edit». Правила, которые Claude и так соблюдает, нужно удалить из CLAUDE.md «or convert it to a hook». PostToolUse не блокирует, потому что инструмент уже отработал, но при exit 2 stderr передаётся Claude ([best practices](https://code.claude.com/docs/en/best-practices), [hooks](https://code.claude.com/docs/en/hooks) [П] (вендор)). Böckeler: типизированный язык «naturally has type-checking as a sensor» ([Harness engineering](https://martinfowler.com/articles/exploring-gen-ai/harness-engineering.html) [П]). Thoughtworks Radar vol. 34, по сниппету: «Feedback sensors for coding agents use deterministic quality gates — compilers, linters, type checkers and test suites — integrated directly into agent workflows so failures trigger auto-correction before human review». Там же упомянут CodeScene ([Radar](https://thoughtworks.com/radar) [В]).

**Sonar AI Code Assurance** (вендор) — quality gate «Sonar way for AI Code» из семи условий. На новом коде: «No new issues are introduced», все новые Security Hotspots проверены, покрытие не ниже 80.0%, дублирование не выше 3.0%. На всём коде: Security rating A, все hotspots проверены, Reliability rating C. Проекты помечаются как содержащие AI-код ([Sonar docs 2026.3](https://docs.sonarsource.com/sonarqube-server/2026.3/quality-standards-administration/ai-code-assurance/quality-gates-for-ai-code.md) [П] (вендор)). Доступность только в коммерческих редакциях подтверждена лишь сниппетом блога [В].

**AI-ревью, эмпирика.** Cihan et al. (ICSE SEIP 2025): Beko, инструмент на Qodo PR-Agent, 10 проектов, 238 практиков, 4 335 PR. «73.8% of automated comments were resolved», но среднее время закрытия PR выросло примерно с 5 ч 52 мин до 8 ч 20 мин. Большинство отметили лишь небольшое улучшение качества, проблемы — «faulty reviews, unnecessary corrections, and irrelevant comments» ([arXiv 2412.18531](https://arxiv.org/abs/2412.18531) [П]). Lin, Liang, Thongtanunam, Tantithamthavorn (07.2026) разобрали 31 073 пары «ревью — реакция» в 10 191 PR из 239 репозиториев с CodeRabbit. Принято 36.4%, обсуждалось 7.3%, **отклонено 56.3%**. Причины: false positives, избыточность, выход за рамки задачи, расхождение с намерением. Функциональные комментарии «more likely to be invalid» ([arXiv 2607.03316](https://arxiv.org/abs/2607.03316) [П]). Anthropic предлагает ревью в свежем контексте субагента и бандл-скилл `/code-review`, но предупреждает, что ревьюер, которому велено искать пробелы, найдёт их и в хорошей работе ([best practices](https://code.claude.com/docs/en/best-practices) [П] (вендор)). Данных об эффективности этого скилла нет.

Если 36–74% AI-комментариев полезны, AI-ревью стоит включать с фильтром «только корректность и требования», иначе оно шумит [И]. «Внимание к деталям» (имена, мёртвый код, дублирование) частично покрывается линтерами и порогами дублирования. Остальное — человеческое ревью диффа, и при соло-разработке это единственный способ, при котором человек действительно понимает весь код [И].

### Механизмы

| Механизм | Вид | Тип | Данные об эффективности |
|---|---|---|---|
| Форматтер в PostToolUse hook и pre-commit | хук | гарантия | данных для агентов нет |
| Линтер, тайпчекер, архитектурные тесты в Stop hook и CI | хук / CI | гарантия (блокирующая) | данных для агентов нет |
| Пороги сложности и дублирования | CI | гарантия измерения | данных для агентов нет |
| Sonar AI Code Assurance или аналог на новом коде | CI gate | гарантия по правилам Sonar | данных нет (вендор) |
| AI-ревью (CodeRabbit, Qodo, `/code-review`) | бот / субагент | вероятностная | 73.8% resolved (Beko) [П]; 36.4% accepted / 56.3% rejected (CodeRabbit) [П] |
| Стилевые правила в CLAUDE.md | инструкция | договорённость | данных нет; вендор советует переводить в hook |
| Ревью диффа человеком | ручная практика | договорённость (зависит от дисциплины) | данных нет |

### Соразмерность для небольшого проекта

Слои по убыванию гарантии [И]: форматтер в PostToolUse hook и pre-commit; линтер, тайпчекер и архитектурные тесты в Stop hook и CI; Sonar или аналог как CI gate на новом коде; AI-ревью как подсказки, где человек решает по каждому пункту. Для одного разработчика первые два слоя почти бесплатны и дают основную гарантию. Коммерческий gate окупается хуже. Его условия (покрытие 80% и дублирование 3% на новом коде) можно воспроизвести бесплатными средствами, если экосистема это позволяет. AI-ревью добавляет время, это видно по данным Beko. Без фильтра «корректность и требования» оно провоцирует переусложнение.

### Что уточнить, когда станет известен стек

Какой форматтер, линтер и тайпчекер в экосистеме достаточно быстры для hook после каждой правки. eslint в примере Anthropic [П] (вендор); остальные в заметках по экосистемам не проверялись. Есть ли в языке статическая типизация: по Böckeler, она даёт сенсор бесплатно. Покрывает ли Sonar или аналог выбранный язык и какая редакция нужна.

## Проверка утверждений агента

### Что делают подходы

Вендоры сходятся в трёх правилах: агенту нужна исполняемая проверка; он должен показывать доказательства, а не заявлять об успехе; проверять должен не тот, кто делал. Anthropic: «Claude stops when the work looks done. Without a check it can run, "looks done" is the only signal available». Лестница жёсткости: требование в промпте → `/goal`, где отдельный evaluator перепроверяет после каждого хода → **Stop hook**, который «blocks the turn from ending until it passes» (с лимитом последовательных блокировок) → verification subagent, где свежая модель пытается опровергнуть результат. И правило: «Have Claude show evidence rather than asserting success: the test output, the command it ran and what it returned». После того как проверка агентом пройдена, человек сам запускает `/verify` против работающего приложения ([best practices](https://code.claude.com/docs/en/best-practices) [П] (вендор)). Stop hook при exit 2 «Prevents Claude from stopping, continues the conversation» ([hooks](https://code.claude.com/docs/en/hooks) [П] (вендор)).

В статье «Effective harnesses for long-running agents» (26.11.2025) описаны сбои: преждевременное объявление готовности и фичи, помеченные готовыми без end-to-end проверки. Механизмы: JSON-список фич с полем `"passes": false`; e2e через Puppeteer MCP, который «dramatically improved performance»; разделение на initializer и coding agent. Количественных метрик нет ([Anthropic](https://www.anthropic.com/engineering/effective-harnesses-for-long-running-agents) [П] (вендор)). Статья «Harness design for long-running application development» (24.03.2026) вводит роли planner, generator и evaluator, evaluator работает через Playwright MCP. До реализации стороны согласуют «sprint contracts» — что считается «done» и какие критерии тестируемы. Ключевое наблюдение о самооценке: «agents tend to respond by confidently praising the work—even when… the quality is obviously mediocre». Ранний evaluator одобрял работу, хотя сам находил проблемы, и его калибровали по логам. Кейс: solo-агент за 20 мин и $9 против harness за 6 ч и $200. В solo-варианте «core gameplay failed», в harness всё работает. DAW-кейс: 3 ч 50 мин, $124.70 ([Anthropic](https://www.anthropic.com/engineering/harness-design-long-running-apps) [П] (вендор)). Это вендорские кейсы без контрольной статистики, и разница в цене больше чем в двадцать раз.

### Три состояния: done / verified / accepted

Модель трёх различимых статусов [И] опирается на вендорские источники:

| Статус | Что означает | Кто ставит | Опора в источниках |
|---|---|---|---|
| **done (agent)** | агент заявил, что закончил | агент | «looks done» как единственный сигнал без проверки ([best practices](https://code.claude.com/docs/en/best-practices) [П] (вендор)) |
| **verified** | детерминированные проверки зелёные + отчёт независимого verifier-субагента | Stop hook / CI + субагент | Stop hook, verification subagent ([best practices](https://code.claude.com/docs/en/best-practices) [П] (вендор)); `"passes": false` в feature list ([Effective harnesses](https://www.anthropic.com/engineering/effective-harnesses-for-long-running-agents) [П] (вендор)) |
| **accepted (human)** | человек прочитал дифф, руками прогнал основной сценарий и понял код | человек | `/verify` человеком против работающего приложения и «If you can't verify it, don't ship it» ([best practices](https://code.claude.com/docs/en/best-practices) [П] (вендор)) |

Модель «feature list с passes» у Anthropic как раз отделяет первое состояние от второго. Третий шаг вендор описывает отдельно. Ни одно из состояний не подменяет следующее: зелёный CI не означает, что человек понимает код, а это требование брифа.

**Definition of Done с доказательствами** [И]: каждый пункт DoD — это команда и ожидаемый результат (тесты, линтер, тайпчек, архитектурные тесты, мутации на изменённом, SAST). Агент прикладывает вывод, Stop hook повторно запускает тот же набор и блокирует, если он красный. Приложенный вывод — договорённость, ведь агент может приложить не тот вывод. Повторный запуск в hook — гарантия.

**Независимость проверки бывает трёх степеней** [И]: свежий контекст того же агента — самая слабая; другой агент с явными критериями из спека или sprint contract; детерминированная проверка — самая сильная. По Anthropic, отдельного критика настроить легче, чем самокритику генератора. Но вердикт критика остаётся вероятностным, и его самого нужно калибровать.

### Механизмы

| Механизм | Вид | Тип | Данные об эффективности |
|---|---|---|---|
| Требование показывать вывод команд вместо «готово» | инструкция | договорённость | данных нет |
| DoD как список «команда → ожидаемый результат» | артефакт | договорённость (до исполнения в hook/CI) | данных нет |
| `/goal` с отдельным evaluator после каждого хода | режим / субагент | вероятностная | данных нет |
| Stop hook, перезапускающий проверки, exit 2 | хук | гарантия | данных нет |
| CI на тот же набор проверок | CI | гарантия | данных нет |
| Feature list с `"passes": false` | артефакт | договорённость (поле меняет агент), сильнее, если `passes` выставляет скрипт [И] | вендор, без метрик [П] |
| Verifier/evaluator-субагент, sprint contract | субагент + артефакт | вероятностная | кейс Anthropic: $9 solo — не работает, $200 harness — работает [П] (вендор) |
| e2e через Playwright/Puppeteer MCP | субагент + инструмент | вероятностный агент, детерминированные действия браузера | «dramatically improved performance» без чисел [П] (вендор) |
| Ручной прогон и ревью диффа человеком (`/verify`) | ручная практика | договорённость с самим собой | данных нет |

### Соразмерность для небольшого проекта

Минимальный вариант с гарантией [И]: DoD из нескольких команд, Stop hook, который их перезапускает, и тот же набор в CI. Плюс отдельный статус «accepted», который ставит только человек после чтения диффа и ручного прогона. Полный planner/generator/evaluator harness в вендорском кейсе стоил в двадцать с лишним раз дороже и рассчитан на многочасовые автономные прогоны. Для работы короткими шагами под надзором человека он окупается хуже. Verifier-субагент с явными критериями — промежуточный вариант для задач, где детерминированная проверка невозможна: UX, соответствие намерению.

### Что уточнить, когда станет известен стек

Какие команды составят DoD (тест-раннер, линтер, тайпчекер, архитектурный линтер, мутационный инструмент на изменённом, SAST) и сколько они идут в сумме. От этого зависит, годится ли полный набор для Stop hook или часть уходит только в CI. Нужен ли браузерный e2e (Playwright/Puppeteer MCP) или для API достаточно контрактных и интеграционных тестов с реальным клиентом.

## Безопасность и надёжность кода

### Что делают подходы

**Масштаб проблемы.** Veracode 2025 GenAI Code Security Report (30.07.2025, вендор): 80 задач, больше 100 LLM, **45% случаев с уязвимостью класса OWASP Top 10**. Java — больше 70%; Python, C# и JS — 38–45%. XSS (CWE-80) не защищён в 86% случаев, log injection (CWE-117) — в 88%. «security performance has not kept up, remaining unchanged over time», крупные модели не лучше мелких ([Veracode](https://www.veracode.com/press-release/ai-generated-code-poses-major-security-risks-in-nearly-half-of-all-development-tasks-veracode-research-reveals/) [П] (вендор, пресс-релиз)). Если данные верны, это аргумент против довода «модель стала лучше, SAST не нужен» [И]. Методика Veracode не проверена.

**SAST против LLM.** Gnieciak & Szandała (08.2025): 63 уязвимости в 10 C#-проектах. F1 у LLM — 0.797, 0.753 и 0.750, у SonarQube, CodeQL и Snyk Code — 0.260, 0.386 и 0.546. У LLM выше recall, но «substantially higher false-positive rates» и неточная локализация. Рекомендация авторов: «employ language models early in development for broad, context-aware triage, while reserving deterministic rule-based scanners for high-assurance verification» ([arXiv 2508.04448](https://arxiv.org/abs/2508.04448) [П]). Выборка мала, язык один. По сниппету, на 1 080 LLM-сгенерированных образцах только 65% отчётов Semgrep и 61% отчётов CodeQL совпали с ground truth ([arXiv 2602.05868](https://arxiv.org/abs/2602.05868v1) [В]).

**LLM-ревью безопасности.** Claude Code Security Review (GitHub Action) работает по диффу. Категории: инъекции (SQL, command, LDAP, XPath, NoSQL, XXE), auth/IDOR, секреты, криптография, race/TOCTOU, конфигурация, supply chain, десериализация, XSS. По умолчанию **исключены** DoS, rate limiting, исчерпание ресурсов и open redirect. Предупреждение в README: «not hardened against prompt injection attacks and should only be used to review trusted PRs». `/security-review` в Claude Code — «equivalent analysis», кастомизируется копированием `security-review.md`. Количественных метрик нет ([GitHub anthropics/claude-code-security-review](https://github.com/anthropics/claude-code-security-review) [П] (вендор)). Anthropic также предлагает пример субагента «security-reviewer» с фокусом на injection, authn/authz, секреты и небезопасную обработку данных ([best practices](https://code.claude.com/docs/en/best-practices) [П] (вендор)).

**N+1 ловится тестами.** У Bullet (Ruby) есть `Bullet.raise = true # raise an error if n+1 query occurs`, «useful for making your specs fail unless they have optimized queries» ([Bullet](https://github.com/flyerhzm/bullet) [П]). nplusone (Python: SQLAlchemy, Peewee, Django ORM) через `NPLUSONE_RAISE` позволяет «force all automated tests involving unnecessary queries to fail» ([nplusone](https://github.com/jmcarp/nplusone) [П]). Для Django есть django-zen-queries и assertion на число запросов (`assertNumQueries` и аналоги) [В, не открывал]. Это гарантия, но только в пределах кода, покрытого тестами.

**Требования к безопасности.** OWASP ASVS 5.0.0 (30.05.2025): около 350 требований в 17 главах, идентификаторы вида v5.0.0-x.y.z, «Documented Security Decisions» ([OWASP](https://owasp.org/www-project-application-security-verification-standard/) [В]). Фаззинг Radar vol. 34 упоминает как сенсор для агентов [В]. Смежный механизм с данными — агентное PBT из раздела о качестве тестов [П].

### Механизмы

| Механизм | Вид | Тип | Данные об эффективности |
|---|---|---|---|
| SAST (Semgrep / CodeQL / Sonar) на каждом PR | CI | гарантия запуска, полнота ограничена правилами | F1 0.26–0.55 на C#-бенчмарке [П]; 61–65% совпадений с ground truth [В] |
| Детектор N+1 в режиме raise + query-count assertions | тест | гарантия в пределах покрытия | данных нет |
| Линт обработки ошибок (голые `except`, проглатывание, неиспользованные error-значения) | линтер / CI | гарантия | данных нет |
| Секрет-сканер в pre-commit | хук | гарантия | данных нет |
| Скан зависимостей | CI | гарантия запуска | данных нет |
| LLM security review (`/security-review`, Action, субагент security-reviewer) | команда / Action / субагент | вероятностная, Action уязвим к prompt injection | F1 LLM 0.75–0.80, но много FP (другие LLM) [П]; для самого Action данных нет |
| ASVS уровень 1 как чек-лист требований | артефакт | договорённость, частично переводимая в тесты | данных нет |
| DAST (OWASP ZAP и т.п.) | CI / ручной | гарантия запуска | источников 2025–2026 в агентном контексте нет |
| Фаззинг / PBT | CI | гарантия запуска | для фаззинга данных нет [В]; PBT — см. качество тестов [П] |

### Соразмерность для небольшого проекта

Набор для небольшого бэкенда [И], по возрастанию цены. Дешёвые гарантии: секрет-сканер в pre-commit, линт обработки ошибок, детектор N+1 в режиме raise во всех интеграционных тестах плюс query-count assertions на ключевых эндпоинтах, скан зависимостей, SAST (Semgrep или CodeQL) в CI. Вероятностный слой: LLM security review на диффе. Он полезен для логических уязвимостей вроде IDOR, которые SAST пропускает, но DoS и rate limiting нужно добавить через настройку, если они важны. Использовать его стоит только на доверенных PR. На этапе спецификации — ASVS уровня 1 как чек-лист. DAST — по необходимости. Это сочетается с выводом Gnieciak & Szandała: LLM для широкой сортировки, детерминированные сканеры для высокой уверенности.

### Что уточнить, когда станет известен стек

| Вопрос | Примеры из заметок | Статус |
|---|---|---|
| ORM и детектор N+1 | Bullet для ActiveRecord; nplusone или django-zen-queries для Python; для Hibernate, EF Core, Prisma и Go — не проверялось | [П] для Bullet и nplusone; [В] для django-zen-queries |
| Покрытие языка в SAST | CodeQL, Semgrep | не проверялось по языкам |
| Линтеры обработки ошибок | errcheck/golangci-lint, ruff BLE/TRY, eslint no-floating-promises | не проверялось |
| Правило по умолчанию для Veracode-рисков | для Java доля уязвимых задач выше 70% [П] (вендор) — ставка на SAST там важнее | [И] |

## Сводная таблица механизмов

| Задача | Механизм | Вид | Гарантия / договорённость / вероятностный | Данные об эффективности |
|---|---|---|---|---|
| Архитектура | Plan mode, интервью, SPEC | инструкция / режим | договорённость (plan mode блокирует правки — режим, а не правило качества) | данных нет |
| Архитектура | Constitution, simplicity gates (spec-kit) | артефакт | договорённость | данных нет; Böckeler: агент инструкции не всегда выполняет [П] |
| Архитектура | ArchUnit, dependency-cruiser, import-linter, Deptrac, Packwerk | тест / CI / хук | гарантия (в объёме правил) | данных для агентов нет |
| Архитектура | Agentic fitness function | субагент | вероятностный | данных нет (InfoQ 2026) |
| Решения | ADR (MADR/Y), Confirmation → тест | артефакт + CI | ADR — договорённость; Confirmation-тест — гарантия | данных нет |
| Полномочия | deny/ask-правила, sandbox | permissions | гарантия (Edit-deny без sandbox обходится подпроцессом [П]) | данных нет |
| Полномочия | Auto mode | классификатор | вероятностный | данных нет |
| TDD | Промпт Red→Green (Beck) | инструкция | договорённость | тесты в промпте повышают решаемость (2024) [П] |
| TDD | TDD-Guard | хук + LLM | блокировка детерминированная, вердикт вероятностный | данных нет |
| TDD | Red/green-коммиты + CI-проверка | CI | гарантия факта red→green | данных нет |
| Подгонка тестов | Read-only тесты (deny + sandbox) | permissions | гарантия | ImpossibleBench: снижает читерство [П] |
| Качество тестов | Мутационное тестирование | CI | гарантия измерения (порог — gate) | Meta ACH 73% принятых тестов [П] |
| Качество тестов | Agentic PBT | субагент + Hypothesis | генерация вероятностная, прогон детерминированный | 56% валидных багрепортов [П] |
| Стиль / качество | Форматтер / линтер в хуке + CI | хук / CI | гарантия | данных для агентов нет |
| Качество | Sonar AI Code Assurance | CI gate | гарантия по правилам Sonar | данных нет (вендор) |
| Качество | AI-ревью (CodeRabbit, Qodo, /code-review) | бот / субагент | вероятностный | 73.8% resolved (Beko) [П]; 36.4% accepted / 56.3% rejected (CodeRabbit) [П] |
| Верификация | Evidence в ответе | инструкция | договорённость | данных нет |
| Верификация | Stop hook с тестами | хук | гарантия | данных нет |
| Верификация | Evaluator/verifier-агент | субагент | вероятностный | кейс Anthropic: $9 solo — не работает, $200 harness — работает [П] (вендор) |
| Безопасность | SAST (CodeQL/Semgrep/Sonar) | CI | гарантия запуска, неполнота | F1 0.26–0.55 на C#-бенчмарке [П] |
| Безопасность | LLM security review | Action / команда | вероятностный, уязвим к prompt injection | F1 LLM 0.75–0.80, но много FP (другие LLM) [П]; для самого Action данных нет |
| Надёжность | N+1 raise-режим / query count | тест | гарантия в пределах покрытия | данных нет |

Строка про ImpossibleBench в сводной таблице заметок помечена [П], но в разделе о TDD та же находка помечена «[П по аннотации]». Здесь принята более осторожная трактовка: эффект подтверждён аннотацией, чисел из полного текста нет.

## Варианты и критерии выбора

Готового процесса в этой части нет. Вместо него — оси, по которым выбирает человек [И].

**Цена ошибки в данной области.** Где ошибка дорогая или незаметная (безопасность, необратимые миграции, публичный контракт, тесты как оракул), стоит платить за гарантию: deny, sandbox, CI gate. Где ошибка дешёвая и видна в диффе (стиль, имена), хватит форматтера и ревью.

**Можно ли выразить правило как вычисление.** Если правило записывается проверкой (направление зависимостей, «тесты не меняются», «N+1 запрещён», «тесты зелёные»), его стоит выносить из инструкций, как советуют Anthropic и InfoQ. Если правило — суждение (простота, верность границе, соответствие намерению), остаются вероятностные проверки и человек. InfoQ предлагает траекторию: начинать с советующей LLM-проверки и переводить повторяющиеся находки в детерминированные правила.

**Стоимость ревью для человека.** Каждый markdown-артефакт (спек, ADR, decision log, отчёт ревьюера) человек должен прочитать. Böckeler предпочитает читать код. Данные Beko показывают, что AI-ревью удлиняет закрытие PR. Критерий: артефакт оправдан, если человек его действительно читает и он уменьшает объём кода, который надо проверять вручную.

**Независимость проверяющего.** Чем ближе проверяющий к автору (тот же контекст → свежий контекст → другой агент с критериями → детерминированная проверка), тем слабее проверка. Вендор признаёт, что агенты «confidently praising the work».

**Скорость проверки.** От этого зависит место механизма: hook после каждой правки (форматтер), Stop hook (линтер, тайпчек, быстрые тесты, архитектурные правила) или только CI (мутации, SAST, e2e). Это выясняется только после выбора стека.

## Пробелы

Нет количественных данных о том, снижают ли архитектурные тесты дрейф архитектуры у агентов. Нет первоисточников 2025–2026 о C4, Structurizr, Mermaid или D2 как контексте для агента. Нет исследований о vertical slices или размере файлов в агентной разработке, найден лишь косвенный сниппет о токенах ([dev.to](https://dev.to/bwca/the-hidden-ai-tax-on-tech-debt-4k10) [В]). Нет данных о том, улучшают ли ADR в контексте согласованность решений агента между сессиями. RFC и decision log в контексте агентов по первоисточникам не проверены. Нет эмпирических данных об оптимальном уровне автономии для соло-разработки бэкенда. Документ Anthropic о «framework for safe and trustworthy agents» не открывался.

По тестам. Нет исследования 2025–2026 о том, что строгий TDD-цикл с агентом даёт меньше дефектов, чем test-after с мутационным тестированием. Нет готового инструмента CI-проверки «red-before-green по истории git». Нет данных о стоимости и времени мутационного тестирования в соло-проекте с агентом. Combinatorial testing (pairwise, PICT) в агентном контексте источниками не покрыт. Числа ImpossibleBench об эффекте read-only из полного текста не извлечены.

По качеству и проверке. Нет независимых данных о Copilot code review и Claude Code review (GitHub app) в 2025–2026: отдельно не искали. Нет сравнений Sonar AI Code Assurance с обычным gate. Нет независимых (не вендорских) измерений того, насколько verifier-субагент снижает число ложных «done». Утверждения о Codex task logs и citations первоисточником не проверены (403).

По безопасности. Нет независимых данных о точности `/security-review` и Action. Нет данных о DAST и фаззинге в агентном цикле для небольшого бэкенда. По экосистемам не проверены: детекторы N+1 для Hibernate, EF Core, Prisma и Go; покрытие языков в CodeQL и Semgrep; линтеры обработки ошибок (errcheck/golangci-lint, ruff BLE/TRY, eslint no-floating-promises); инструменты архитектурных правил для Go и .NET.

## Сомнительное / не проверено

- **ImpossibleBench: «GPT-5 cheats in 76% of the tasks in Oneoff-SWEbench and 2.9% on Oneoff-LiveCodeBench».** Есть только в сниппете поиска, в открытой аннотации нет ([arXiv 2510.20270](https://arxiv.org/abs/2510.20270)) [В]. Эффект read-only доступа в числах не извлечён.
- **Anthropic «write tests, commit, then code».** В текущей редакции best practices (2026) такой формулировки нет. Возможно, она была в старой статье Anthropic «Claude Code: Best practices for agentic coding» (2025), которая не открывалась. В текущей редакции есть «write a failing test that reproduces the issue, then fix it» и разделение writer/tester по сессиям.
- **OpenAI Codex «citations of terminal logs and test outputs».** Страница анонса вернула 403, утверждение не проверено.
- **Böckeler, цитата про computational sensors** («catch the structural stuff reliably: duplicate code, cyclomatic complexity, missing test coverage, architectural drift»). Фетчер пометил её как «approximate paraphrase», дословно не цитировать.
- **TDD-Guard: упоминание «newer Probity project».** Из пересказа README, не проверено.
- **adr.github.io: дата «2026-11-10».** Дата в будущем относительно 2026-10-03, вероятно, это дата события на странице. Не использовать.
- **Цифры о LLM-оракулах из сниппета** («43% vs 45% mutation score», «93% vs 74% fault detection», «assertion errors >85% of failures»). Первоисточники не открыты, источники смешаны.
- **«LLMs can detect up to 90-100% of vulnerabilities but suffer from high false-positive rates»** (сравнение 15 SAST и 12 LLM). Только сниппет ([arXiv 2503.01449](https://arxiv.org/abs/2503.01449)) [В].
- **Sonar AI Code Assurance «только в коммерческих редакциях».** Только сниппет блога, в открытой документации редакция не указана.
- **Thoughtworks Radar vol. 34.** Все утверждения по сниппету поиска, страницы блипов не открыты [В].
- **Veracode** — пресс-релиз вендора. Методика (как выбирались задачи и что значит «choose insecure option») не проверена.
- **Метка Meta ACH расходится между заметками.** В заметках этой части цифры ACH помечены [П] (arXiv и блог Meta открыты). В заметках о слабостях агента (часть 4б) те же цифры 73% / 36% / 9 095 / 571 помечены [В] — «из поисковой выдачи с текстом абстракта». Сами цифры совпадают. При сборке общего отчёта стоит указать обе метки или перепроверить по полному тексту.
- **Модульность «is not a core factor»** ([arXiv 2407.11406](https://arxiv.org/abs/2407.11406)) — только сниппет [В], старше 2025, генерация функций, а не агенты. Переносить на агентную разработку нельзя.
- **Количественные кейсы Anthropic о harness** ($9 / 20 мин против $200 / 6 ч; DAW $124.70). Это вендорские единичные кейсы без контрольной группы и статистики. Их можно использовать как иллюстрацию механизма, а не как меру эффекта.
