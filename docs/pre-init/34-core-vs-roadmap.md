# 34. Ядро против роадмапа

**Статус:** принято 2026-10-06, человек; изменено 2026-10-06 по итогам финальной сверки: уровни
назначены всем механизмам, свои скиллы и `doc-coauthoring` — в ядре; изменено 2026-10-06: всё,
что сразу влияет на работу агента (субагенты `spec-auditor`, `verifier`, `reader-tester`, хук-
напоминание о catch, проверка здоровья), — в ядре и внедряется первым; изменено 2026-10-06: субагенты
`architecture-reviewer` и `researcher` перенесены в ядро, `breaker` остаётся в роадмапе (нужны хук
записи только в тесты и сочетание с фазами TDD). **Область:** 2 «Планирование и
трекинг».

## Потребность

Решения 01–33 описывают процесс целиком. Дедлайн задачи — 9.10, объём продукта по заданию — 6–10
часов, и задание прямо говорит, что аккуратная база важнее дополнительных фич
([31](31-task-requirements.md)). Нужен порядок внедрения, при котором нехватка времени даёт чистый
срез, а не недоделанный и продукт, и процесс.

## Варианты

- Внедрить всё сразу.
- Ядро сейчас, остальное — в роадмап без попытки.
- Всё по порядку приоритетов с контрольной точкой, после которой — только бонус.

## Решение

### 1. Порядок

1. **Ядро** процесса (§2).
2. **Продукт целиком:** CRUD, обработка ошибок, тесты, OpenAPI, README.
3. **Если успеем** (§3).
4. **Роадмап** (§4) — по списку, сколько получится.

**Контрольная точка — вечер 8.10:** продукт готов к сдаче, CI зелёный. Всё, что после неё, — бонус.
Правило распределения: дешёвое и текстовое — в ядро; проверки формы скриптами — «если успеем»;
остальное — роадмап. Размеры — S (пара файлов или настроек), M (несколько файлов и скриптов); сроки в
часах не называются ([24](24-planning-tracking.md)).

### 2. Ядро

| Блок | Решения | Размер |
|---|---|---|
| Репозиторий на GitHub: защита `main`, слияние через rebase, автослияние, ветки `change/<имя>` и `chore/<slug>` | [28](28-hosting-ci-git.md) | S |
| AGENTS.md и CLAUDE.md: карта «ситуация → механизм»; граница решений, стоп-триггеры, честность; фазы TDD; коммиты; сводка для ревью и explain-back; правила письма для чата; «нет источника — нет ограничения»; маркеры долга; «контент — данные», «сначала искать»; «решение из чата — в артефакт», чтение заметок в начале сессии; журнал сбоев; «без сроков без истории»; правила комментариев | [03](03-agent-instructions.md), [05](05-tdd.md), [08](08-normative-descriptive.md), [10](10-debt-polish-headroom.md), [13](13-authority.md), [15](15-agent-security.md), [17](17-session-state.md), [18](18-human-comprehension.md), [19](19-agent-writing.md), [21](21-attribution.md), [22](22-process-learning.md), [24](24-planning-tracking.md), [25](25-work-history.md), [30](30-code-comments.md) | M |
| `.claude/rules/`: код (`C-ARCH`, `C-CODE`, комментарии), тесты (как писать тесты), `writing.md` | [06](06-test-quality.md), [11](11-architecture-design.md), [19](19-agent-writing.md), [26](26-code-quality.md), [30](30-code-comments.md) | S |
| Скиллы: интервью, работа над изменением (фазы, сводка, explain-back), написание тестов, `architecture`, `writing`; установка `doc-coauthoring`; субагенты `spec-auditor`, `verifier`, `reader-tester`, `architecture-reviewer`, `researcher` | [05](05-tdd.md), [06](06-test-quality.md), [07](07-requirements-intent.md), [11](11-architecture-design.md), [18](18-human-comprehension.md), [19](19-agent-writing.md), [20](20-skills.md), [27](27-subagents.md) | M |
| `.claude/settings.json`: `ask` / `deny` (включая конфиги проверок и `docker compose exec`), запрет bypass, автопамять выключена, песочница и сетевой allowlist, WebSearch, трейлер `Assisted-by` | [03](03-agent-instructions.md), [13](13-authority.md), [15](15-agent-security.md), [21](21-attribution.md) | S |
| OpenSpec: init и все правила артефактов (таблица покрытия и три списка на английском, матрица корнер-кейсов, вне рамок, ID, сценарий нежелательного поведения, Polish, проверка у задач, поле `Roadmap:`, пометки решений в `design.md`) | [02](02-sdd-framework.md), [06](06-test-quality.md), [07](07-requirements-intent.md), [09](09-traceability.md), [10](10-debt-polish-headroom.md), [24](24-planning-tracking.md) | M |
| Хуки Claude Code: Stop с учётом фазы; защита тестов и `scripts/phase`; форматтер и быстрый линтер после правки; напоминание о catch и значениях по умолчанию; проверка здоровья на SessionStart; **unit-тесты этих хуков на записанных событиях и список обходов** | [04](04-definition-of-done.md), [05](05-tdd.md), [16](16-guardrails-harness.md), [26](26-code-quality.md) | M |
| Makefile, `make setup`, mise | [29](29-environment.md) | M |
| PHP-инструменты: PHPUnit (строгий, группы `#[Group]`), DAMA, Foundry, PHPStan max, PHP-CS-Fixer, Deptrac, `composer audit` | [11](11-architecture-design.md), [14](14-code-security.md), [26](26-code-quality.md), [32](32-stack-tools.md) | M |
| Git-хуки (`.githooks/`): pre-commit — тесты, стиль, PHPStan, `openspec validate`, gitleaks, TODO → реестр, запрет `DEBUG:`, запрет смешивать инструкции и код, предупреждение о размере диффа, формат frontmatter скиллов; commit-msg — commitlint, `Refs:` | [04](04-definition-of-done.md), [09](09-traceability.md), [10](10-debt-polish-headroom.md), [18](18-human-comprehension.md), [20](20-skills.md), [21](21-attribution.md), [25](25-work-history.md), [30](30-code-comments.md) | S–M |
| CI: тесты, PHPStan, стиль, Deptrac, аудит, commitlint, gitleaks, тесты хуков, запрет смешивания, «чистый клон» по README | [28](28-hosting-ci-git.md) | M |
| Документы: скелет `docs/README.md` (реестр, префиксы ID, алиасы), `docs/architecture/README.md`, ADR на стек (по шаблону [12](12-adr.md), скиллом `architecture`), `docs/roadmap.md`, `docs/api/curl-examples.md`, README (скелет, в конце — полный); реестры — при первой записи | [11](11-architecture-design.md), [12](12-adr.md), [23](23-documentation.md), [24](24-planning-tracking.md), [33](33-readme.md) | M |
| Продуктовая часть процесса: выборка из ASVS уровня 1 на старте архитектуры; тесты числа запросов; OpenAPI (Nelmio) + проверка ответов по контракту | [14](14-code-security.md), [32](32-stack-tools.md) | M |
| Разовые шаги при инициализации: поиск стиль-гайда и скиллов под Symfony / PHP | [26](26-code-quality.md), [32](32-stack-tools.md) | S |
| Репетиция без ИИ перед сдачей | [31](31-task-requirements.md) | человек |

