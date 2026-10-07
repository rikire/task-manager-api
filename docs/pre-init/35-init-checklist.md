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
    В роадмап перенести из таблицы покрытия задания (`docs/task/README.md`, [36](36-constraints-and-ids.md)):
    приоритет «база важнее фич; если не успеваем — CRUD и чистые миграции» (строка 13 задания); «Вне
    рамок»: пагинация, сложная авторизация, админка, деплой (строка 141); чек-лист вехи «Сдача»:
    заполнить «Ваше решение» и «Ваши контакты», открыть доступ, прислать ссылку (строки 8–9, 144–150).
    В реестр префиксов — алиас `C-<AREA>-<slug>` → `RUL-<AREA>-<slug>` и области `CON` / `RUL` по 36.
12. **Старт архитектуры** — первое изменение OpenSpec: драйверы, выборка из ASVS уровня 1, ADR на стек
    (версии Symfony и PHP, FPM + nginx или FrankenPHP, подход к контракту API), правила Deptrac
    ([11](11-architecture-design.md), [12](12-adr.md), [14](14-code-security.md)).
    **Порядок изменён 2026-10-06 (решение человека):** первая группа задач изменения
    `architecture-kickoff` — ADR на стек (версии, запуск PHP, контракт API, Doctrine ORM, Symfony
    Validator) с драйверами из `docs/constraints.md` — идёт **до шагов 8–11**; остальное — после скелета.
    Сценарии качества в этом изменении: `QAS-DEPLOY-clean-clone-start` (строки 108, 135 задания),
    `QAS-MAINT-layering`, `QAS-MAINT-typing`, `QAS-MAINT-readability` (строка 137),
    `QAS-MAINT-readme-matches-code` (строка 138); после этого `Source:` правила
    `RUL-ARCH-thin-controllers` перевести на `QAS-MAINT-layering`.
13. **Стиль и скиллы под стек** — поиск Symfony Coding Standards, Best Practices, скиллов; карточки
    кандидатов ([26](26-code-quality.md) §3).
14. **Заморозка `docs/pre-init/`** — отметка коммита в README папки.

## Проверки при настройке

- [x] OpenSpec: поведение `continue` и `verify`; если `continue` не подходит — ручная приёмка
  ([02](02-sdd-framework.md), [07](07-requirements-intent.md) §6). Проверено на изменении `finish-init`
  (2026-10-07): артефакты создаются через CLI (`openspec new change`, `status`, `instructions`,
  `validate`), proposal принимается человеком вручную; `/opsx:verify` сверяет задачи
  (`openspec instructions apply`: 25 из 27 на момент проверки), пропущенные спеки (`skip_specs`) отмечает
  как неприменимые, а решения `design.md` — с кодом.
