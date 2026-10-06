# Блок 4 (05d): механизмы — детерминированные guardrails, тестирование harness, воспроизводимость, субагенты, обучение процесса, оценка процесса, инструменты построения процессов, история работы

Контекст: один разработчик + AI-агент, новый небольшой backend-сервис; человек владеет результатом. Документация Claude Code проверена 2026-10-03 (code.claude.com/docs/en/...).
Метки: [П] сверено с первоисточником (открыт мной), [В] вторичный источник / пересказ поисковика, [И] моя интерпретация. (вендор) — источник производителя инструмента. (старше 2025) — источник до 2025 года.
Терминология: **гарантия** = срабатывает независимо от решения модели (исполняется harness/ОС/CI); **договорённость** = зависит от того, прочитает ли и выполнит ли модель (или человек) правило.

---

## 1. Детерминированные guardrails: hooks, CI, pre-commit, deny-правила, managed settings

### Takeaway
Инструкции в CLAUDE.md — это договорённость («context, not enforced configuration»); гарантию дают только permission-правила, PreToolUse-хуки (exit 2 / JSON deny), sandbox и CI с branch protection. У каждого слоя есть документированная «тихая дыра»: exit 1 у хука не блокирует, таймаут PreToolUse не блокирует, git-хуки не ставятся при clone и обходятся `--no-verify`, — поэтому последним рубежом должен быть CI, который агент не может отключить локально.

### Cited Findings

