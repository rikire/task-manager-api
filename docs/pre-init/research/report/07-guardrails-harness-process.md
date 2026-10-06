# Часть 7. Ограждения, harness и сам процесс: что исполняется, а что только обещано

Эта часть закрывает восемь задач блока 4 брифа: детерминированные ограждения, тестирование harness, воспроизводимость, субагенты, обучение процесса, проверка процесса (evals), инструменты для создания процессов с помощью ИИ и история работы. Главный вывод такой. Почти всё, что агенту можно «сказать» (CLAUDE.md, скилл, промпт субагента, запись в памяти), остаётся **договорённостью**. Её исполнение зависит от того, прочтёт ли модель правило и выполнит ли его. **Гарантию** дают только механизмы, которые исполняют harness, ОС или CI: permission-правила, блокирующие хуки, sandbox, ограничение инструментов субагента, проверки в CI с защитой ветки, lockfile с контрольными суммами. При этом у каждого «гарантирующего» слоя есть задокументированный способ тихо отключиться: exit 1 у хука не блокирует, таймаут PreToolUse не блокирует, git-хуки не ставятся при клонировании, текстовые Bash-правила обходятся другой формой той же команды. Поэтому ограждения приходится тестировать как код, а последним рубежом должна стоять проверка, которую агент не может отключить локально. Количественных данных об эффективности почти нет. Есть вендорские методики и вендорские цифры, а независимых измерений того, насколько хук или строка в CLAUDE.md снижают число инцидентов, не найдено.

Терминология и метки — как в заметках: **гарантия** срабатывает независимо от решения модели (её исполняет harness, ОС или CI); **договорённость** зависит от того, прочтёт ли и выполнит ли правило модель или человек. [П] — сверено с первоисточником; [В] — вторичный источник или пересказ поисковика; [И] — интерпретация автора заметок. (вендор) — источник производителя инструмента; (старше 2025) — источник до 2025 года. Факты о Claude Code сверены с документацией (code.claude.com/docs/en/...) **2026-10-03**. Версии и поведение могут измениться: всё, что ниже относится к Claude Code, верно на эту дату.

---

## Ключевые выводы

