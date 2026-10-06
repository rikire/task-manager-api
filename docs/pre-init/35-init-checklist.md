# 35. Чек-лист инициализации

**Статус:** принято 2026-10-06, человек (итог финальной сверки). **Область:** все.

Порядок шагов инициализации репозитория по решениям 01–34 и список проверок, которые решения оставили
«на настройку». Уровни — по [34](34-core-vs-roadmap.md): здесь только ядро; «если успеем» и роадмап — в
34 §3–§4. После выполнения чек-листа папка `docs/pre-init/` замораживается ([README](README.md)).

## Шаги (ядро)

1. **Репозиторий.** `git init`, `.gitignore` (в т.ч. `.agent-state/`, `.claude/settings.local.json`,
   `.env.local`); публичный репозиторий на GitHub, защита `main`, слияние через rebase, автослияние
   ([28](28-hosting-ci-git.md)).
2. **Инструменты.** Установить mise (с одобрения человека); `mise.toml` / `mise.lock`: Node, OpenSpec,
   markdownlint-cli2, lychee, commitlint, gitleaks, jscpd; `OPENSPEC_TELEMETRY=0`
   ([29](29-environment.md), [32](32-stack-tools.md)).
3. **OpenSpec.** `openspec init --tools claude,agents`, профиль `custom` (+ continue, verify);
   `openspec/config.yaml`: язык английский, все правила артефактов из [34](34-core-vs-roadmap.md) §2
   ([02](02-sdd-framework.md), [07](07-requirements-intent.md)).
4. **Инструкции.** AGENTS.md (состав — 34 §2), CLAUDE.md (`@AGENTS.md`), `.claude/rules/` (код, тесты,
   `writing.md`) ([03](03-agent-instructions.md), [19](19-agent-writing.md)).
5. **Скиллы.** Интервью, работа над изменением, написание тестов, `architecture`, `writing`; установить
   `doc-coauthoring` ([20](20-skills.md)).
6. **Настройки Claude Code.** `.claude/settings.json`: `ask` / `deny` по [13](13-authority.md) §4,
   `disableBypassPermissionsMode`, `autoMemoryEnabled: false`, песочница и сетевой allowlist
   ([15](15-agent-security.md)), `allow` WebSearch, `attribution` ([21](21-attribution.md)).
7. **Хуки Claude Code + их тесты.** Stop (с учётом фазы), защита тестов, `scripts/phase`, форматтер и
   линтер после правки; записать фикстуры событий из реальной сессии, unit-тесты, список обходов
   ([16](16-guardrails-harness.md)).
8. **Скелет приложения и PHP-инструменты.** Docker Compose, Symfony, PostgreSQL; PHPUnit (строгий),
   DAMA, Foundry, PHPStan max, PHP-CS-Fixer, Deptrac; миграции при старте
   ([32](32-stack-tools.md), [33](33-readme.md)).
9. **Makefile и git-хуки.** Цели `setup`, `up`, `test`, `check` и др.; `.githooks/` — pre-commit и
   commit-msg по [34](34-core-vs-roadmap.md) §2 ([29](29-environment.md)).
10. **CI.** Джобы ядра из 34 §2, включая «чистый клон» по README ([28](28-hosting-ci-git.md)).
11. **Документы.** Скелет `docs/README.md` (реестр, префиксы ID, алиасы), `docs/architecture/README.md`,
    `docs/roadmap.md`, `docs/api/curl-examples.md`, скелет README ([23](23-documentation.md),
    [24](24-planning-tracking.md), [33](33-readme.md)).
12. **Старт архитектуры** — первое изменение OpenSpec: драйверы, выборка из ASVS уровня 1, ADR на стек
    (версии Symfony и PHP, FPM + nginx или FrankenPHP, подход к контракту API), правила Deptrac
    ([11](11-architecture-design.md), [12](12-adr.md), [14](14-code-security.md)).
13. **Стиль и скиллы под стек** — поиск Symfony Coding Standards, Best Practices, скиллов; карточки
    кандидатов ([26](26-code-quality.md) §3).
14. **Заморозка `docs/pre-init/`** — отметка коммита в README папки.

## Проверки при настройке

- [ ] OpenSpec: поведение `continue` и `verify`; если `continue` не подходит — ручная приёмка
  ([02](02-sdd-framework.md), [07](07-requirements-intent.md) §6).
- [ ] Приоритет `autoMemoryEnabled: false` над пользовательскими настройками
  ([03](03-agent-instructions.md)).
- [ ] Подгружаются ли правила по путям при правке через Bash ([03](03-agent-instructions.md)).
- [ ] Время набора pre-commit в Stop-хуке (порог ~1 мин) ([04](04-definition-of-done.md)).
- [ ] Скорость форматтера через `docker compose exec` после каждой правки; если медленно — в Stop
  ([32](32-stack-tools.md)).
- [ ] Защита Stop-хука от зацикливания по полям входа хука ([16](16-guardrails-harness.md)).
- [ ] Песочница: строгий режим bubblewrap; что происходит при обращении к домену вне allowlist;
  доступ к сокету Docker из песочницы ([15](15-agent-security.md)).
- [ ] `scripts/phase` пишет файл фазы, а прямая запись агентом блокируется (встроенные инструменты и
  известные формы Bash) ([16](16-guardrails-harness.md) §2).
- [ ] `attribution` принимает свой текст `Assisted-by: Claude Code`; можно ли отличить коммит агента
  ([21](21-attribution.md)).
- [ ] Команда установки `doc-coauthoring` ([19](19-agent-writing.md)).
- [ ] Хуки во frontmatter субагентов работают при принятом доверии к папке
  ([27](27-subagents.md)) — когда субагенты будут внедряться.
- [ ] Совместимость версий PHP-инструментов между собой ([32](32-stack-tools.md)).
- [ ] Задержка свежих релизов в Composer — есть ли настройка ([32](32-stack-tools.md)).
- [ ] Защита веток на тарифе GitHub — если репозиторий будет приватным ([28](28-hosting-ci-git.md)).
- [ ] Тестовые пути, манифесты, миграции, конфиги — внести в правила `ask` / хуки
  ([05](05-tdd.md), [13](13-authority.md)).
- [ ] После первых замеров — пороги: размер диффа (~400 строк), сложность, дублирование, мутации
  ([18](18-human-comprehension.md), [26](26-code-quality.md), [06](06-test-quality.md)).

## Гарантия

**Д**: чек-лист — договорённость; результат проверяется джобом «чистый клон» и проверкой здоровья
(когда внедрена).