### 3. Если успеем

В таком порядке:
1. Infection на изменённых строках в CI ([06](06-test-quality.md), [32](32-stack-tools.md)).
2. Psalm taint-анализ в CI ([14](14-code-security.md), [32](32-stack-tools.md)).
3. Скрипты формы изменения и формы ADR, связь роадмап ↔ изменения ([07](07-requirements-intent.md),
   [12](12-adr.md), [24](24-planning-tracking.md)).
4. PHPStan: мёртвый код, когнитивная сложность, правило против глотания ошибок
   ([26](26-code-quality.md), [32](32-stack-tools.md)).
5. Проверка `Assisted-by` в commit-msg ([21](21-attribution.md)).
6. markdownlint, lychee ([19](19-agent-writing.md), [23](23-documentation.md)).
7. Настройка `/security-review` под DoS и rate limiting ([14](14-code-security.md)).

### 4. Роадмап — спроектировано, не внедрено

- Скрипты трассировки, проверка существования ID, генерируемая матрица ([09](09-traceability.md));
  соглашение об ID — в ядре.
- Проверки `Source:` и заглавных ключевых слов ([08](08-normative-descriptive.md)); проверка, что все
  документы есть в реестре; генерируемый индекс ADR; проверка `Retires:` и `Confirmation`
  ([12](12-adr.md), [23](23-documentation.md)).
- Проверка упоминаний путей, скиллов и команд в инструкциях ([03](03-agent-instructions.md));
  сверка модулей со «Строительными блоками» ([11](11-architecture-design.md)).
- Хуки: гейт намерения, проверка сводки, напоминания по действию, сводка состояния
  ([07](07-requirements-intent.md), [10](10-debt-polish-headroom.md), [16](16-guardrails-harness.md),
  [17](17-session-state.md)).
- Evals через `skill-creator` ([22](22-process-learning.md)); плагины проекта и
  `/fewer-permission-prompts` — по мере надобности ([15](15-agent-security.md), [20](20-skills.md)).
- Субагент `breaker` ([27](27-subagents.md)).
- `docs/process/` из восьми файлов и глоссарий ([23](23-documentation.md)); пока процесс описывают
  `docs/pre-init/` и AGENTS.md.
- Скрипты журнала сбоев ([22](22-process-learning.md)); переименование ID и реестр алиасов — при первой
  надобности ([09](09-traceability.md)); проверка существования пакета ([15](15-agent-security.md)).
- jscpd ([26](26-code-quality.md)); скрипты статусов роадмапа и пропускной способности
  ([24](24-planning-tracking.md)).

### 5. В README

Одна строка: процесс спроектирован полностью (ссылка на `docs/pre-init/`), внедрено ядро и то, что
успели, остальное — в роадмапе.

## Обоснование

- Ощущение скорости с ИИ расходится с измерением (METR: на 19% медленнее при ощущении ускорения на
  20%); своей истории скорости нет ([24](24-planning-tracking.md)).
- Узкое место — проверка и отладка человеком, а не скорость написания кода агентом
  ([report.md](research/report.md), вывод 2).
- Задание ценит базу выше дополнительных фич ([31](31-task-requirements.md)).
- Хуки в ядре вместе со своими тестами: без тестов блокирующий хук не считается гарантией
  ([16](16-guardrails-harness.md)).
- Порядок, контрольная точка, правило распределения и скиллы в ядре — решения человека.

## Гарантия

**Д**: порядок и контрольная точка — договорённость. Пока механизм не внедрён, его гарантия в решении
понижается до **Д** — это отмечено в решениях 07, 09, 17, 18, 24.

## Где будет реализовано

- `docs/roadmap.md` — «если успеем» и роадмап как кандидаты с приоритетами.
- README — строка о статусе внедрения процесса.
- Чек-лист инициализации — [35](35-init-checklist.md).