- **CLAUDE.md — это договорённость по определению вендора.** Документация прямо называет его «context, not enforced configuration» и предлагает для блокировки PreToolUse-хук. Permission-правила «enforced by Claude Code, not by the model» [П] (вендор) ([memory](https://code.claude.com/docs/en/memory); [permissions](https://code.claude.com/docs/en/permissions)). Длинные файлы инструкций, по словам вендора, «reduce adherence»: ориентир — до 200 строк [П] (вендор).
- **Хук блокирует только в определённых условиях.** Блокирует exit 2 или JSON-решение. Exit 1 и любой другой код — «non-blocking error», и действие выполняется. PreToolUse-хук, превысивший таймаут, тоже не блокирует. Значит, guard-хук по умолчанию fail-open, и fail-closed его нужно делать явно [П] (вендор) ([hooks](https://code.claude.com/docs/en/hooks)).
- **Bash-правила сравнивают текст команды и сами говорят, что не являются границей безопасности.** `sh -c`, абсолютный путь, `git -C .`, Python- или Node-скрипт обходят deny. Настоящую границу на файлы и сеть даёт sandbox на уровне ОС, а последним рубежом остаётся CI [П] (вендор) ([permissions](https://code.claude.com/docs/en/permissions)).
- **Слой ограждений может быть тихо выключен по многим причинам:** git-хуки не ставятся при clone и обходятся `--no-verify`; `core.hooksPath` перекрывает каталог хуков; `claude` запущен из поддиректории (проектные хуки не загружены); workspace trust не принят (frontmatter-хуки субагентов и `permissions.allow` не применяются); `disableAllHooks`; cloud-сессия не читает user-настройки; в `bypassPermissions` снимается защита `.git`/`.claude`. Проверить, что ограждение живое, — отдельная задача: `/hooks`, `--debug-file`, тесты на записанных payload'ах, CI-джоб «чистый клон».
- **Субагенты в соло-backend оправданы как изолированные read-only исполнители** (поиск, ревью, верификация) с ограничением через `tools`/`disallowedTools`/`permissionMode` — это гарантия. Как параллельные «писатели» кода они не оправданы. Вендор сам пишет, что большинство coding-задач плохо распараллеливаются. Мультиагентная схема стоит ~15× токенов чата, а +90.2% измерены только на внутренних research-задачах Anthropic [П] (вендор) ([Anthropic Engineering](https://www.anthropic.com/engineering/multi-agent-research-system)).
- **Ошибку агента надёжнее превращать в тест, проверку CI или хук, чем в строку CLAUDE.md** [И]. Описанный паттерн «compound engineering» (Every) делает уроки систематическими, но количественных данных о его пользе нет [П] ([Every](https://every.to/guides/compound-engineering)).
- **Меняет ли инструкция поведение, проверяют сравнением с baseline «без неё».** `claude plugin eval` выводит колонки WITH / W/OUT / Δ и по умолчанию делает 3 прогона, потому что «a high score on its own doesn't tell you the plugin helped». Методика Anthropic: начинать с 20–50 задач из реальных провалов, различать pass@k и pass^k [П] (вендор) ([plugin evals](https://code.claude.com/docs/en/plugin-evals.md); [Demystifying evals](https://www.anthropic.com/engineering/demystifying-evals-for-ai-agents)).
- **Инструменты «агент пишет процесс для себя» бывают двух классов.** Валидаторы (`claude plugin validate`, skills-ref, agnix) гарантируют только синтаксис и схему. Поведенческие (skill-creator с eval/benchmark/слепым comparator, `claude plugin eval`) измеряют эффект, но стоят токенов. Ни один не гарантирует, что инструкция хорошая.
- **История работы — это прежде всего git** (атомарные conventional-коммиты, trailer для ИИ). Объективный учёт времени и стоимости без ручной дисциплины дают OTel-метрики Claude Code (`active_time.total`, `commit.count`, `cost.usage`) и ccusage по локальным транскриптам. Транскрипты лежат вне репозитория, и их нужно архивировать самому [П] (вендор) ([monitoring](https://code.claude.com/docs/en/monitoring-usage); [ccusage](https://github.com/ryoppippi/ccusage)).
- **Данных об эффективности почти нет ни по одной из восьми задач.** Ни вендор, ни независимые исследователи не публикуют, сколько инцидентов предотвращает хук по сравнению с инструкцией, сколько обходов случается в реальных сессиях и насколько меняет поведение строка в CLAUDE.md. Выбор механизма поэтому строится на свойстве «исполняется или нет», а не на измеренном эффекте.

---

## 1. Детерминированные ограждения: хуки, CI, pre-commit, запреты в правах — что выносить из инструкций в проверки; как убедиться, что ограждения действительно работают, а не тихо отключены

### Граница между гарантией и договорённостью проводит сам вендор

Документация Claude Code явно разделяет слои. Про CLAUDE.md и auto memory сказано: «Claude treats them as context, not enforced configuration. To block an action regardless of what Claude decides, use a PreToolUse hook instead» — и там же рекомендация держать каждый CLAUDE.md «under 200 lines», потому что длинные файлы «consume more context and reduce adherence» [П] (вендор) ([memory](https://code.claude.com/docs/en/memory)). Про permissions: «Permission rules are enforced by Claude Code, not by the model. Instructions in your prompt or CLAUDE.md shape what Claude tries to do, but they don't change what Claude Code allows» [П] (вендор) ([permissions](https://code.claude.com/docs/en/permissions)). На странице memory есть таблица распределения: блокировка инструментов и путей — `permissions.deny`, изоляция — `sandbox.enabled`, стиль кода и поведение — CLAUDE.md [П] (вендор) ([memory](https://code.claude.com/docs/en/memory)). Из этого получается простое правило отбора: в проверку выносится то, что выразимо кодом и проверяемо, а остальное остаётся договорённостью.

### Семантика хуков Claude Code (на 2026-10-03)

**События.** Полный список на дату проверки: SessionStart, Setup, UserPromptSubmit, UserPromptExpansion, PreToolUse, PermissionRequest, PermissionDenied, PostToolUse, PostToolUseFailure, PostToolBatch, Notification, MessageDisplay, SubagentStart, SubagentStop, TaskCreated, TaskCompleted, Stop, StopFailure, TeammateIdle, InstructionsLoaded, ConfigChange, CwdChanged, DirectoryAdded, FileChanged, WorktreeCreate, WorktreeRemove, PreCompact, PostCompact, PreModelSwitch, PostModelSwitch, Elicitation, ElicitationResult, SessionEnd [П] (вендор) ([hooks reference](https://code.claude.com/docs/en/hooks)).

**Какие события блокируются кодом выхода 2** [П] (вендор) ([hooks](https://code.claude.com/docs/en/hooks)):

| Событие | Что делает exit 2 / блок |
|---|---|
| PreToolUse | блокирует вызов инструмента |
| UserPromptSubmit, UserPromptExpansion | блокирует обработку промпта |
| Stop, SubagentStop | не даёт остановиться: агент продолжает работу |
| TeammateIdle, TaskCreated, TaskCompleted | блок |
| ConfigChange | блокирует изменение конфигурации, **кроме policy_settings** |
| PreCompact, PreModelSwitch | блок |
| Elicitation, ElicitationResult | блок |
| WorktreeCreate, WorktreeRemove | блокирует **любой ненулевой** код, не только 2 |

**Не блокируют** [П] (вендор) ([hooks](https://code.claude.com/docs/en/hooks)): PostToolUse, PostToolUseFailure, PostToolBatch (stderr показывается Claude, но «tool already ran»); PermissionRequest (exit 2 не учитывается, нужен JSON `decision`); PermissionDenied, Notification, SessionStart, SessionEnd и другие.

**Главная ловушка — exit 1.** «Without valid JSON on stdout, Claude Code treats exit code 1 as a non-blocking error and proceeds with the action, even though 1 is the conventional Unix failure code». Любой код, кроме 2, — «non-blocking error … the action proceeds», и в транскрипте остаётся только notice `Failed with non-blocking status code:` [П] (вендор) ([hooks](https://code.claude.com/docs/en/hooks)). На это регулярно жалуются: например, issue #44707 «[Docs] Hook exit codes: exit(1) silently non-blocking — needs prominent warning» [В] ([claudeissues.com #44707](https://claudeissues.com/issue/44707-docs-hook-exit-codes-exit-1-silently-non-blocking-needs-prominent-warning)). Следствие: скрипт-ограждение, который упал из-за собственной ошибки (не найден `jq`, опечатка, необработанное исключение), **пропускает действие**.

**Таймауты.** command/http/mcp_tool — 600 с, prompt — 30 с, agent — 60 с; UserPromptSubmit — 30 с; у SessionEnd общий бюджет 1.5 с. Ключевая оговорка: «On PreToolUse, timed-out hooks don't block», тогда как на PreModelSwitch зависший хук блокирует [П] (вендор) ([hooks](https://code.claude.com/docs/en/hooks)). Зависший или медленный guard-хук на PreToolUse — это fail-open.

**Типы хуков и условие `if`.** Типы: `command`, `http`, `mcp_tool`, `prompt` (одноходовая оценка моделью), `agent` (субагент-верификатор). Поле `if` использует синтаксис permission-правил (`"if": "Bash(git *)"`), и «When Claude's command is ambiguous, hook runs anyway» [П] (вендор) ([hooks](https://code.claude.com/docs/en/hooks)). Хуки типа `prompt` и `agent` сами недетерминированы, потому что решение принимает модель [И].

**Формат решения.** Для PreToolUse: `hookSpecificOutput.permissionDecision: allow|deny|ask|defer`, `permissionDecisionReason`, `updatedInput`, `additionalContext`. Top-level `decision: "block"` — для UserPromptSubmit, PostToolUse, Stop, SubagentStop, ConfigChange, PreCompact, TaskCreated и других [П] (вендор) ([hooks](https://code.claude.com/docs/en/hooks)).

**Взаимодействие с permission-правилами** асимметрично. Хук не может ослабить правила: «PreToolUse hook decisions don't bypass permission rules … a matching deny rule blocks the call, and a matching ask rule still prompts even when the hook returned "allow"». Ужесточить может: «A hook that exits with code 2 stops the tool call before permission rules are evaluated, so the block applies even when an allow rule would otherwise let the call proceed» [П] (вендор) ([permissions](https://code.claude.com/docs/en/permissions)).

### Permission deny-правила и managed settings

Приоритет — deny > ask > allow. «An allow rule can't carve an exception out of a deny rule»; deny с любого уровня бьёт allow с любого уровня; managed deny не перекрывается даже `--allowedTools` [П] (вендор) ([permissions](https://code.claude.com/docs/en/permissions)). Голое имя инструмента в deny (`Bash`) убирает инструмент из контекста целиком. Скоуп-правило (`Bash(rm *)`) оставляет инструмент и блокирует только совпадающие вызовы [П] (вендор). `permissions.disableBypassPermissionsMode` и `disableAutoMode` со значением `"disable"` работают с любого scope: «A user can set it in their own settings to lock themselves out of bypass mode». `allowManagedPermissionRulesOnly` оставляет только managed-правила [П] (вендор) ([permissions](https://code.claude.com/docs/en/permissions)).

Защищённые пути `.git` и `.claude` требуют подтверждения записи во всех режимах, кроме `bypassPermissions`: в нём Claude Code пропускает промпты «including for writes to protected paths such as .git and .claude» [П] (вендор) ([permissions](https://code.claude.com/docs/en/permissions)). Моды (плагины-моды с `tool.check`) могут перекрыть блок PreToolUse-хука (кроме managed), а вне managed/Team/Enterprise — даже deny-правило [П] (вендор) ([permissions](https://code.claude.com/docs/en/permissions)).

### Как ограждение оказывается тихо выключенным (сводный список обходов и отказов)

Это самая ценная для практики часть. Каждый пункт — документированный путь, по которому ограждение перестаёт работать без явной ошибки.

| # | Как ограждение выключается или обходится | Источник |
|---|---|---|
| 1 | Хук завершился с exit 1 или другим кодом, кроме 2, без валидного JSON: действие выполняется | [П] (вендор) [hooks](https://code.claude.com/docs/en/hooks) |
| 2 | PreToolUse-хук превысил таймаут: не блокирует | [П] (вендор) [hooks](https://code.claude.com/docs/en/hooks) |
| 3 | Хук стоит на неблокирующем событии (PostToolUse и др.): инструмент уже отработал | [П] (вендор) [hooks](https://code.claude.com/docs/en/hooks) |
| 4 | PermissionRequest с exit 2: игнорируется, нужен JSON `decision` | [П] (вендор) [hooks](https://code.claude.com/docs/en/hooks) |
| 5 | `"disableAllHooks": true`; при этом `false` в project settings перекрывает `true` в user settings; managed-хуки не отключаются из не-managed настроек; `allowManagedHooksOnly` блокирует user/project/local/plugin-хуки; хуки разных уровней сливаются, а не заменяют друг друга | [П] (вендор) [hooks](https://code.claude.com/docs/en/hooks) |
| 6 | `claude` запущен из поддиректории: хуки из `.claude/settings.json` грузятся только из `.claude/` текущей директории «with no parent-directory fallback» | [П] (вендор) [permissions](https://code.claude.com/docs/en/permissions) |
| 7 | Workspace trust не принят: frontmatter-хуки проектного субагента не работают; в `claude -p`/SDK без доверия — «Not used, and no dialog is offered» | [П] (вендор) [permissions](https://code.claude.com/docs/en/permissions); [sub-agents](https://code.claude.com/docs/en/sub-agents) |
| 8 | Cloud-сессия: `~/.claude/settings.json` не читается, переносятся только managed и project settings | [П] (вендор) [hooks](https://code.claude.com/docs/en/hooks) |
| 9 | Режим `bypassPermissions`: снимается защита записи в `.git`/`.claude` | [П] (вендор) [permissions](https://code.claude.com/docs/en/permissions) |
| 10 | Мод с `tool.check` перекрывает блок хука (кроме managed) и вне managed/Team/Enterprise — deny-правило | [П] (вендор) [permissions](https://code.claude.com/docs/en/permissions) |
| 11 | Bash-правило обойдено другой формой команды (`sh -c`, абсолютный путь, `git -C .` и т.п.) | [П] (вендор) [permissions](https://code.claude.com/docs/en/permissions) (подробно в разделе 2) |
| 12 | Read/Edit deny не срабатывает для `grep -r pattern .` и скриптов, открывающих файлы сами | [П] (вендор) [permissions](https://code.claude.com/docs/en/permissions) |
| 13 | Правила `Write(...)`/`NotebookEdit(...)`/`Glob(...)` по путям принимаются, но «never consults it»; `Bash(command:…)` игнорируется с warning | [П] (вендор) [permissions](https://code.claude.com/docs/en/permissions) |
| 14 | `git commit --no-verify`: пропускает pre-commit, commit-msg, pre-merge-commit, pre-push (но не prepare-commit-msg) | [П] [git-scm githooks](https://git-scm.com/docs/githooks) (старше 2025 — справочник git, но актуален) |
| 15 | `core.hooksPath` переопределяет каталог хуков: второй менеджер хуков (например, при husky, который ставит `.husky`) молча не работает | [П] [githooks](https://git-scm.com/docs/githooks) (старше 2025); следствие — [И] |
| 16 | Свежий клон без шага `install`: git-хуков нет, при clone они не копируются | [В] [githooks](https://git-scm.com/docs/githooks) (пересказ страницы) |
| 17 | Branch protection у соло-владельца: админ может её обойти, если не включено «Do not allow bypassing» — это гарантия от агента, не от себя | [И] |
| 18 | Для Codex: tool hooks не перехватывают hosted-инструменты (например, WebSearch) | [П] (вендор) [Codex hooks](https://learn.chatgpt.com/docs/hooks) |
| 19 | Cursor: на форуме множественные отчёты «hooks not firing»; `ask` игнорируется при allow-list/sandbox | [В] [Cursor forum: hooks not firing](https://forum.cursor.com/t/hooks-not-firing-cannot-have-guardrails/168407); [ask ignored](https://forum.cursor.com/t/beforeshellexecution-hook-permissions-allow-ask-ignored-allow-list-takes-precedence/144244) |

**Как проверить, что ограждения загружены.** `/hooks` — «read-only browser for your configured hooks … labels each hook with where it comes from»; подробный лог — `claude --debug-file <path>` [П] (вендор) ([hooks](https://code.claude.com/docs/en/hooks)). Это показывает, что хук загружен, но не то, что он корректно блокирует. Для второго нужны тесты из раздела 2.

### Аналоги в других агентах

Механизм хуков с блокирующим PreToolUse есть не только в Claude Code, поэтому принцип переносим, а детали — нет. В OpenAI Codex события такие: SessionStart, SessionEnd, SubagentStart, SubagentStop, PreToolUse, PermissionRequest, PostToolUse, PreCompact, PostCompact, UserPromptSubmit, Stop, Interrupt. Блок — exit 2 или JSON `decision: block` / `permissionDecision: deny`. Вендор прямо пишет: «Treat tool hooks as a useful guardrail, not a complete enforcement boundary». Не-managed хуки требуют ревью, доверие привязано к хешу хука (после изменения нужно переутвердить), managed-хуки задаются через `requirements.toml` [П] (вендор) ([Codex hooks](https://learn.chatgpt.com/docs/hooks), редирект с developers.openai.com/codex/hooks). По стороннему блогу, хуки Codex стали stable в v0.124.0 (23.04.2026), с inline-конфигом `[[hooks.PreToolUse]]` в `config.toml` [В] ([D. Vaughan blog](https://codex.danielvaughan.com/2026/04/23/codex-cli-hooks-graduate-stable-v0124-mcp-observation-inline-config/)). В Cursor есть `beforeShellExecution`, `beforeReadFile`, `preToolUse` с ответом `permission: allow|deny|ask` и опция `failClosed`. На форуме советуют считать надёжным только deny [В] ([Cursor forum](https://forum.cursor.com/t/hooks-not-firing-cannot-have-guardrails/168407); [ask ignored](https://forum.cursor.com/t/beforeshellexecution-hook-permissions-allow-ask-ignored-allow-list-takes-precedence/144244)). В Gemini CLI есть событие `BeforeTool` в `settings.json` с JSON через stdin/stdout [В] ([Gemini CLI hooks reference](https://geminicli.com/docs/hooks/reference/)).

### Что выносить из инструкций в проверки (варианты, не готовый набор)

Заметки предлагают такой отбор для малого проекта [И]. Формат, линт и типы — PostToolUse-хук на Edit|Write с exit 2 (фидбэк агенту) плюс pre-commit плюс CI. Запрет правок секретов, `.env`, lock-файлов и CI-конфигов — deny `Edit(...)`, `Read(./.env)`. Запрет `git push --force` и `--no-verify` — PreToolUse, но паттерн обходится вариациями (раздел 2). Правило «не заканчивать, пока тесты красные» — Stop-хук с exit 2, с осторожностью из-за возможного зацикливания. Архитектура, стиль именования, «объясняй решения» остаются договорённостью.

Дополнительные варианты [И]:
- **Guard-хук обязан быть fail-closed.** Скрипт с `set -euo pipefail` и trap переводит любую внутреннюю ошибку в exit 2. Нужен короткий явный `timeout`. В PreToolUse не стоит полагаться на сетевые вызовы.
- **Managed settings** (`/etc/claude-code/managed-settings.json`) защищают ограждения от самого агента: `.claude/settings.json` в проекте агент править может, `/etc` — нет. По оценке заметок, это окупается уже для одного разработчика, если агент работает в auto/acceptEdits. Пропорционально — deny-список и пара хуков. `allowManagedHooksOnly` для соло избыточен.
- **Последний рубеж — CI с required status checks и защитой ветки**, которые локально не отключить. Ограждение, которое должно работать везде (в том числе в cloud-сессиях), кладётся в проектный `.claude/settings.json` под VCS, а не в user-scope.

### Таблица механизмов

| Механизм | Вид | Гарантия или договорённость | Данные |
|---|---|---|---|
| Правило в CLAUDE.md / AGENTS.md | инструкция | договорённость («context, not enforced configuration») [П] (вендор) | данных нет (только качественное «reduce adherence» для длинных файлов) |
| `permissions.deny` (инструмент или путь) | запрет в правах | гарантия для распознаваемых форм вызова; для Bash — только по тексту команды | данных нет |
| PreToolUse-хук, exit 2 / JSON deny | хук | гарантия, **если** хук загружен, отвечает в пределах таймаута и не падает с кодом ≠2 | данных нет |
| PostToolUse-хук (линт/типы) | хук | не блокирует; даёт обратную связь агенту после действия | данных нет |
| Stop-хук с exit 2 («тесты зелёные») | хук | гарантия, что агент не остановится без прохождения проверки (риск зацикливания) [И] | данных нет |
| ConfigChange-хук | хук | гарантия блока изменения конфигурации, кроме policy_settings | данных нет |
| Sandbox (`sandbox.enabled`) | ОС-уровень | гарантия для Bash, PowerShell, Monitor и их дочерних процессов | данных нет |
| Managed settings / managed deny | конфигурация вне репозитория | гарантия от агента (не перекрывается `--allowedTools`) | данных нет |
| `disableBypassPermissionsMode` | запрет в правах | гарантия (самоблокировка возможна с любого scope) | данных нет |
| git pre-commit / commit-msg | git-хук | договорённость на практике: обходится `--no-verify`, не ставится при clone | данных нет |
| CI + branch protection (required checks) | CI | гарантия от агента; от админа-владельца — только с «Do not allow bypassing» [И] | данных нет |

### Пропорциональность

Для малого проекта окупаются deny-список (секреты, `.env`, CI-конфиги, force-push), 2–4 fail-closed PreToolUse/PostToolUse-хука, Stop-хук на тесты, `disableBypassPermissionsMode` и CI с required checks. Managed settings стоят немного и защищают от правки ограждений самим агентом [И]. Окупаются только на большом проекте или в команде `allowManagedHooksOnly`, `allowManagedPermissionRulesOnly`, централизованная раздача managed-хуков и многоуровневые хуки `prompt`/`agent` (недетерминированные и дорогие) [И].

### Что уточнить позже

Когда станет известен стек: какой менеджер git-хуков (pre-commit, lefthook, husky) и как он ведёт себя при занятом `core.hooksPath` (не проверено). Будет ли агент работать в auto/acceptEdits (от этого зависит ценность managed settings). Где CI и есть ли у тарифа required status checks. Нужен ли sandbox для сети (зависит от того, ходит ли сервис во внешние API при тестах).

---

## 2. Тестирование самого harness: как проверять хуки и правила (обходы через shell, формат входных событий, который надо наблюдать в реальной сессии, а не придумывать)

### Вендор сам признаёт, что текстовые правила — не граница

Документация Claude Code в разделе «Bash rule limits» формулирует это прямо: «A Bash rule matches the command text Claude writes … It doesn't match the same program invoked in a different form, so a deny or ask rule covers the invocation Claude usually produces and isn't a security boundary around the program» [П] (вендор) ([permissions → Bash rule limits](https://code.claude.com/docs/en/permissions)). Примеры из документации:

| Правило | Не ловит |
|---|---|
| `Bash(curl *)` | `/usr/bin/curl …`, `sh -c 'curl …'` |
| `Bash(rm *)` | `/bin/rm`, `bash -c 'rm …'` |
| `Bash(git push *)` | `git -C . push`, `git -c push.default=current push`, `git 'push' origin main` |
| `Bash(curl http://github.com/ *)` | опции перед URL, https, редиректы, переменные (`URL=… && curl $URL`) |

«Bash permission patterns that try to constrain command arguments are fragile». Вместо этого вендор рекомендует deny на curl/wget плюс `WebFetch(domain:…)` плюс network allowlist в sandbox либо PreToolUse-хук [П] (вендор) ([permissions](https://code.claude.com/docs/en/permissions)).

**Файловые deny-правила.** Read/Edit deny применяются к встроенным файловым инструментам, к распознанным Bash-командам (`cat`, `head`, `tail`, `sed`, `tee`) и к редиректам `>`/`<`, но «don't apply to a command that reads files without naming them, such as `grep -r pattern .` … or to arbitrary subprocesses that read or write files indirectly, like a Python or Node script that opens files itself. For OS-level enforcement … enable the sandbox» [П] (вендор) ([permissions](https://code.claude.com/docs/en/permissions)). Интерпретация заметок: классический обход «deny на Edit → `sed -i`» для `sed` с явным путём частично закрыт, а `python -c "open('x','w')…"`, `node -e` и `perl -pi` (его нет в списке) не закрыты. Список распознаваемых команд дан как «such as», полный перечень не опубликован [И].

**Составные команды.** Разделители — `&&`, `||`, `;`, `|`, `|&`, `&`, newline. Deny/ask срабатывают на любую подкоманду, включая subshell, `$()` и тело `for`. Правило по полю `command` (`Bash(command:rm *)`) игнорируется с warning, потому что оно «would be bypassable by a compound command». Путевые правила для `Write(...)`/`NotebookEdit(...)`/`Glob(...)` принимаются, но «never consults it»: писать нужно `Edit(...)`/`Read(...)`. С v2.1.210 об этом выдаётся warning при старте [П] (вендор) ([permissions](https://code.claude.com/docs/en/permissions)).

**Sandbox как настоящая граница.** «OS-level enforcement … applies only to Bash, PowerShell, and Monitor commands and their child processes»; «Use both for defense-in-depth, since sandbox restrictions still apply even if a prompt injection bypasses Claude's decision-making» [П] (вендор) ([permissions](https://code.claude.com/docs/en/permissions)). Codex пишет то же самое: «Treat tool hooks as a useful guardrail, not a complete enforcement boundary» [П] (вендор) ([Codex hooks](https://learn.chatgpt.com/docs/hooks)).

### Формат входа меняется между версиями, поэтому его снимают с реальной сессии

Общие поля входа хука: `session_id`, `prompt_id`, `transcript_path`, `cwd`, `permission_mode`, `hook_event_name`, в субагенте ещё `agent_id`, `agent_type`. Для tool-событий добавляются `tool_name`, `tool_input`, `tool_use_id`. Поле `scratchpad_dir` появилось только с v2.1.257+ [П] (вендор) ([hooks](https://code.claude.com/docs/en/hooks)). Формат меняется между версиями, поэтому payload нужно записывать из реальной сессии текущей версии, а не придумывать [И]. PreToolUse/PostToolUse срабатывают и для вызовов субагента, с `agent_id`/`agent_type` во входе [П] (вендор) ([hooks](https://code.claude.com/docs/en/hooks)): хук, который должен ограничивать только основной агент или только субагента, обязан проверять эти поля.

**Нюансы разбора вывода хука** [П] (вендор) ([hooks](https://code.claude.com/docs/en/hooks)): stdout, который начинается с `{` и заканчивается `}`, разбирается как JSON; многострочный вывод из нескольких JSON без полей — как текст; вывод длиннее 10 000 символов заменяется путём к файлу и превью на 2 000 символов. Случайный отладочный `echo` перед JSON может сломать решение хука [И].

### Процедура тестирования harness (вариант, предложенный в заметках) [И]

Заметки оценивают минимальную процедуру для соло-проекта в 1–2 часа:

1. **Запись payload'ов.** Временный логирующий хук (`cat > /tmp/payload-$(date +%s).json`) на нужное событие, прогон реальной сессии, сохранение payload'ов как фикстур (`tests/hooks/fixtures/*.json`).
2. **Unit-тесты хук-скрипта.** `script < fixture.json`, проверка `exit==2` или JSON на stdout (bats, pytest или shell-скрипт), запуск в CI. Это гарантирует, что **скрипт** корректен, но не то, что он **загружен**.
3. **Набор обходных вариантов.** Таблица из документации плюс `python -c`, `node -e`, `perl -pi`, `git -C`, `env X=1 git push`, абсолютные пути. Для каждого deny или хука нужно либо проверить блок, либо явно записать «не покрыто, держит sandbox/CI».
4. **Smoke-тест загрузки.** `/hooks` плюс `claude -p` с промптом, который должен упереться в блок (end-to-end). Учтите, что в `-p` без trust frontmatter-хуки субагентов не грузятся.

Критерий отбора [И]: ограждение, обход которого нельзя протестировать, — это договорённость, а не гарантия. Для реальной границы на файлы и сеть нужен sandbox, а не текстовые паттерны.

### Таблица механизмов

| Механизм | Вид | Гарантия или договорённость | Данные |
|---|---|---|---|
| Unit-тесты хук-скриптов на записанных payload'ах | тест + CI | гарантия корректности скрипта (не его загрузки) [И] | данных нет |
| Набор «атакующих» вариантов команд | тест | гарантия покрытия перечисленных форм; непокрытые формы явно записаны [И] | данных нет |
| `/hooks`, `--debug-file` | ручная проверка | договорённость (зависит от того, посмотрит ли человек) | данных нет |
| E2E `claude -p` с заведомо блокируемым промптом | тест | частичная гарантия загрузки (оговорка про trust) [И] | данных нет |
| Предупреждения Claude Code при старте о неиспользуемых правилах | встроенная проверка | гарантия, что предупреждение будет показано; реакция — договорённость | данных нет |
| Sandbox | ОС-уровень | гарантия для Bash/PowerShell/Monitor и дочерних процессов | данных нет |
| `claude plugin eval` (поведение плагина) | eval | измерение, не гарантия (см. раздел 6) | вендорская методика, независимых данных нет |

### Пропорциональность

Для малого проекта окупаются фикстуры, unit-тесты 2–4 хук-скриптов в CI, короткий список обходов на каждое deny и `/hooks` при онбординге. Это часы, а не дни [И]. Полный red-team набор, фаззинг формы команд и тестирование матрицы версий Claude Code окупаются только тогда, когда harness переиспользуется на многих проектах или людьми, которым агент не принадлежит [И].

### Что уточнить позже

Есть ли (появится ли) официальный инструмент Anthropic для unit-тестирования хуков на записанных payload'ах. Найден только `claude plugin eval`, который тестирует поведение плагина. Сторонние инструменты (упомянутый в поиске `garyd203/hokum`) не проверены. Полный список Bash-команд, которые Claude Code распознаёт как файловые, не опубликован. При обновлении Claude Code нужно перезаписывать фикстуры, потому что поля входа меняются.

---

## 3. Воспроизводимость окружения и процесса: чистая машина, онбординг, проверка, что хуки реально запускаются; контрольные суммы скачиваемых инструментов

### Что даёт гарантию воспроизводимости

Гарантию дают lockfile с контрольными суммами и проверка в CI на чистой машине. Документ онбординга — договорённость. Конкретный пример: **mise.lock** записывает версии, а по платформам — checksums, URL и данные верификации. При установке он «validates downloaded artifacts against recorded checksums» для поддерживаемых бэкендов и фиксирует результаты SLSA, Cosign, Minisign и GitHub attestations. При этом «A provenance field alone is not proof that the bytes were verified»: принудительная перепроверка включается через `locked_verify_provenance = true`. Режим `lockfile_mode = "generate"` экспериментальный, дефолт `merge` «pending … before mise 2026.12.0» [П] (вендор) ([mise.lock](https://mise.jdx.dev/dev-tools/mise-lock.html)). Значит, на дату проверки поведение по умолчанию ещё может измениться.

### Воспроизводимость harness агента

В Claude Code проектные хуки и настройки лежат в `.claude/settings.json` (под VCS, «Shareable»), а `settings.local.json` — в gitignore. Cloud-сессии user-настроек не видят [П] (вендор) ([hooks](https://code.claude.com/docs/en/hooks)). Без принятого workspace trust `permissions.allow` из `.claude/settings.json` не используются, в `claude -p` печатается warning «this workspace has not been trusted», а frontmatter-хуки проектных субагентов не используются [П] (вендор) ([permissions](https://code.claude.com/docs/en/permissions)). Git-хуки клоном не переносятся, нужен шаг установки [В] ([githooks](https://git-scm.com/docs/githooks)). Итог: на чистой машине процесс воспроизводится только при трёх условиях — настройки под VCS, явный bootstrap, принятый trust.

### Варианты по нарастанию стоимости [И]

| Вариант | Что даёт | Цена | Когда окупается |
|---|---|---|---|
| `mise.toml` + `mise.lock` + языковой lockfile + `make bootstrap` / `mise run setup`, который ставит git-хуки | версии и суммы инструментов, одна команда онбординга | низкая | малый проект |
| devcontainer | идентичное окружение агенту и человеку; естественная граница для sandbox и bypass-режимов | средняя | когда агент работает в автономных режимах |
| Nix flake | максимальная воспроизводимость | высокий порог входа | большие и многоязычные проекты |

(Первоисточники devcontainers, Nix, `gh attestation verify` и SLSA levels в заметках не открыты, поэтому строки 2–3 — [И].)

**Проверка, что хуки реально работают** [И]: CI-джоб «clean clone» (`git clone` → bootstrap → проверка `git config core.hooksPath` или наличия `.git/hooks/pre-commit` → попытка коммита с заведомо плохим файлом, которая должна упасть). Для Claude-хуков — тесты на фикстурах из раздела 2 и `/hooks` вручную при онбординге.

**Контрольные суммы скачиваемых инструментов** [И]: ручной `sha256sum -c` даёт гарантию, если сумма зафиксирована в репозитории до скачивания. SLSA/sigstore (`cosign verify`, `gh attestation verify`) для соло стоит применять в «лёгком» варианте и только к инструментам, скачиваемым мимо пакетного менеджера.

### Таблица механизмов

| Механизм | Вид | Гарантия или договорённость | Данные |
|---|---|---|---|
| Языковой lockfile | артефакт | гарантия версий зависимостей (при установке по lock) [И] | данных нет |
| mise.lock с checksums | артефакт + инструмент | гарантия сверки для поддерживаемых бэкендов; provenance — только при `locked_verify_provenance` [П] (вендор) | данных нет |
| `sha256sum -c` по зафиксированной сумме | скрипт | гарантия [И] | данных нет |
| `.claude/settings.json` под VCS | конфигурация | гарантия переноса проектных настроек; применение зависит от cwd и trust | данных нет |
| bootstrap-скрипт, ставящий git-хуки | скрипт | договорённость (его надо запустить) | данных нет |
| CI-джоб «чистый клон» | CI | гарантия, что bootstrap работает и хуки ставятся [И] | данных нет |
| Документ онбординга | артефакт | договорённость | данных нет |
| devcontainer / Nix | окружение | гарантия идентичности окружения [И] | данных нет |

Данных о влиянии воспроизводимости на агентную разработку как таковую нет.

### Пропорциональность

Для малого проекта достаточно: lockfile, `mise.lock` (или аналог), одна команда bootstrap и CI-джоб «чистый клон». devcontainer стоит рассматривать, если агент будет работать в auto/bypass-режиме, потому что контейнер одновременно служит границей. Nix окупается на больших и многоязычных проектах [И].

### Что уточнить позже

Стек определит языковой lockfile и наличие бэкенда mise с checksums. Нужно отследить, как изменится дефолт `lockfile_mode` в mise 2026.12.0. Проверить первоисточники devcontainers, Nix и `gh attestation verify`. Решить, будет ли работа идти в cloud-сессиях: тогда всё нужное должно быть в project или managed settings.

---

## 4. Работа с субагентами: когда они оправданы, как ставить им задачу и проверять результат; ограничения через список инструментов, а не через промпт; оценка вслепую

### Когда оправданы: данные и критика

**Вендорские данные.** В исследовании Anthropic о мультиагентной research-системе (13.06.2025) агент тратит ~«4× more tokens than chat», мультиагентная схема — ~«15× more tokens than chats». Получено «90.2% performance improvement» (Opus 4 lead + Sonnet 4 субагенты против одиночного Opus 4 на внутреннем research-eval), а число токенов объясняет ~80% дисперсии на browsing-eval [П] (вендор) ([Anthropic Engineering](https://www.anthropic.com/engineering/multi-agent-research-system)). Тот же источник оговаривает: «most coding tasks involve fewer truly parallelizable tasks than research, and LLM agents are not yet great at coordinating and delegating to other agents in real time» [П] (вендор). Переносить 90.2% и 15× на написание кода нельзя.

**Критика.** Cognition (Walden Yan, 12.06.2025) формулирует принципы «Share context, and share full agent traces, not just individual messages» и «Actions carry implicit decisions, and conflicting decisions carry bad results». Пример — Flappy Bird, где параллельные субагенты сделали несовместимые фон и птицу. Рекомендация — single-threaded linear agent и компрессия истории. О Claude Code там сказано: «it never does work in parallel with the subtask agent, and the subtask agent is usually only tasked with answering a question, not writing any code» [П] ([Cognition](https://cognition.com/blog/dont-build-multi-agents)). Источник вендорский (Devin), а утверждение о Claude Code относится к 2025 году: сейчас Claude Code поддерживает фоновые и параллельные субагенты.

**Позиция документации Claude Code.** «Use subagents when: The task produces verbose output you don't need in your main context; You want to enforce specific tool restrictions or permissions; The work is self-contained and can return a summary». Основной диалог лучше, когда нужна итерация, общий контекст фаз, быстрая правка или важна задержка [П] (вендор) ([sub-agents](https://code.claude.com/docs/en/sub-agents)).

Вместе эти три источника дают согласованную картину [И]: для соло-backend субагенты оправданы как изолированные **read-only** исполнители (поиск, ревью, верификация, прогон тестов). Параллельные «писатели» кода с неявными решениями, которые могут друг другу противоречить, не оправданы.

### Как ставить задачу

Anthropic перечисляет обязательное содержание брифа: «an objective, an output format, guidance on the tools and sources to use, and clear task boundaries» [П] (вендор) ([Anthropic Engineering](https://www.anthropic.com/engineering/multi-agent-research-system)). Изоляция контекста в Claude Code: субагент (не fork) не видит историю диалога, ранее вызванные скиллы и auto memory основного диалога. Он получает CLAUDE.md (если не задан `omitClaudeMd`), git status и preloaded skills. Fork наследует весь диалог и prompt cache. Встроенные Explore и Plan — read-only и пропускают CLAUDE.md [П] (вендор) ([sub-agents](https://code.claude.com/docs/en/sub-agents)). Практическое следствие: всё, что субагенту нужно знать о задаче, должно быть в брифе. «Он же видел, о чём мы договорились» неверно для не-fork субагента [И].

### Ограничения через инструменты, а не через промпт

Механизмы Claude Code [П] (вендор) ([sub-agents](https://code.claude.com/docs/en/sub-agents)): `tools` (allowlist) и `disallowedTools` (denylist, применяется первым); без `tools` наследуются все инструменты. `permissionMode` (default/acceptEdits/auto/dontAsk/bypassPermissions/plan). Хуки во frontmatter. `memory: user|project|local`. Вложенность до 3 уровней (`CLAUDE_CODE_MAX_SUBAGENT_SPAWN_DEPTH`); чтобы запретить спавн, нужно убрать `Agent` из tools. Не больше 20 субагентов одновременно. Отчёты субагентов сканируются на текст, похожий на инструкции (защита от инъекций), но «The scan never removes or rewords anything» [П] (вендор). Поэтому отчёт субагента остаётся данными, а не командами, и следить за этим должен основной агент или человек.

Интерпретация [И]: `tools`/`disallowedTools`/`permissionMode` — гарантия, их исполняет harness. «Не редактируй файлы» в промпте субагента — договорённость. Ревьюеру или верификатору подходит `tools: Read, Grep, Glob`. Bash добавляется только если нужен прогон тестов, и тогда с PreToolUse-хуком во frontmatter. Не забывайте, что frontmatter-хуки проектного субагента без trust не работают (раздел 1, строка 7 таблицы обходов).

### Проверка результата и оценка вслепую

Проверка результата [И]: требовать от субагента формат с путями и строками и проверяемыми утверждениями. Основной агент или человек перепроверяет выборочно, потому что субагент тоже может галлюцинировать.

Оценка вслепую. В инструментарии Anthropic есть прямой механизм: skill-creator «Comparator Agents: Enable A/B testing between two skill versions or skill versus no skill, with blind judging» [П] (вендор) ([Claude blog, 03.03.2026](https://claude.com/blog/improving-skill-creator-test-measure-and-refine-agent-skills)). Принцип из брифа «кто писал правила, тот не оценивает их эффект» реализуется так [И]: эффект правила или скилла оценивает независимый грейдер — субагент со свежим контекстом, который не знает, какая версия новая, либо код-грейдер. Иначе получается самооценка с конфликтом интересов.

### Таблица механизмов

| Механизм | Вид | Гарантия или договорённость | Данные |
|---|---|---|---|
| `tools` / `disallowedTools` | конфигурация субагента | гарантия [И на основе П] | данных нет |
| `permissionMode` субагента | конфигурация | гарантия | данных нет |
| Хуки во frontmatter субагента | хук | гарантия **только** при принятом trust | данных нет |
| Удаление `Agent` из tools / лимит глубины | конфигурация | гарантия от рекурсивного спавна | данных нет |
| Ограничение в промпте субагента | инструкция | договорённость | данных нет |
| Бриф (цель, формат, инструменты, границы) | инструкция | договорённость | вендорская рекомендация, без цифр |
| Скан отчётов на инъекции | встроенная функция | только помечает, ничего не удаляет | данных нет |
| Слепой comparator (skill-creator) | субагент-грейдер | измерение без знания авторства | вендорская функция, независимых данных нет |
| Мультиагентная схема для research | архитектура | — | [П] (вендор) +90.2%, ~15× токенов, только research |

Данных об эффективности субагентов в coding-задачах нет: 90.2% относятся только к вендорским research-задачам, независимых измерений нет.

### Пропорциональность

Для малого сервиса достаточно 1–3 субагентов: read-only explorer, reviewer, test-runner/verifier. Параллельные писатели и оркестрация окупаются на больших широких задачах (исследование, массовые миграции), а не на одном сервисе, учитывая цену в ×4–15 токенов [И].

### Что уточнить позже

Появятся ли независимые измерения пользы субагентов на коде. Какие команды прогона тестов понадобятся test-runner'у (от этого зависит, давать ли ему Bash и какой хук ставить). Как именно субагент-ревьюер будет получать diff, если он не видит историю диалога.

---

## 5. Обучение процесса: как ошибка агента превращается в изменение правил, хуков или тестов; кто может менять инструкции агента; ревью изменений инструкций отдельно от кода

### Описанный паттерн: compound engineering

Every (Kieran Klaassen, с участием Trevin Chow) описывает цикл «Plan → Work → Review → Compound → Repeat». Шаг Compound: «Capture the solution… What's the reusable insight?», «Make it findable» (YAML frontmatter), «Update the system. Add new patterns into CLAUDE.md … Create new agents when warranted». Плагин включает 26 агентов, 23 команды и 13 скиллов (Claude Code, OpenCode, Codex). **Количественных утверждений об эффективности на странице нет, дата не указана** [П] (вендор-практик) ([Every: compound engineering guide](https://every.to/guides/compound-engineering)). Anthropic в методике evals рекомендует брать задачи «from real failures», то есть каждая ошибка агента становится кейсом регрессионного eval [П] (вендор) ([Demystifying evals](https://www.anthropic.com/engineering/demystifying-evals-for-ai-agents)).

### Механизмы памяти и их владельцы

В Claude Code два механизма [П] (вендор) ([memory](https://code.claude.com/docs/en/memory)). CLAUDE.md пишет человек. Auto memory пишет Claude в `~/.claude/projects/<project>/memory/`; загружаются первые 200 строк или 25KB `MEMORY.md`. Включается и выключается через `autoMemoryEnabled`, просматривается через `/memory`, файлы — обычный markdown, который человек может править. Managed CLAUDE.md (`/etc/claude-code/CLAUDE.md` на Linux) «cannot be excluded by individual settings»; в managed-settings есть ключ `claudeMd` [П] (вендор). Событие `ConfigChange` может блокировать изменения конфигурации (exit 2), кроме policy_settings, а `InstructionsLoaded` только наблюдает за загрузкой инструкций и не блокирует [П] (вендор) ([hooks](https://code.claude.com/docs/en/hooks)). `.claude` и `.git` — protected paths: запись требует подтверждения во всех режимах, кроме bypassPermissions [П] (вендор) ([permissions](https://code.claude.com/docs/en/permissions)).

### Лестница «во что превращать ошибку» [И]

Заметки предлагают упорядочить варианты от гарантии к договорённости и выбирать самый высокий уровень, который реально выразим кодом:

| Уровень | Во что превращается ошибка | Вид |
|---|---|---|
| 1 | тест в сьюте проекта | гарантия (в CI) |
| 2 | проверка в CI / pre-commit | гарантия в CI; pre-commit обходится |
| 3 | PreToolUse / PostToolUse / Stop-хук | гарантия с оговорками раздела 1 |
| 4 | deny-правило | гарантия для распознаваемых форм |
| 5 | eval-кейс для скилла | измерение, не блок |
| 6 | строка в CLAUDE.md / скилле | договорённость |
| 7 | запись в auto memory | договорённость; пишет сам агент |

### Кто может менять инструкции

По умолчанию агент может править CLAUDE.md и `.claude/**`: они защищены только промптом подтверждения, а в bypass свободны [И на основе П]. Варианты гарантии [И]: deny `Edit(./CLAUDE.md)`, `Edit(./.claude/**)` в managed settings (агент не перепишет `/etc`); ConfigChange-хук (кроме policy_settings); CODEOWNERS и branch protection «require review from code owners» для `CLAUDE.md`, `AGENTS.md`, `.claude/**`, `.github/**`. Для соло смысл последнего варианта — только отдельный PR или коммит, потому что ревьюер всё равно вы. Рабочий вариант для соло: агент предлагает diff инструкций, человек принимает. Это договорённость, подкреплённая deny.

Auto memory — особый случай: туда агент пишет сам и по замыслу, и файлы лежат вне репозитория (раздел 8). Если нужно, чтобы правила процесса менял только человек, auto memory либо выключают (`autoMemoryEnabled`), либо периодически просматривают через `/memory` [И].

### Ревью изменений инструкций отдельно от кода [И]

Изменения `CLAUDE.md`, скиллов и хуков идут отдельными коммитами или PR с conventional-префиксом (например, `chore(agent):`). Их можно откатить отдельно и сопоставить с изменением поведения через evals (раздел 6) и историю (раздел 8).

### Таблица механизмов

| Механизм | Вид | Гарантия или договорённость | Данные |
|---|---|---|---|
| Шаг Compound (Every) | ритуал + артефакты | договорённость | данных нет (только качественные утверждения) |
| Ошибка → регрессионный тест | тест | гарантия | данных нет |
| Ошибка → eval-кейс | eval | измерение | вендорская методика, без цифр по эффекту |
| Ошибка → строка CLAUDE.md | инструкция | договорённость | данных нет |
| Auto memory | память агента | договорённость; пишет агент | данных нет (опубликованных измерений нет) |
| Protected paths `.claude`/`.git` | встроенная защита | гарантия подтверждения, кроме bypass | данных нет |
| Managed deny на правку инструкций | запрет в правах | гарантия от агента | данных нет |
| ConfigChange-хук | хук | гарантия, кроме policy_settings | данных нет |
| Managed CLAUDE.md | конфигурация | гарантия загрузки, исполнение — договорённость | данных нет |
| CODEOWNERS + branch protection | CI/хостинг | гарантия отдельного ревью (для соло — формально) | данных нет |
| Отдельные коммиты для инструкций | конвенция | договорённость (commitlint может дать гарантию формата) | данных нет |

### Пропорциональность

Для малого проекта достаточно: каждая повторившаяся ошибка поднимается как можно выше по лестнице, обычно до теста или хука; правки инструкций идут отдельными коммитами; правка `.claude/**` и CLAUDE.md запрещена через managed deny, если агент работает автономно. Полный набор compound engineering (26 агентов, библиотека `docs/solutions/`) и CODEOWNERS-ревью окупаются в команде и на длинной истории проекта [И].

### Что уточнить позже

Включать ли auto memory. Существует ли ещё «#»-шорткат быстрого добавления в память (раздел «Сомнительное»). Где хостится репозиторий и доступны ли CODEOWNERS/rulesets (первоисточник GitHub не проверен). Нужно найти публичные пост-мортемы с измерением «до/после» для правок правил.

---

## 6. Проверка самого процесса: как измерить, что инструкция или скилл действительно меняют поведение агента (evals до и после), периодический аудит процесса

### Методика Anthropic

«Demystifying evals for AI agents» (09.01.2026) [П] (вендор) ([Anthropic Engineering](https://www.anthropic.com/engineering/demystifying-evals-for-ai-agents)) задаёт словарь и правила:
- **Термины:** task, trial, grader, transcript, outcome.
- **Три типа грейдеров:** code-based (быстрые, дешёвые, воспроизводимые, но хрупкие); model-based (гибкие, но недетерминированные и требуют калибровки); human (золотой стандарт, дорогие).
- **pass@k и pass^k:** pass@k — хотя бы один успех из k, pass^k — успешны все k. «By k=10, pass@k approaches 100% while pass^k falls to 0%» (это иллюстрация, а не измерение). Для процесса, который должен работать надёжно каждый раз, важнее pass^k [И].
- **Старт:** «Begin with 20-50 simple tasks drawn from real failures».
- **Capability evals** (низкий pass rate) отличаются от **regression evals** (~100%).
- **Чтение транскриптов:** «When a task fails, the transcript tells you whether the agent made a genuine mistake or whether your graders rejected a valid solution».
- **Окружение:** агент в eval должен работать «roughly the same as the agent used in production», в изолированном окружении.

В мультиагентной работе Anthropic оценку начинали примерно с 20 запросов, потому что на раннем этапе эффекты большие, и использовали LLM-judge плюс людей [П] (вендор) ([multi-agent](https://www.anthropic.com/engineering/multi-agent-research-system)).

### Инструмент: `claude plugin eval`

`claude plugin eval` требует v2.1.269+ [П] (вендор) ([plugin evals](https://code.claude.com/docs/en/plugin-evals.md)). Кейс — это промпт плюс грейдеры. Типов грейдеров шесть: `regex`, `tool_used`, `tool_order`, `file_exists` (бесплатные, работают по транскрипту и файлам), `llm` и `baseline` (судья-модель). «One run of a non-deterministic agent tells you little, so each case runs three times by default». Есть baseline-плечо без плагина: «A high score on its own doesn't tell you the plugin helped, because Claude might do as well without it», поэтому вывод содержит колонки WITH, W/OUT и Δ. Порог `--threshold` по умолчанию 1.0; возможен гейтинг в CI; `claude plugin eval init` генерирует кейсы; моки MCP можно записать для повторяемости в CI. Совет из документации: если `tool_used: Skill` проходит, а Δ отрицательная, «suspect the judge before the plugin». Каждый прогон — реальные вызовы модели за ваш счёт.

### Инструмент: skill-creator

Обновление от 03.03.2026 [П] (вендор) ([Claude blog](https://claude.com/blog/improving-skill-creator-test-measure-and-refine-agent-skills); [plugin evals](https://code.claude.com/docs/en/plugin-evals.md)): режимы Eval, Benchmark (pass rate, время, токены), Comparator (слепое A/B: версия против версии или скилл против отсутствия скилла) и оптимизация description для срабатывания — «improved triggering on 5 out of 6 public skills». Прогоны идут параллельно в чистых контекстах. Формат `evals/evals.json` **несовместим** с `claude plugin eval`.

### Наблюдение в реальной работе

OTel-события `claude_code.skill_activated`, `claude_code.tool_decision` и `claude_code.tool_result` позволяют считать, как часто скиллы и хуки фактически срабатывают в реальной работе [П] (вендор) ([monitoring](https://code.claude.com/docs/en/monitoring-usage)).

### Варианты схемы для соло [И]

Заметки предлагают такой минимум: 10–20 кейсов из реальных ошибок агента в этом проекте; на каждый — код-грейдер (тест прошёл, файл не тронут, команда не вызвана); 3 прогона; сравнение «с правилом / без правила» (git-ветка с изменением CLAUDE.md или `--ablation`). Изменение инструкции принимается, если Δ больше шума между прогонами. Это и есть «слепая» оценка: грейдер — код, а не автор правила. Число 10–20 меньше вендорских 20–50: это компромисс для малого проекта, а не рекомендация вендора.

promptfoo, Inspect AI (UK AISI) и Braintrust — универсальные eval-фреймворки. Для одного разработчика с Claude Code ближе к делу `claude plugin eval` и skill-creator, потому что они знают про скиллы и инструменты. Первоисточники этих трёх фреймворков не открыты, поэтому уровень — [И].

**Периодический аудит процесса** [И] (раз в N недель или при смене модели): прогнать regression-евалы; по OTel или транскриптам посмотреть, как часто срабатывают хуки (хук, который не срабатывает никогда, либо не нужен, либо не загружен); удалить устаревшие правила из CLAUDE.md, удерживая его в пределах 200 строк.

### Таблица механизмов

| Механизм | Вид | Гарантия или договорённость | Данные |
|---|---|---|---|
| Regression evals с код-грейдерами в CI | eval + CI | гарантия, что регрессия будет обнаружена (в пределах шума и покрытия) [И] | данных нет |
| `claude plugin eval` (WITH / W/OUT / Δ, 3 прогона) | инструмент | измерение эффекта относительно baseline | вендорская методика; независимых данных нет |
| skill-creator Benchmark / Comparator | инструмент | измерение, слепое A/B | [П] (вендор) 5/6 по срабатыванию |
| LLM-judge | грейдер | недетерминирован, требует калибровки | данных нет |
| Чтение транскриптов человеком | ручная проверка | договорённость | данных нет |
| OTel-счётчики срабатываний | телеметрия | объективное наблюдение, не блок | данных нет |
| Периодический аудит | ритуал | договорённость | данных нет |

Вендорские цифры есть (5/6 по срабатыванию, 90.2% для мультиагента), независимых не найдено. Публичных данных «сколько процентов поведения меняет строка в CLAUDE.md» нет.

### Пропорциональность

Для одного малого сервиса достаточно нескольких регрессионных кейсов на самые дорогие ошибки, с код-грейдерами и сравнением с baseline при каждой значимой правке инструкций. Полноценные eval-сьюты, LLM-judge с калибровкой и OTel-стек окупаются, когда скилл или плагин переиспользуется много раз или на нескольких проектах [И]. Стоимость прогонов (реальные вызовы модели, ×3 на кейс, ×2 с baseline) — прямой ограничитель масштаба.

### Что уточнить позже

Бюджет на прогоны evals. Будут ли инструкции оформлены как плагин (тогда `claude plugin eval`) или как скиллы через skill-creator: форматы несовместимы, выбирать придётся один. Как отделять шум от эффекта при 3 прогонах. Первоисточники promptfoo, Inspect AI и Braintrust.

---

## 7. Инструменты для создания процессов с помощью ИИ: генераторы и линтеры скиллов, eval-фреймворки для скиллов и плагинов, официальные гайды; насколько они работают

### Два класса инструментов

**Валидаторы структуры** проверяют синтаксис и схему, но не поведение:
- `claude plugin validate` проверяет файлы плагина на синтаксис и схему «rather than its behavior» [П] (вендор) ([plugin evals](https://code.claude.com/docs/en/plugin-evals.md)). По поисковому пересказу, с v2.1.77 он проверяет frontmatter skills/agents/commands и `hooks/hooks.json` (ошибки разбора YAML, нарушения схемы) [В] ([поисковый пересказ; claudeissues #35138](https://claudeissues.com/issue/35138-docs-plugin-validation-docs-omit-frontmatter-and-hooks-hooks-json-coverage)).
- Сам Claude Code при старте предупреждает о неиспользуемых правилах (`Write(path)` и т.п.) и о `Bash(command:…)`. Это встроенная «линтовка» permissions [П] (вендор) ([permissions](https://code.claude.com/docs/en/permissions)).
- **skills-ref** (спецификация Agent Skills): команды `validate`, `read-properties`, `to-prompt`. Предупреждение авторов: «This library is intended for demonstration purposes only. It is not meant to be used in production» [П] ([agentskills/skills-ref](https://github.com/agentskills/agentskills/tree/main/skills-ref)).
- **agnix** (agent-sh): линтер и LSP для CLAUDE.md, AGENTS.md, SKILL.md, hooks и MCP-конфигов. Заявлено ~400+ правил (в одном описании «442 rules») для Claude Code, Codex, OpenCode, Cursor и Copilot, автофиксы, GitHub Action `agent-sh/agnix@v0`. Независимой оценки качества правил нет [В] ([agnix GitHub (форк/зеркало)](https://github.com/shawilly/agnix); [agnix wiki](https://github.com/agent-sh/agnix/wiki)).

**Поведенческие инструменты** измеряют эффект, но стоят токенов:
- **skill-creator:** Create, Eval, Improve, Benchmark, слепой comparator, оптимизация description (5 из 6) [П] (вендор) ([Claude blog 03.03.2026](https://claude.com/blog/improving-skill-creator-test-measure-and-refine-agent-skills)).
- **`claude plugin eval`** — см. раздел 6 [П] (вендор) ([plugin evals](https://code.claude.com/docs/en/plugin-evals.md)).

**Справка и диагностика:**
- Встроенный агент `claude-code-guide` отвечает на вопросы о хуках, настройках и `claude plugin eval`. Он виден в списке типов агентов сессии и задокументирован в описании агента [П] (вендор). Это справочник по текущей документации, а не валидатор [И].
- Хуки проверяются через `/hooks` (источник каждого хука) и `--debug-file` [П] (вендор) ([hooks](https://code.claude.com/docs/en/hooks)).

Не проверены в этом прогоне [И]: hookify (плагин Anthropic, генерирующий хуки из формулировки на естественном языке) и plugin-dev toolkit.

### Насколько они работают

Ни один инструмент не гарантирует, что инструкция «хорошая». Валидатор подтверждает, что файл загрузится. Eval подтверждает, что на выбранных кейсах поведение меняется по сравнению с baseline. Данных об эффективности линтеров инструкций нет, у правил agnix нет независимой оценки, skills-ref прямо назван демонстрационным. Единственная вендорская цифра о поведенческом эффекте — улучшение срабатывания на 5 из 6 публичных скиллов после оптимизации description.

Отдельный риск [И]: агент, который пишет собственные ограждения, может сделать fail-open хук (exit 1) или паттерн, который обходится. Поэтому тест на обход (раздел 2) обязателен, а автор правила не должен быть его оценщиком. Это прямо отвечает на слабость из блока 3 брифа «плохо делает процессы для самого себя».

### Конвейер, предложенный в заметках [И]

Агент пишет скилл или хук через skill-creator либо по документации (claude-code-guide) → `claude plugin validate` или agnix в pre-commit/CI (гарантия синтаксиса) → unit-тест хук-скрипта на записанном payload (раздел 2) → для скиллов 3–5 eval-кейсов с baseline. Человек ревьюит diff инструкций.

### Таблица механизмов

| Механизм | Вид | Гарантия или договорённость | Данные |
|---|---|---|---|
| `claude plugin validate` | валидатор | гарантия синтаксиса и схемы (в CI) | данных нет |
| Предупреждения Claude Code о правилах | встроенная проверка | гарантия показа предупреждения | данных нет |
| skills-ref validate | валидатор | гарантия схемы; «demonstration purposes only» | данных нет |
| agnix | линтер + LSP + Action | гарантия соответствия своим правилам (в CI) | данных нет; независимой оценки правил нет |
| skill-creator (Eval/Benchmark/Comparator) | eval-инструмент | измерение | [П] (вендор) 5/6 по срабатыванию |
| `claude plugin eval` | eval-инструмент | измерение относительно baseline; гейтинг CI по порогу | вендорская методика |
| claude-code-guide | субагент-справочник | договорённость (ответ модели) | данных нет |
| `/hooks`, `--debug-file` | диагностика | ручная проверка загрузки | данных нет |
| hookify, plugin-dev | генераторы | не проверено | — |

### Пропорциональность

Для малого проекта достаточно валидатора в pre-commit/CI (дёшево), unit-тестов хуков и ручного ревью diff инструкций. Поведенческие евалы на каждый скилл окупаются, если скиллы переиспользуются между проектами [И]. agnix стоит рассматривать как источник подсказок, а не как авторитетный стандарт, пока нет независимой оценки его правил.

### Что уточнить позже

Проверить первоисточники hookify, plugin-dev и официальный репозиторий agnix (agent-sh/agnix, пока открыт только через зеркало и wiki); найти cclint (в поиске не нашёлся); сверить changelog для v2.1.77. Решить, нужна ли кроссагентная переносимость инструкций (AGENTS.md), от этого зависит ценность agnix.

---

## 8. История работы: коммиты как артефакт, журнал работы с ИИ, учёт времени

### Источники истории

**Git** — главный долговременный артефакт [И]. Атомарные conventional-коммиты, «коммит на задачу» с ID задачи и trailer Co-Authored-By для ИИ связывают историю с планом и атрибуцией.

**Телеметрия Claude Code.** OTel включается через `CLAUDE_CODE_ENABLE_TELEMETRY=1` [П] (вендор) ([monitoring](https://code.claude.com/docs/en/monitoring-usage)). Метрики: `claude_code.session.count`, `lines_of_code.count`, `pull_request.count`, `commit.count`, `cost.usage`, `token.usage`, `code_edit_tool.decision`, `active_time.total` («excluding idle time», атрибут `type: user|cli`). События: `user_prompt`, `tool_result`, `tool_decision`, `api_request`, `skill_activated`, хук-события и другие. Промпты и детали инструментов по умолчанию редактируются (`OTEL_LOG_USER_PROMPTS`, `OTEL_LOG_TOOL_DETAILS`, `OTEL_LOG_TOOL_CONTENT`).

**Транскрипты.** Хукам транскрипт сессии доступен через `transcript_path` (JSONL) [П] ([hooks](https://code.claude.com/docs/en/hooks)). ccusage анализирует токены и стоимость по локальным данным (`~/.claude/projects` JSONL, `~/.config/claude`): отчёты daily/weekly/monthly/session и 5-часовые blocks, JSON-экспорт, поддержка Codex, OpenCode, Amp и других [П] ([ccusage GitHub](https://github.com/ryoppippi/ccusage)). Auto memory хранится в `~/.claude/projects/<project>/memory/` — в той же директории, что и транскрипты, вне VCS проекта [П] (вендор) ([memory](https://code.claude.com/docs/en/memory)).

**Точки автоматической записи журнала:** хуки `SessionEnd`, `Stop`, `TaskCompleted`. У SessionEnd бюджет 1.5 с, поэтому там возможны только быстрые операции [П] (вендор) ([hooks](https://code.claude.com/docs/en/hooks)).

### Варианты [И]

| Вариант | Что даёт | Вид |
|---|---|---|
| (а) conventional commits + атомарные коммиты + commitlint в commit-msg/CI | машиночитаемая история | формат — гарантия в CI; атомарность — договорённость |
| (б) «коммит на задачу» с ID задачи | связь истории с планом и трекингом | договорённость (формат ID можно проверять в CI) |
| (в) журнал `docs/worklog/YYYY-MM-DD.md`, который агент дописывает по Stop/SessionEnd-хуку | человекочитаемый журнал | запуск по хуку гарантирован; содержание пишет модель, и это договорённость. Вручную — полностью договорённость |
| (г) OTel в локальный collector или просто ccusage | объективное время и стоимость без дисциплины | гарантия сбора (при включённой телеметрии) |
| (д) WakaTime | учёт времени в редакторе | не видит агентную работу в терминале (не проверено) |

**Оценка скорости без выдуманных сроков** [И]. `active_time.total` и число коммитов или задач по conventional-типам за неделю дают собственную базовую линию. Оценки новых задач калибруются по своим завершённым задачам. Для малого проекта хватает git log и ccusage; OTel-стек окупается, когда людей или проектов несколько. Внешних калиброванных данных для соло нет: данные бывают только свои.

**Транскрипты как сырьё** [И] для пост-мортемов (раздел 5) и для eval-кейсов с `context.history_file` в `claude plugin eval` (раздел 6). Нужные транскрипты стоит копировать в проект или архив, потому что локальная директория может очищаться (срок хранения не проверен, см. «Сомнительное»).

### Таблица механизмов

| Механизм | Вид | Гарантия или договорённость | Данные |
|---|---|---|---|
| Conventional commits + commitlint в CI | CI | гарантия формата | данных нет (о связи стиля коммитов с качеством AI-работы) |
| Атомарность коммитов | конвенция | договорённость | данных нет |
| Trailer Co-Authored-By | конвенция / настройка агента | договорённость (проверяема в CI) [И] | данных нет |
| Журнал по Stop/SessionEnd-хуку | хук + артефакт | запуск гарантирован, содержание — договорённость | данных нет |
| OTel-метрики Claude Code | телеметрия | гарантия сбора при включении | данных нет |
| ccusage по локальным JSONL | инструмент | объективный учёт, пока транскрипты не удалены | данных нет |
| Архивирование транскриптов | скрипт / ритуал | договорённость, если вручную | данных нет |
| WakaTime | инструмент | не видит работу агента в терминале (не проверено) | данных нет |

### Пропорциональность

Для малого проекта достаточно conventional commits с проверкой в CI, ID задачи в сообщении, ccusage и при желании короткого журнала по хуку. Полноценный OTel-стек с collector и дашбордами окупается при нескольких людях или проектах [И].

### Что уточнить позже

Срок хранения транскриптов (`cleanupPeriodDays`, не проверен). Политика атрибуции в репозитории (связано с разделом брифа об атрибуции). Нужен ли журнал для человека или достаточно git и метрик. Где хранить архив транскриптов, учитывая, что в них могут быть секреты и промпты.

---

## Пробелы

- **Нет количественных данных об эффективности ни по одной из восьми задач.** Не найдено измерений «частота нарушения инструкций CLAUDE.md против срабатывания хуков», доли обходов deny/хуков в реальных сессиях, влияния воспроизводимости на агентную разработку, пользы субагентов в coding-задачах (вне вендорских research-задач), эффекта compound engineering и auto memory, размера эффекта одной строки CLAUDE.md, эффективности линтеров инструкций и связи стиля коммитов с качеством AI-работы.
- **Нет независимых (не вендорских) данных** о субагентах и evals: все цифры (90.2%, ~15×, 5/6) получены от Anthropic на её собственных задачах.
- **Нет публичных пост-мортемов с измерением «до/после»** для изменений правил агента.
- **Нет официального инструмента для unit-тестирования хуков** на записанных payload'ах; сторонние (`garyd203/hokum`) не проверены.
- **Не опубликован полный список Bash-команд**, которые Claude Code распознаёт как файловые для Read/Edit deny.
- **Первоисточники не открыты:** pre-commit, lefthook (страница вернула 404), husky; поведение `lefthook install` при занятом `core.hooksPath`; документация Cursor и Gemini CLI hooks (только форум и поисковик); devcontainers, Nix, `gh attestation verify`, SLSA levels; GitHub CODEOWNERS/rulesets; promptfoo, Inspect AI, Braintrust; hookify, plugin-dev, cclint (не найден), официальный репозиторий agent-sh/agnix; conventionalcommits.org, WakaTime docs.

## Сомнительное / не проверено

- Срок хранения транскриптов `~/.claude/projects` по умолчанию «30 дней» через `cleanupPeriodDays` — по памяти, в этом прогоне не проверено; README ccusage этого не упоминает.
- «#»-шорткат для быстрого добавления в память Claude Code — на текущей странице memory (2026-10-03) не найден; был ли он удалён и когда — не проверено.
- agnix: число правил (442 / «400+») взято из описаний в поиске, а не из репозитория; репозиторий открыт по зеркалу shawilly/agnix, а не agent-sh/agnix.
- То, что `claude plugin validate` с v2.1.77 проверяет frontmatter и hooks.json, взято из поискового пересказа; первоисточник (changelog) не открыт.
- То, что хуки Codex stable с v0.124.0 (23.04.2026), взято из стороннего блога (D. Vaughan), а не из release notes OpenAI.
- Cursor: «Cursor CLI 2026.08.11 на Windows не запускает user-level hooks» — форумный отчёт, вендор не подтвердил.
- Утверждение Cognition (06.2025), что Claude Code «never does work in parallel with the subtask agent», устарело: текущая документация описывает фоновые субагенты и до 20 одновременных.
- Цифры «90.2%» и «15×» получены на внутреннем eval Anthropic на research-задачах; переносить их на coding нельзя, и источник сам это оговаривает.
- «Хуки не копируются при git clone» — пересказ страницы githooks моделью-извлекателем. Факт общеизвестен, но точная цитата не сверена.
- Всё, что в этой части помечено [И], включая процедуру тестирования harness, лестницу «во что превращать ошибку», схему evals для соло (10–20 кейсов, 3 прогона), конвейер инструментов и оценки «окупается на малом / только на большом», — интерпретация автора заметок, а не проверенный факт.