- [x] Приоритет `autoMemoryEnabled: false` над пользовательскими настройками
  ([03](03-agent-instructions.md)). Проверено 2026-10-07: проектные настройки перекрывают
  пользовательские, их перекрывают только локальные, управляемые и переменная
  `CLAUDE_CODE_DISABLE_AUTO_MEMORY` (https://code.claude.com/docs/en/settings.md); в
  `.claude/settings.json` — `false`, в `~/.claude/settings.json` и `.claude/settings.local.json` ключа нет,
  управляемых настроек и переменной нет.
- [x] Подгружаются ли правила по путям при правке через Bash ([03](03-agent-instructions.md)).
  Проверено 2026-10-07 (изменение `finish-init`): **нет** — правила из `.claude/rules/` приходили в
  контекст при Read/Edit/Write, при правках через `sed`/`python` в Bash — ни разу. Следствие: файлы,
  к которым привязаны правила, правятся инструментами Edit/Write.
- [x] Время набора pre-commit в Stop-хуке (порог ~1 мин) ([04](04-definition-of-done.md)).
  Замер 2026-10-07 (`/usr/bin/time make stop-check`): фаза `off` — 12,2 с, фаза `tests` — 10,8 с; после
  добавления проверок групп 5–6 `finish-init` (`md`, `forms-check`, правила PHPStan) — 15,5 с.
- [x] Скорость форматтера через `docker compose exec` после каждой правки; если медленно — в Stop
  ([32](32-stack-tools.md)). Замер 2026-10-07: `make fix-file` — 0,5 с, `make lint-file` — 2,9 с на
  файл; остаётся после каждой правки.
- [x] Защита Stop-хука от зацикливания по полям входа хука ([16](16-guardrails-harness.md)).
  Сделано собственным счётчиком, а не полем `stop_hook_active`: `MAX_BLOCKS = 3` подряд на одном
  отпечатке дерева (`.claude/hooks/stop_check.py`), тест `test_releases_turn_after_max_blocks`;
  наблюдалось 2026-10-07 (блоки 1/3–3/3, затем ход отпущен). Шум повторов — `IMP-006`.
- [x] Песочница: строгий режим bubblewrap; что происходит при обращении к домену вне allowlist;
  доступ к сокету Docker из песочницы ([15](15-agent-security.md)). Проверено 2026-10-07: на этой
  машине песочница не запускается ни для одной команды, поэтому песочница **выключена** (решение
  владельца); попытки, ошибка и условие возврата — `DEBT-001-sandbox-disabled`
  (`docs/registers/debt.md`).
- [x] `scripts/phase` пишет файл фазы, а прямая запись агентом блокируется (встроенные инструменты и
  известные формы Bash) ([16](16-guardrails-harness.md) §2). `deny Edit(./.agent-state/phase)`; тесты
  `test_phase_file_cannot_be_written_directly`, `…_after_phase_script`, `test_phase_script_is_allowed`
  (`.claude/hooks/tests/`); непокрытые формы — `.claude/hooks/BYPASSES.md`.
- [x] `attribution` принимает свой текст `Assisted-by: Claude Code`; можно ли отличить коммит агента
  ([21](21-attribution.md)). Принимает (`attribution.commit` и `.pr` в `.claude/settings.json`;
  трейлер в коммитах be164cd, dc68555). Отличить можно: в окружении команд агента `CLAUDECODE=1`
  (проверено 2026-10-07), git-хуки наследуют окружение — основа задачи 5.2 `finish-init`.
- [x] Команда установки `doc-coauthoring` ([19](19-agent-writing.md)). Установлен:
  `.claude/skills/doc-coauthoring/`.
- [x] Хуки во frontmatter субагентов работают при принятом доверии к папке
  ([27](27-subagents.md)) — когда субагенты будут внедряться. Не применимо: субагенты внедрены
  (`.claude/agents/`), но ни один не объявляет хуков во frontmatter (проверено 2026-10-07); проверить,
  когда первый объявит.
- [x] Совместимость версий PHP-инструментов между собой ([32](32-stack-tools.md)). `composer install`
  разрешил зависимости; `make check` зелёный (PHPUnit 13.4.1, PHPStan, PHP-CS-Fixer 3.95.27, Deptrac на
  PHP 8.4.26), локально и в CI `clean-clone` (PR #10); Psalm 6.19 и Infection 0.35.6 добавлены и прошли в
  CI (PR #14), Infection с PHPUnit 13.4 проверен пока только на пустом диффе.
- [x] Задержка свежих релизов в Composer — есть ли настройка ([32](32-stack-tools.md)). Встроенной
  настройки не найдено (поиск 2026-10-07; по исходникам Composer не проверено); есть сторонние плагины
  `zingstudios/composer-delay`, `innobrain/soak-time`, Heimdall. Перенесено в роадмап кандидатом (решение
  владельца, 2026-10-07).
- [x] Защита веток на тарифе GitHub — если репозиторий будет приватным ([28](28-hosting-ci-git.md)).
  Репозиторий публичный; `main` защищён набором правил `protect-main` (active); слияние только rebase,
  автослияние и удаление веток после слияния включены (`gh api`, 2026-10-07).
- [x] Тестовые пути, манифесты, миграции, конфиги — внести в правила `ask` / хуки
  ([05](05-tdd.md), [13](13-authority.md)). Манифесты (`composer.*`, `mise.*`), миграции и конфиги
  инструментов — в `ask` (`.claude/settings.json`); тесты защищает хук фаз: `tests/`, а с задачи 2.3
  `finish-init` (решение владельца, 2026-10-07) и `scripts/tests/`, `.claude/hooks/tests/` — для
  Edit/Write и для перенаправления в Bash; тест `test_script_and_hook_tests_are_locked_like_tests`.
- [x] После первых замеров — пороги: размер диффа (~400 строк), сложность, дублирование, мутации
  ([18](18-human-comprehension.md), [26](26-code-quality.md), [06](06-test-quality.md)). Разнесено по
  местам (решение владельца, 2026-10-07): размер диффа — 400 строк, предупреждение в
  `scripts/git_checks.py`; сложность — `IMP-001-readability-threshold`; мутации —
  `IMP-008-mutation-score-threshold`; дублирование (jscpd) — роадмап решения [34](34-core-vs-roadmap.md) §4.

## Гарантия

**Д**: чек-лист — договорённость; результат проверяется джобом «чистый клон» и проверкой здоровья
(когда внедрена).