**Что гарантия, а что договорённость (официальная позиция)**
- [П] (вендор) «Claude treats them [CLAUDE.md и auto memory] as context, not enforced configuration. To block an action regardless of what Claude decides, use a PreToolUse hook instead.» Также: «target under 200 lines per CLAUDE.md file. Longer files consume more context and reduce adherence.» — [Claude Code: memory](https://code.claude.com/docs/en/memory)
- [П] (вендор) «Permission rules are enforced by Claude Code, not by the model. Instructions in your prompt or CLAUDE.md shape what Claude tries to do, but they don't change what Claude Code allows.» — [Claude Code: permissions](https://code.claude.com/docs/en/permissions)
- [П] (вендор) В доке memory есть таблица «settings для технического enforcement, CLAUDE.md — для поведенческих указаний»: блок инструментов/путей → `permissions.deny`; sandbox → `sandbox.enabled`; стиль кода и поведение → CLAUDE.md. — [memory](https://code.claude.com/docs/en/memory)

**Claude Code hooks — события и блокировка (проверено 2026-10-03)**
- [П] (вендор) Полный список событий на дату проверки: SessionStart, Setup, UserPromptSubmit, UserPromptExpansion, PreToolUse, PermissionRequest, PermissionDenied, PostToolUse, PostToolUseFailure, PostToolBatch, Notification, MessageDisplay, SubagentStart, SubagentStop, TaskCreated, TaskCompleted, Stop, StopFailure, TeammateIdle, InstructionsLoaded, ConfigChange, CwdChanged, DirectoryAdded, FileChanged, WorktreeCreate, WorktreeRemove, PreCompact, PostCompact, PreModelSwitch, PostModelSwitch, Elicitation, ElicitationResult, SessionEnd. — [hooks reference](https://code.claude.com/docs/en/hooks)
- [П] (вендор) Exit 2 блокирует: PreToolUse (блок вызова), UserPromptSubmit, UserPromptExpansion, Stop/SubagentStop (не дать остановиться — продолжить работу), TeammateIdle, TaskCreated, TaskCompleted, ConfigChange (кроме policy_settings), PreCompact, PreModelSwitch, Elicitation, ElicitationResult; WorktreeCreate/Remove — блокирует любой ненулевой код. — [hooks](https://code.claude.com/docs/en/hooks)
- [П] (вендор) Не блокируют: PostToolUse/PostToolUseFailure/PostToolBatch (stderr показывается Claude, «tool already ran»), PermissionRequest (exit 2 не учитывается — нужен JSON `decision`), PermissionDenied, Notification, SessionStart, SessionEnd и др. — [hooks](https://code.claude.com/docs/en/hooks)
- [П] (вендор) Ключевая ловушка: «Without valid JSON on stdout, Claude Code treats exit code 1 as a non-blocking error and proceeds with the action, even though 1 is the conventional Unix failure code.» Любой иной код (кроме 2) → «non-blocking error … the action proceeds», в транскрипте только notice `Failed with non-blocking status code:`. — [hooks](https://code.claude.com/docs/en/hooks)
- [В] Это регулярный источник багрепортов: issue #44707 «[Docs] Hook exit codes: exit(1) silently non-blocking — needs prominent warning». — [claudeissues.com #44707](https://claudeissues.com/issue/44707-docs-hook-exit-codes-exit-1-silently-non-blocking-needs-prominent-warning)
- [П] (вендор) Таймауты: command/http/mcp_tool — 600 с, prompt — 30 с, agent — 60 с; UserPromptSubmit — 30 с; SessionEnd — общий бюджет 1.5 с. «On PreToolUse, timed-out hooks don't block» (а на PreModelSwitch — блокируют). Т.е. зависший/медленный guard-хук = fail-open. — [hooks](https://code.claude.com/docs/en/hooks)
- [П] (вендор) Типы хуков: `command`, `http`, `mcp_tool`, `prompt` (одноходовая оценка моделью), `agent` (субагент-верификатор). Поле `if` с синтаксисом permission-правил (`"if": "Bash(git *)"`); «When Claude's command is ambiguous, hook runs anyway». — [hooks](https://code.claude.com/docs/en/hooks)
- [П] (вендор) PreToolUse JSON: `hookSpecificOutput.permissionDecision: allow|deny|ask|defer`, `permissionDecisionReason`, `updatedInput`, `additionalContext`. Top-level `decision: "block"` — для UserPromptSubmit, PostToolUse, Stop, SubagentStop, ConfigChange, PreCompact, TaskCreated и др. — [hooks](https://code.claude.com/docs/en/hooks)
- [П] (вендор) Взаимодействие с permissions: «PreToolUse hook decisions don't bypass permission rules … a matching deny rule blocks the call, and a matching ask rule still prompts even when the hook returned "allow"». Обратное: «A hook that exits with code 2 stops the tool call before permission rules are evaluated, so the block applies even when an allow rule would otherwise let the call proceed.» — [permissions](https://code.claude.com/docs/en/permissions)
- [П] (вендор) Хуки могут быть выключены: `"disableAllHooks": true`; при этом «A "disableAllHooks": false in project settings overrides true in user settings»; managed-хуки не отключаются из немanaged-настроек; `allowManagedHooksOnly` блокирует user/project/local/plugin-хуки. Хуки сливаются между уровнями, а не заменяют друг друга. — [hooks](https://code.claude.com/docs/en/hooks)
- [П] (вендор) Хуки из `.claude/settings.json` грузятся только из `.claude/` текущей рабочей директории «with no parent-directory fallback» — запуск `claude` из поддиректории = проектные хуки не загружены. — [permissions](https://code.claude.com/docs/en/permissions)
- [П] (вендор) Frontmatter-хуки проектного субагента не работают, пока не принят workspace trust dialog; в `claude -p`/SDK без доверия — «Not used, and no dialog is offered». — [permissions](https://code.claude.com/docs/en/permissions); [sub-agents](https://code.claude.com/docs/en/sub-agents)
- [П] (вендор) Проверка, что хуки реально загружены: `/hooks` — «read-only browser for your configured hooks … labels each hook with where it comes from»; лог: `claude --debug-file <path>`. — [hooks](https://code.claude.com/docs/en/hooks)
- [П] (вендор) Cloud-сессии не читают `~/.claude/settings.json` — переносятся только managed и project settings. → guardrail, который должен работать везде, кладётся в проектный `.claude/settings.json` (под VCS), а не в user-scope. — [hooks](https://code.claude.com/docs/en/hooks)

**Permission deny rules и managed settings**
- [П] (вендор) Приоритет deny > ask > allow; «An allow rule can't carve an exception out of a deny rule»; deny с любого уровня бьёт allow с любого уровня; managed deny не перекрывается даже `--allowedTools`. — [permissions](https://code.claude.com/docs/en/permissions)
- [П] (вендор) Голое имя инструмента в deny (`Bash`) удаляет инструмент из контекста; скоуп-правило (`Bash(rm *)`) оставляет инструмент и блокирует совпадающие вызовы. — [permissions](https://code.claude.com/docs/en/permissions)
- [П] (вендор) `permissions.disableBypassPermissionsMode` / `disableAutoMode` = `"disable"`; работает с любого scope, «A user can set it in their own settings to lock themselves out of bypass mode». `allowManagedPermissionRulesOnly` — только managed-правила. — [permissions](https://code.claude.com/docs/en/permissions)
- [П] (вендор) Защищённые пути: в `bypassPermissions` Claude Code пропускает промпты «including for writes to protected paths such as .git and .claude» — т.е. в обычных режимах `.git`/`.claude` защищены, в bypass — нет. — [permissions](https://code.claude.com/docs/en/permissions)
- [П] (вендор) Mods (плагины-моды с `tool.check`) могут перекрыть блок PreToolUse-хука (кроме managed) и, вне managed/Team/Enterprise, даже deny-правило. — [permissions](https://code.claude.com/docs/en/permissions)

**Аналоги в других агентах**
- [П] (вендор) OpenAI Codex: события SessionStart, SessionEnd, SubagentStart, SubagentStop, PreToolUse, PermissionRequest, PostToolUse, PreCompact, PostCompact, UserPromptSubmit, Stop, Interrupt; блок — exit 2 или JSON `decision: block` / `permissionDecision: deny`; прямо: «Treat tool hooks as a useful guardrail, not a complete enforcement boundary» (не перехватываются hosted-инструменты вроде WebSearch). Не-managed хуки требуют ревью, доверие привязано к хешу хука (изменили — переутверждение); managed — через `requirements.toml`. — [Codex hooks](https://learn.chatgpt.com/docs/hooks) (редирект с developers.openai.com/codex/hooks)
- [В] Codex hooks стали stable в v0.124.0 (23.04.2026), inline-конфиг в `config.toml` (`[[hooks.PreToolUse]]`). — [D. Vaughan blog](https://codex.danielvaughan.com/2026/04/23/codex-cli-hooks-graduate-stable-v0124-mcp-observation-inline-config/)
- [В] Cursor: `beforeShellExecution`, `beforeReadFile`, `preToolUse` с ответом `permission: allow|deny|ask`, опция `failClosed`; на форуме — множественные отчёты «hooks not firing», ask игнорируется при allow-list/sandbox; совет — использовать deny как единственно надёжный. — [Cursor forum: hooks not firing](https://forum.cursor.com/t/hooks-not-firing-cannot-have-guardrails/168407); [ask ignored](https://forum.cursor.com/t/beforeshellexecution-hook-permissions-allow-ask-ignored-allow-list-takes-precedence/144244)
- [В] Gemini CLI: событие `BeforeTool` в `settings.json`, JSON через stdin/stdout. — [Gemini CLI hooks reference](https://geminicli.com/docs/hooks/reference/)

**Git-хуки, pre-commit, CI**
- [П] `--no-verify` пропускает pre-commit, commit-msg, pre-merge-commit, pre-push; `prepare-commit-msg` не пропускается. `core.hooksPath` переопределяет каталог хуков. — [git-scm githooks](https://git-scm.com/docs/githooks) (старше 2025 — справочник git, но актуален)
- [В] Хуки не копируются при `git clone` (только шаблоны при `git init`) → каждый клон требует установки (`pre-commit install`, `lefthook install`, husky через `prepare`-скрипт npm). — [githooks](https://git-scm.com/docs/githooks) (пересказ страницы)
- [И] Следствия для «тихо выключенного» guardrail: (а) свежий клон без `install` — хуков нет; (б) `core.hooksPath`, выставленный другим инструментом (husky ставит `.husky`), перекрывает `.git/hooks`, и второй менеджер хуков молча не работает; (в) агент сам может вызвать `git commit --no-verify` → нужен PreToolUse-хук/deny на `Bash(git commit --no-verify*)` (но см. п.2 — паттерн обходится вариациями) и, главное, те же проверки в CI + branch protection (required status checks), которые локально не отключить.
- [И] Для соло-разработчика branch protection на GitHub даёт гарантию только против push в main напрямую; админ (сам владелец) может её обойти, если не включено «Do not allow bypassing» — это гарантия от агента, не от себя.

### Inferences
- [И] Что выносить из инструкций в проверки (пропорционально малому проекту): формат/линт/типы (PostToolUse-хук на Edit|Write с exit 2 → фидбэк агенту + pre-commit + CI); запрет правок секретов/`.env`/lock-файлов/CI-конфигов (deny `Edit(...)`, `Read(./.env)`); запрет `git push --force`, `--no-verify` (PreToolUse); «не заканчивать, пока тесты красные» (Stop-хук с exit 2, осторожно с зацикливанием); всё остальное (архитектура, стиль именования, «объясняй решения») — остаётся договорённостью.
- [И] Правило «guard-хук обязан быть fail-closed»: скрипт с `set -euo pipefail` и trap, который любую внутреннюю ошибку превращает в exit 2 (иначе exit 1 = пропуск); короткий явный `timeout`; не полагаться на сетевые вызовы в PreToolUse.
- [И] Managed settings (`/etc/claude-code/managed-settings.json`) для соло — способ защитить guardrails от самого агента (агент правит `.claude/settings.json` в проекте, но не `/etc`); окупается уже на одном разработчике, если агент работает в auto/acceptEdits режиме. Пропорционально: deny-список и пара хуков; `allowManagedHooksOnly` — избыточно для соло.
- [И] Эффективность: данных нет (ни Anthropic, ни независимых измерений «сколько инцидентов предотвращает хук vs инструкция»). Есть только качественное утверждение вендора, что CLAUDE.md не enforced и длинные файлы «reduce adherence».

### Gaps
- Нет количественных данных о частоте нарушения инструкций CLAUDE.md vs срабатывания хуков.
- Не проверил первоисточники pre-commit/lefthook/husky (страница lefthook вернула 404); поведение `lefthook install` при занятом `core.hooksPath` — не проверено.
- Документация Cursor и Gemini CLI hooks не открыта напрямую (только форум/поисковик).

---

## 2. Тестирование самого harness (хуков и правил)

### Takeaway
Официальная дока Claude Code сама признаёт: Bash-правила матчат текст команды и «isn't a security boundary around the program» — `sh -c`, абсолютный путь, `git -C .`, Python-скрипт обходят deny. Поэтому harness надо тестировать как код: записанные из реальной сессии payload’ы → unit-тесты хук-скриптов → набор «атакующих» вариантов команд; для настоящей границы — sandbox.

### Cited Findings
- [П] (вендор) «A Bash rule matches the command text Claude writes … It doesn't match the same program invoked in a different form, so a deny or ask rule covers the invocation Claude usually produces and isn't a security boundary around the program.» Примеры: `Bash(curl *)` не ловит `/usr/bin/curl …`, `sh -c 'curl …'`; `Bash(rm *)` не ловит `/bin/rm`, `bash -c 'rm …'`; `Bash(git push *)` не ловит `git -C . push`, `git -c push.default=current push`, `git 'push' origin main`. — [permissions → Bash rule limits](https://code.claude.com/docs/en/permissions)
- [П] (вендор) «Bash permission patterns that try to constrain command arguments are fragile» — `Bash(curl http://github.com/ *)` не ловит опции перед URL, https, редиректы, переменные `URL=… && curl $URL`. Рекомендация: deny на curl/wget + WebFetch(domain:…) + sandbox network allowlist, либо PreToolUse-хук. — [permissions](https://code.claude.com/docs/en/permissions)
- [П] (вендор) Read/Edit deny применяются к встроенным файловым инструментам, к распознанным Bash-командам (`cat`, `head`, `tail`, `sed`, `tee`) и к редиректам `>`/`<`, но «don't apply to a command that reads files without naming them, such as `grep -r pattern .` … or to arbitrary subprocesses that read or write files indirectly, like a Python or Node script that opens files itself. For OS-level enforcement … enable the sandbox.» — [permissions](https://code.claude.com/docs/en/permissions)
- [И] Т.е. классический обход «deny на Edit → `sed -i`» в текущей версии для `sed` с явным путём частично закрыт (sed распознаётся), но `python -c "open('x','w')…"` / `node -e` / `perl -pi` (не в списке) — не закрыты. Список распознаваемых команд в доке дан как «such as», полный перечень не опубликован.
- [П] (вендор) Составные команды: разделители `&&`, `||`, `;`, `|`, `|&`, `&`, newline; deny/ask срабатывают на любую подкоманду, включая subshell, `$()`, тело `for`. Правило по полю `command` (`Bash(command:rm *)`) игнорируется с warning, т.к. «would be bypassable by a compound command». Путевые правила для `Write(...)`/`NotebookEdit(...)`/`Glob(...)` принимаются, но «never consults it» — надо писать `Edit(...)`/`Read(...)` (с v2.1.210 — warning при старте). — [permissions](https://code.claude.com/docs/en/permissions)
- [П] (вендор) Sandbox: «OS-level enforcement … applies only to Bash, PowerShell, and Monitor commands and their child processes»; «Use both for defense-in-depth, since sandbox restrictions still apply even if a prompt injection bypasses Claude's decision-making». — [permissions](https://code.claude.com/docs/en/permissions)
- [П] (вендор) Формат входа хука (общие поля): `session_id`, `prompt_id`, `transcript_path`, `cwd`, `permission_mode`, `hook_event_name`, (в субагенте) `agent_id`, `agent_type`; для tool-событий — `tool_name`, `tool_input`, `tool_use_id`; поле `scratchpad_dir` — с v2.1.257+. Т.е. формат меняется между версиями → payload нужно снимать с реальной сессии текущей версии, а не придумывать. — [hooks](https://code.claude.com/docs/en/hooks)
- [П] (вендор) Хуки субагентов: PreToolUse/PostToolUse срабатывают и для вызовов субагента, с `agent_id`/`agent_type` во входе. — [hooks](https://code.claude.com/docs/en/hooks)
- [П] (вендор) Нюансы парсинга вывода: stdout, начинающийся с `{` и заканчивающийся `}`, парсится как JSON; многострочный вывод из нескольких JSON без полей — как текст; вывод >10 000 символов заменяется путём к файлу + превью 2 000 символов. — [hooks](https://code.claude.com/docs/en/hooks)
- [П] (вендор) Codex прямо пишет: «Treat tool hooks as a useful guardrail, not a complete enforcement boundary.» — [Codex hooks](https://learn.chatgpt.com/docs/hooks)

### Inferences
- [И] Минимальная процедура тестирования harness (пропорционально соло-проекту, ~1–2 часа):
  1. Временный «логирующий» хук `cat > /tmp/payload-$(date +%s).json` на нужное событие → прогнать реальную сессию → сохранить payload’ы как фикстуры (`tests/hooks/fixtures/*.json`).
  2. Unit-тесты хук-скрипта: `script < fixture.json; assert exit==2 / stdout JSON` (bats, pytest, или просто shell-скрипт) — запускаются в CI. Это гарантия того, что *скрипт* корректен, но не того, что он *загружен*.
  3. Набор обходных вариантов (таблица из доки выше + `python -c`, `node -e`, `perl -pi`, `git -C`, `env X=1 git push`, абсолютные пути) — для каждого deny/хука проверить, что блокируется, или явно зафиксировать «не покрыто, держит sandbox/CI».
  4. Smoke-тест загрузки: `/hooks` + `claude -p` с промптом, который должен упереться в блок (проверка end-to-end; учитывать, что в `-p` без trust не грузятся frontmatter-хуки субагентов).
- [И] Ключевой критерий: guard, который нельзя протестировать на обход, — договорённость, а не гарантия. Для реальной границы на файлы/сеть — sandbox, а не текстовые паттерны.
- [И] Эффективность: данных нет (не найдено публикаций с измерением доли обходов в реальных сессиях).

### Gaps
- Не найден официальный инструмент Anthropic для unit-тестирования хуков с recorded payload’ами (кроме `claude plugin eval`, который тестирует поведение плагина, см. п.6–7). Не проверял сторонние (например, упомянутый в поиске `garyd203/hokum`).
- Полный список Bash-команд, которые Claude Code распознаёт как файловые для Read/Edit deny, не опубликован.

---

## 3. Воспроизводимость окружения и процесса

### Takeaway
Гарантию воспроизводимости дают lockfile’ы с контрольными суммами (mise.lock, языковые lock’и, Nix flake.lock) и проверка в CI на чистой машине; «онбординг-документ» — договорённость. Отдельно надо проверять, что хуки реально активны на чистом клоне (git-хуки не ставятся сами; Claude-хуки зависят от cwd и trust).

### Cited Findings
- [П] (вендор) mise.lock записывает конкретные версии, по платформам — checksums, URL и данные верификации; при установке «validates downloaded artifacts against recorded checksums» для поддерживаемых бэкендов; фиксирует результаты SLSA, Cosign, Minisign, GitHub attestations, но «A provenance field alone is not proof that the bytes were verified»; принудительная перепроверка — `locked_verify_provenance = true`. Режим `lockfile_mode = "generate"` экспериментальный, дефолт `merge` «pending … before mise 2026.12.0». — [mise.lock](https://mise.jdx.dev/dev-tools/mise-lock.html)
- [П] (вендор) Claude Code: проектные хуки/настройки в `.claude/settings.json` (под VCS, «Shareable»), `settings.local.json` — gitignored; cloud-сессии не видят user-настроек. — [hooks](https://code.claude.com/docs/en/hooks)
- [П] (вендор) Без принятого trust: `permissions.allow` из `.claude/settings.json` не используются, в `claude -p` печатается warning «this workspace has not been trusted»; frontmatter-хуки проектных субагентов не используются. — [permissions](https://code.claude.com/docs/en/permissions)
- [В] Git-хуки не переносятся клоном → требуется шаг установки. — [githooks](https://git-scm.com/docs/githooks)

### Inferences
- [И] Опции по нарастанию стоимости: (1) `mise.toml` + `mise.lock` + языковой lockfile + `make bootstrap`/`mise run setup`, который ставит git-хуки — дёшево, подходит для малого проекта; (2) devcontainer — даёт идентичное окружение агенту и человеку и естественную границу для sandbox/bypass-режимов; (3) Nix flake — максимальная воспроизводимость, но высокая цена входа, окупается в основном на больших/многоязычных проектах.
- [И] Проверка «хуки реально работают»: CI-джоб «clean clone» (`git clone` → bootstrap → `git config core.hooksPath`/наличие `.git/hooks/pre-commit` → попытка коммита с заведомо плохим файлом должна упасть). Для Claude-хуков — тест из п.2 (fixtures) + `/hooks` вручную при онбординге.
- [И] Ручной `sha256sum -c` для скачанных бинарников — гарантия, если сумма зафиксирована в репозитории до скачивания; SLSA/sigstore (cosign verify, `gh attestation verify`) — «лёгкий уровень» для соло имеет смысл только для инструментов, скачиваемых вне пакетного менеджера.
- [И] Эффективность: данных нет (по влиянию на агентную разработку конкретно).

### Gaps
- Не открыл первоисточники devcontainers, Nix, `gh attestation verify`, SLSA levels — утверждения о них выше помечены [И].

---

## 4. Субагенты: когда оправданы, брифинг, ограничения, слепая оценка

### Takeaway
Данные Anthropic: мультиагент даёт +90.2% на внутренних research-задачах ценой ~15× токенов к чату, при этом сам вендор пишет, что большинство coding-задач плохо параллелятся; Cognition — «не стройте мультиагентов» для записи кода. Для соло-backend субагенты оправданы как изолированный read-only поиск/ревью/верификация с ограничением инструментов через `tools`/`disallowedTools` (гарантия), а не как параллельные писатели кода.

### Cited Findings
- [П] (вендор) Anthropic multi-agent research (13.06.2025): агенты ~«4× more tokens than chat», мультиагент ~«15× more tokens than chats»; «90.2% performance improvement» (Opus 4 lead + Sonnet 4 субагенты vs одиночный Opus 4, внутренний research-eval); токены объясняют ~80% дисперсии на browsing-eval. — [Anthropic Engineering](https://www.anthropic.com/engineering/multi-agent-research-system)
- [П] (вендор) Там же: «most coding tasks involve fewer truly parallelizable tasks than research, and LLM agents are not yet great at coordinating and delegating to other agents in real time.» Бриф субагенту должен содержать «an objective, an output format, guidance on the tools and sources to use, and clear task boundaries». Оценка: начинать с ~20 реальных запросов, LLM-judge по рубрике + люди (люди нашли предпочтение SEO-контента). — [Anthropic Engineering](https://www.anthropic.com/engineering/multi-agent-research-system)
- [П] Cognition, Walden Yan, 12.06.2025: принципы «Share context, and share full agent traces, not just individual messages» и «Actions carry implicit decisions, and conflicting decisions carry bad results»; пример Flappy Bird (несовместимые фон и птица от параллельных субагентов); рекомендация — single-threaded linear agent + компрессия истории. О Claude Code: «it never does work in parallel with the subtask agent, and the subtask agent is usually only tasked with answering a question, not writing any code.» — [Cognition](https://cognition.com/blog/dont-build-multi-agents) (вендор Devin; утверждение о Claude Code — на 2025, сейчас Claude Code поддерживает фоновые/параллельные субагенты, см. ниже)
- [П] (вендор) Claude Code subagents: «Use subagents when: The task produces verbose output you don't need in your main context; You want to enforce specific tool restrictions or permissions; The work is self-contained and can return a summary». Основной диалог — когда нужна итерация, общий контекст фаз, быстрая правка, важна латентность. — [sub-agents](https://code.claude.com/docs/en/sub-agents)
- [П] (вендор) Ограничение инструментов: `tools` (allowlist), `disallowedTools` (denylist, применяется первым); без `tools` наследуются все; `permissionMode` (default/acceptEdits/auto/dontAsk/bypassPermissions/plan); хуки во frontmatter; `memory: user|project|local`; вложенность до 3 уровней (`CLAUDE_CODE_MAX_SUBAGENT_SPAWN_DEPTH`), запрет спавна — убрать `Agent` из tools; лимит 20 одновременных. — [sub-agents](https://code.claude.com/docs/en/sub-agents)
- [П] (вендор) Изоляция контекста: субагент (не fork) не видит историю диалога, ранее вызванные скиллы, auto memory основного диалога; получает CLAUDE.md (если не `omitClaudeMd`), git status, preloaded skills. Fork наследует весь диалог и prompt cache. Built-in Explore/Plan — read-only, пропускают CLAUDE.md. — [sub-agents](https://code.claude.com/docs/en/sub-agents)
- [П] (вендор) Отчёты субагентов сканируются на инструкционно-подобный текст (защита от инъекций), «The scan never removes or rewords anything». — [sub-agents](https://code.claude.com/docs/en/sub-agents)
- [П] (вендор) Слепая оценка в инструментарии Anthropic: skill-creator «Comparator Agents: Enable A/B testing between two skill versions or skill versus no skill, with blind judging». — [Claude blog, 03.03.2026](https://claude.com/blog/improving-skill-creator-test-measure-and-refine-agent-skills)

### Inferences
- [И] Ограничение через `tools`/`disallowedTools`/`permissionMode` — гарантия (исполняется harness); ограничение «в промпте субагента» («не редактируй файлы») — договорённость. Ревьюер/верификатор → `tools: Read, Grep, Glob` (+ Bash только если нужен прогон тестов, тогда с PreToolUse-хуком во frontmatter).
- [И] Проверка результатов субагента: требовать формат с цитатами путей/строк и проверяемыми утверждениями; основной агент или человек перепроверяет выборочно (субагент тоже может галлюцинировать). Отчёт субагента — данные, не инструкции.
- [И] Слепая оценка: если агент (или человек) написал правило/скилл, его эффект оценивает независимый грейдер — субагент со свежим контекстом без знания, какая версия «новая» (как comparator в skill-creator), или код-грейдер. Иначе — самооценка с конфликтом интересов.
- [И] Пропорциональность: для малого сервиса — 1–3 субагента (read-only explorer, reviewer, test-runner/verifier). Параллельные «писатели» и оркестрация — окупаются на больших широких задачах (исследование, массовые миграции), не на одном сервисе; цена ×4–15 токенов.
- [И] Эффективность для coding-субагентов: данных нет (90.2% — только research-задачи вендора, не код).

### Gaps
- Нет независимых (не вендорских) измерений пользы субагентов в coding-задачах.

---

## 5. Обучение процесса: ошибка агента → изменение правил/хуков/тестов

### Takeaway
Основной описанный паттерн — «compound engineering» (Every): после каждой задачи шаг Compound записывает урок в `docs/solutions/`, CLAUDE.md, новых агентов/скиллы — но количественных данных о пользе нет. Механически надёжнее превращать повторяющуюся ошибку в тест/хук (гарантия), а не в строчку CLAUDE.md (договорённость); изменения инструкций — отдельный ревью, защита через protected paths/CODEOWNERS/managed settings.

### Cited Findings
- [П] (вендор-практик) Every, Kieran Klaassen (+ Trevin Chow): цикл «Plan → Work → Review → Compound → Repeat»; Compound: «Capture the solution… What's the reusable insight?», «Make it findable» (YAML frontmatter), «Update the system. Add new patterns into CLAUDE.md … Create new agents when warranted.» Плагин: 26 агентов, 23 команды, 13 скиллов (Claude Code, OpenCode, Codex). Количественных утверждений об эффективности на странице нет. Дата на странице не указана. — [Every: compound engineering guide](https://every.to/guides/compound-engineering)
- [П] (вендор) Claude Code: два механизма памяти — CLAUDE.md (пишет человек) и auto memory (пишет Claude, `~/.claude/projects/<project>/memory/`, грузятся первые 200 строк/25KB `MEMORY.md`); включение/выключение `autoMemoryEnabled`, просмотр `/memory`; файлы — обычный markdown, редактируемый человеком. — [memory](https://code.claude.com/docs/en/memory)
- [П] (вендор) Managed CLAUDE.md (`/etc/claude-code/CLAUDE.md` на Linux) «cannot be excluded by individual settings»; ключ `claudeMd` в managed-settings. — [memory](https://code.claude.com/docs/en/memory)
- [П] (вендор) Событие `ConfigChange` может блокировать изменения конфигурации (exit 2), кроме policy_settings; `InstructionsLoaded` — наблюдение за загрузкой инструкций (не блокирует). — [hooks](https://code.claude.com/docs/en/hooks)
- [П] (вендор) `.claude` и `.git` — protected paths (запись требует подтверждения во всех режимах, кроме bypassPermissions). — [permissions](https://code.claude.com/docs/en/permissions)
- [П] (вендор) Anthropic evals: брать задачи «from real failures» — т.е. каждая ошибка агента → кейс регрессионного eval. — [Demystifying evals](https://www.anthropic.com/engineering/demystifying-evals-for-ai-agents)

### Inferences
- [И] Лестница «во что превращать ошибку» (от гарантии к договорённости): тест в сьюте проекта → CI/pre-commit проверка → PreToolUse/PostToolUse/Stop-хук → deny-правило → eval-кейс для скилла → строка в CLAUDE.md/скилле → запись в auto memory. Выбирать самый высокий уровень, который реально выразим кодом.
- [И] Кто может менять инструкции: агент может править CLAUDE.md/`.claude/**` (защищены только промптом подтверждения; в bypass — свободно). Варианты гарантии: deny `Edit(./CLAUDE.md)`, `Edit(./.claude/**)` в managed settings (агент не перепишет `/etc`); CODEOWNERS + branch protection «require review from code owners» для `CLAUDE.md`, `AGENTS.md`, `.claude/**`, `.github/**` (для соло — смысл только как отдельный PR/коммит, ревьюер всё равно вы). Агент предлагает diff инструкций, человек принимает — договорённость, подкреплённая deny.
- [И] Ревью инструкций отдельно от кода: отдельные коммиты/PR для изменений `CLAUDE.md`/скиллов/хуков с conventional-префиксом (`chore(agent):`), чтобы можно было откатить и сопоставить с изменением поведения (см. п.6, п.8).
- [И] «#»-шорткат для добавления памяти в текущей странице memory не упоминается (там `/memory` и auto memory) — вероятно, удалён/заменён; не утверждаю.
- [И] Эффективность: данных нет (compound engineering — только качественные утверждения; auto memory — нет опубликованных измерений).

### Gaps
- Нет публичных пост-мортемов с измерением «до/после» для изменений правил агента.
- Не проверял документацию GitHub CODEOWNERS/rulesets напрямую.

---

## 6. Оценка самого процесса: меняет ли инструкция/скилл поведение агента

### Takeaway
Есть зрелые вендорские методики: 20–50 задач из реальных провалов, несколько прогонов (pass@k / pass^k), код-грейдеры где можно, LLM-judge с калибровкой, чтение транскриптов, и — главное — сравнение с baseline «без скилла/плагина» (`claude plugin eval` даёт WITH / W/OUT / Δ, по 3 прогона). Без baseline высокий score ничего не доказывает. Независимых данных о размере эффекта инструкций мало.

### Cited Findings
- [П] (вендор) «Demystifying evals for AI agents» (09.01.2026): термины task/trial/grader/transcript/outcome; три типа грейдеров (code-based — быстрые, дешёвые, воспроизводимые, но хрупкие; model-based — гибкие, но недетерминированные и требуют калибровки; human — золотой стандарт, дорогие); pass@k (хотя бы один успех из k) vs pass^k (все k успешны) — «By k=10, pass@k approaches 100% while pass^k falls to 0%» (иллюстрация); «Begin with 20-50 simple tasks drawn from real failures»; capability evals (низкий pass rate) vs regression evals (~100%); «When a task fails, the transcript tells you whether the agent made a genuine mistake or whether your graders rejected a valid solution»; агент в eval должен работать «roughly the same as the agent used in production» в изолированном окружении. — [Anthropic Engineering](https://www.anthropic.com/engineering/demystifying-evals-for-ai-agents)
- [П] (вендор) `claude plugin eval` (требует v2.1.269+): кейс = промпт + грейдеры; 6 типов грейдеров — `regex`, `tool_used`, `tool_order`, `file_exists` (бесплатные, по транскрипту/файлам), `llm`, `baseline` (судья-модель); «One run of a non-deterministic agent tells you little, so each case runs three times by default»; baseline-плечо без плагина: «A high score on its own doesn't tell you the plugin helped, because Claude might do as well without it» → колонки WITH, W/OUT, Δ; порог `--threshold` 1.0 по умолчанию; гейтинг CI; `claude plugin eval init` генерирует кейсы; запись моков MCP для повторяемости в CI; совет — если `tool_used: Skill` проходит, а Δ отрицательная, «suspect the judge before the plugin». Каждый прогон — реальные вызовы модели за ваш счёт. — [plugin evals](https://code.claude.com/docs/en/plugin-evals.md)
- [П] (вендор) skill-creator (обновление 03.03.2026): режимы Eval, Benchmark (pass rate, время, токены), Comparator (слепое A/B: версия vs версия или скилл vs без скилла), оптимизация description для триггеринга — «improved triggering on 5 out of 6 public skills»; параллельные прогоны в чистых контекстах. Формат `evals/evals.json` несовместим с `claude plugin eval`. — [Claude blog](https://claude.com/blog/improving-skill-creator-test-measure-and-refine-agent-skills); [plugin evals](https://code.claude.com/docs/en/plugin-evals.md)
- [П] (вендор) Anthropic multi-agent: оценку начинали с ~20 запросов, т.к. на раннем этапе эффекты большие; LLM-judge + люди. — [multi-agent](https://www.anthropic.com/engineering/multi-agent-research-system)
- [П] (вендор) OTel-события `claude_code.skill_activated`, `claude_code.tool_decision`, `claude_code.tool_result` — можно считать фактическую частоту срабатывания скиллов/хуков в реальной работе. — [monitoring](https://code.claude.com/docs/en/monitoring-usage)

### Inferences
- [И] Минимальная схема для соло: 10–20 кейсов из реальных ошибок агента в этом проекте; на каждый — код-грейдер (тест прошёл / файл не тронут / команда не вызвана); 3 прогона; сравнение «с правилом / без правила» (git-ветка с изменением CLAUDE.md или `--ablation`). Изменение инструкции принимается, если Δ > шума между прогонами. Это и есть «блайнд» — грейдер код, не автор правила.
- [И] promptfoo, Inspect AI (UK AISI), Braintrust — универсальные eval-фреймворки; для одного разработчика с Claude Code `claude plugin eval`/skill-creator ближе к делу (знают про скиллы/инструменты). Первоисточники этих трёх не открывал — уровень [И].
- [И] Периодический аудит процесса (раз в N недель/при смене модели): прогнать regression-евалы, просмотреть OTel/транскрипты на частоту срабатывания хуков (если хук никогда не срабатывает — либо не нужен, либо не загружен), удалить устаревшие правила из CLAUDE.md (держать <200 строк).
- [И] Пропорциональность: полноценные eval-сьюты окупаются, когда скилл/плагин переиспользуется много раз или на нескольких проектах; для одного малого сервиса — несколько регрессионных кейсов на самые дорогие ошибки.
- [И] Данные об эффективности: вендорские цифры есть (5/6 по триггерингу, 90.2% мультиагент), независимых — не нашёл.

### Gaps
- Не открыл первоисточники promptfoo / Inspect AI / Braintrust.
- Нет публичных данных «сколько процентов поведения меняет строка в CLAUDE.md».

---

## 7. Инструменты, чтобы агент сам писал хорошие инструкции, скиллы, хуки, конфиг harness

### Takeaway
Есть два класса: валидаторы структуры (`claude plugin validate`, skills-ref validate, agnix) — гарантируют только синтаксис/схему; и поведенческие (skill-creator eval/benchmark/comparator, `claude plugin eval`) — измеряют эффект, но стоят токенов. Ни один не гарантирует, что инструкция «хорошая» — только что она загружается и (при eval) что-то меняет.

### Cited Findings
- [П] (вендор) `claude plugin validate` проверяет файлы плагина на синтаксис/схему «rather than its behavior» (отсылка из доки plugin evals). — [plugin evals](https://code.claude.com/docs/en/plugin-evals.md)
- [В] С v2.1.77 `claude plugin validate` проверяет frontmatter skills/agents/commands и `hooks/hooks.json` (YAML parse errors, schema violations). — [поисковый пересказ; claudeissues #35138](https://claudeissues.com/issue/35138-docs-plugin-validation-docs-omit-frontmatter-and-hooks-hooks-json-coverage)
- [П] (вендор) Claude Code при старте предупреждает о неиспользуемых правилах (`Write(path)` и т.п.), о `Bash(command:…)` — встроенная «линтовка» permissions. — [permissions](https://code.claude.com/docs/en/permissions)
- [П] skills-ref (Agent Skills spec): `validate`, `read-properties`, `to-prompt`; «This library is intended for demonstration purposes only. It is not meant to be used in production.» — [agentskills/skills-ref](https://github.com/agentskills/agentskills/tree/main/skills-ref)
- [П] (вендор) skill-creator: Create/Eval/Improve/Benchmark, слепой comparator, оптимизация description (5/6). — [Claude blog 03.03.2026](https://claude.com/blog/improving-skill-creator-test-measure-and-refine-agent-skills)
- [В] agnix (agent-sh): линтер + LSP для CLAUDE.md, AGENTS.md, SKILL.md, hooks, MCP-конфигов; заявлено ~400+ правил (в одном описании «442 rules») для Claude Code, Codex, OpenCode, Cursor, Copilot; автофиксы; GitHub Action `agent-sh/agnix@v0`. Независимой оценки качества правил нет. — [agnix GitHub (форк/зеркало)](https://github.com/shawilly/agnix); [agnix wiki](https://github.com/agent-sh/agnix/wiki)
- [П] (вендор) Встроенный агент `claude-code-guide` отвечает на вопросы о хуках/настройках/`claude plugin eval` (виден в списке типов агентов этой сессии; задокументирован в описании агента). [И] — полезно как «справочник по текущей доке», но не валидатор.
- [П] (вендор) Хуки проверяются `/hooks` (источник каждого хука) и `--debug-file`. — [hooks](https://code.claude.com/docs/en/hooks)

### Inferences
- [И] Рабочий конвейер для соло: агент пишет скилл/хук через skill-creator или по доке (claude-code-guide) → `claude plugin validate` / agnix в pre-commit/CI (гарантия синтаксиса) → unit-тест хук-скрипта на recorded payload (п.2) → для скиллов — 3–5 eval-кейсов с baseline. Человек ревьюит diff инструкций.
- [И] hookify (плагин Anthropic для генерации хуков из разговорной формулировки) и plugin-dev toolkit — в этом прогоне не проверены.
- [И] Риск: агент, пишущий собственные guardrails, может написать fail-open хук (exit 1) или паттерн, который обходится; поэтому тест обхода (п.2) обязателен, и автор правила ≠ оценщик.

### Gaps
- Не проверены первоисточники: hookify, plugin-dev, cclint (не найден в поиске), официальный репозиторий agnix (agent-sh/agnix) открыт только wiki через поисковик.
- Данных об эффективности линтеров инструкций нет.

---

## 8. История работы: коммиты, журнал, учёт времени, оценка скорости

### Takeaway
Коммиты — главный долговременный артефакт (атомарные, conventional, «коммит на задачу», с trailer Co-Authored-By для AI); телеметрия Claude Code (OTel: commit.count, lines_of_code, active_time, cost) и локальные транскрипты (`~/.claude/projects/*.jsonl`, ccusage) дают объективный учёт, но транскрипты локальны и могут удаляться. Для оценки скорости — данные есть только свои; внешних калиброванных данных для соло нет.

### Cited Findings
- [П] (вендор) OTel Claude Code (`CLAUDE_CODE_ENABLE_TELEMETRY=1`): метрики `claude_code.session.count`, `lines_of_code.count`, `pull_request.count`, `commit.count`, `cost.usage`, `token.usage`, `code_edit_tool.decision`, `active_time.total` («excluding idle time», атрибут `type: user|cli`); события `user_prompt`, `tool_result`, `tool_decision`, `api_request`, `skill_activated`, хук-события и др.; промпты/детали инструментов редактируются по умолчанию (`OTEL_LOG_USER_PROMPTS`, `OTEL_LOG_TOOL_DETAILS`, `OTEL_LOG_TOOL_CONTENT`). — [monitoring](https://code.claude.com/docs/en/monitoring-usage)
- [П] Транскрипт сессии доступен хукам через `transcript_path` (JSONL). — [hooks](https://code.claude.com/docs/en/hooks)
- [П] ccusage: анализ токенов/стоимости из локальных данных (`~/.claude/projects` JSONL, `~/.config/claude`), отчёты daily/weekly/monthly/session/5-часовые blocks, JSON-экспорт; поддерживает Codex, OpenCode, Amp и др. — [ccusage GitHub](https://github.com/ryoppippi/ccusage)
- [П] (вендор) Auto memory хранится в `~/.claude/projects/<project>/memory/` — та же директория, что и транскрипты (не под VCS проекта). — [memory](https://code.claude.com/docs/en/memory)
- [П] (вендор) Хуки `SessionEnd`, `Stop`, `TaskCompleted` — точки для автоматической записи журнала (SessionEnd с бюджетом 1.5 с — только быстрые операции). — [hooks](https://code.claude.com/docs/en/hooks)

### Inferences
- [И] Опции: (а) conventional commits + атомарные коммиты + commitlint в commit-msg/CI — гарантия формата (в CI), договорённость по атомарности; (б) «коммит на задачу» с ID задачи в сообщении — связывает историю с планом; (в) журнал работы `docs/worklog/YYYY-MM-DD.md`, который агент дописывает по Stop/SessionEnd-хуку или вручную (договорённость, если вручную); (г) OTel в локальный collector или просто ccusage — объективное время/стоимость без дисциплины; (д) WakaTime — учёт времени в редакторе, не видит агентную работу в терминале (не проверено).
- [И] Для оценки скорости: `active_time.total` + число коммитов/задач по conventional-типам за неделю → собственная базовая линия; оценки новых задач калибровать по своим же завершённым задачам. Для малого проекта достаточно git log + ccusage; OTel-стек окупается при нескольких людях/проектах.
- [И] Транскрипты — сырой материал для пост-мортемов (п.5) и для eval-кейсов с `context.history_file` в `claude plugin eval` (п.6). Важно копировать нужные транскрипты в проект/архив, т.к. локальная директория может чиститься.

### Gaps
- Не проверил настройку `cleanupPeriodDays` (срок хранения транскриптов) в текущей доке — см. «Сомнительное».
- Не открыл conventionalcommits.org, WakaTime docs — уровень [И].
- Нет данных о связи стиля коммитов с качеством AI-работы.

---

## Сомнительное / не проверено

- Срок хранения транскриптов `~/.claude/projects` по умолчанию «30 дней» через `cleanupPeriodDays` — по памяти, в этом прогоне не проверено; ccusage README этого не упоминает.
- «#»-шорткат для быстрого добавления в память Claude Code — в текущей странице memory (2026-10-03) не найден; был ли удалён и когда — не проверено.
- agnix: число правил (442 / «400+») — из описаний в поиске, не из репозитория; репозиторий открыт по зеркалу shawilly/agnix, не agent-sh/agnix.
- `claude plugin validate` начиная с v2.1.77 проверяет frontmatter и hooks.json — из поискового пересказа, первоисточник (changelog) не открыт.
- Codex hooks stable с v0.124.0 (23.04.2026) — из стороннего блога (D. Vaughan), не из release notes OpenAI.
- Cursor: «Cursor CLI 2026.08.11 на Windows не запускает user-level hooks» — форумный отчёт, не подтверждён вендором.
- Утверждение Cognition (06.2025), что Claude Code «never does work in parallel with the subtask agent» — устарело: текущая дока описывает фоновые субагенты и до 20 одновременных.
- Цифра «90.2%» и «15×» — внутренний eval Anthropic на research-задачах; переносить на coding нельзя (сам источник это оговаривает).
- «Хуки не копируются при git clone» — пересказ страницы githooks моделью-извлекателем; факт общеизвестен, но точную цитату не сверял.
