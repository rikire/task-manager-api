# Блок 4 (05c): механизмы — безопасность агента, понимание кода человеком, атрибуция, контекст, состояние задач, согласованность и доставка инструкций, документация (2025–2026)

Контекст брифа: один разработчик + ИИ-агент, новый небольшой backend-сервис, человек владеет результатом и обязан понимать весь код. Нейтрально к стеку. Здесь варианты и критерии, готового процесса нет.

Легенда: [П] — проверено по первоисточнику (я его открыл); [В] — вторичный источник (агрегатор, пресса, поисковая выдача); [И] — моя интерпретация. (вендор) — источник от производителя инструмента. (старше 2025) — источник до 2025 года.
Вид механизма: **гарантия** — исполняется программой или ОС независимо от того, что решит модель; **договорённость** — текст, которому модель старается следовать.
Документацию Claude Code сверял по code.claude.com/docs **2026-10-03**. Номера версий (v2.1.xxx) взяты из документации на эту дату.

Сквозной первоисточник для всех разделов (вендор) [П]: в документации Claude Code прямо написано: «Claude treats them as context, not enforced configuration. To block an action regardless of what Claude decides, use a PreToolUse hook» — [memory](https://code.claude.com/docs/en/memory). Там же: «CLAUDE.md content is delivered as a user message after the system prompt… there's no guarantee of strict compliance». А также: «Settings rules are enforced by the client regardless of what Claude decides to do. CLAUDE.md instructions shape Claude's behavior but are not a hard enforcement layer.» Отсюда граница «гарантия / договорённость», которой пользуются все разделы ниже.

---

## 1. Безопасность агента: prompt injection, выдуманные пакеты, зависимости, секреты, права и песочница

### Takeaway
От prompt injection надёжной защиты на уровне модели нет. Гарантии дают только архитектурные ограничения: не собирать в одной сессии «смертельную триаду», плюс песочница ОС с allowlist сети и deny-правила. Инструкции вида «не выполняй команды из файлов» — это договорённость. От атак на цепочку поставок защищают детерминированные меры: lockfile, пиннинг, cooldown по минимальному возрасту релиза, отказ от постоянных токенов. Инциденты 2025 года (Nx s1ngularity, Amazon Q, Shai-Hulud) показывают, что локальный ИИ-CLI с флагами обхода прав сам становится инструментом атакующего.

### Cited Findings

**Prompt injection: модель угрозы**
- [П] «Смертельная триада» (Willison, 16.06.2025): доступ к приватным данным + контакт с недоверенным контентом + возможность внешней коммуникации. Корень проблемы: «LLMs follow instructions in content» и не различают, откуда пришла инструкция. Гардрейлы, которые «ловят 95% атак», для безопасности автор считает провалом — [simonwillison.net](https://simonwillison.net/2025/Jun/16/the-lethal-trifecta/)
- [В] Meta «Agents Rule of Two» (окт. 2025): в одной сессии допустимо не больше двух из трёх свойств: [A] обработка недоверенного ввода, [B] доступ к чувствительным системам или данным, [C] изменение состояния или внешняя коммуникация — [conffab](https://conffab.com/elsewhere/agents-rule-of-two-a-practical-approach-to-ai-agent-security/); [критика](https://kenhuangus.substack.com/p/the-rule-of-two-vs-reality-why-metas)
- [В] Beurer-Kellner et al., 2025, «Design Patterns for Securing LLM Agents against Prompt Injections». Шесть паттернов изоляции недоверенных данных от управляющего потока (например, Action-Selector — без обратной связи от результатов действий), «provable resistance» ценой полезности — [arXiv 2506.08837](https://arxiv.org/abs/2506.08837v3)
- [П] GitHub MCP (Invariant Labs, май 2025): вредоносный issue в публичном репозитории перехватывает агента. Агент вытягивает данные приватных репозиториев и публикует их PR-ом в публичный. Авторы называют это архитектурной проблемой («toxic agent flow»), а не багом сервера. Ключевая цитата: пользователи переходят на «Always Allow» «and stop monitoring individual actions» — [invariantlabs.ai](https://invariantlabs.ai/blog/mcp-github-vulnerability)

**Задокументированные инциденты с coding-агентами**
- [В] Amazon Q Developer для VS Code v1.84.0 (июль 2025). Коммит 13.07 добавил промпт на стирание домашнего каталога и AWS-ресурсов (`ec2 terminate-instances`, `s3 rm`, `iam delete-user`). Версия вышла 17.07 подписанным релизом, отозвана 19.07 (v1.85.0), CVE-2025-8217. Причина: GitHub-токен со слишком широкими правами в CodeBuild. AWS заявляет, что код был malformed и реальной угрозы не нёс — [AWS bulletin AWS-2025-015](https://aws.amazon.com/security/security-bulletins/AWS-2025-015/) (страница рендерится JS, текст бюллетеня я не прочитал); [The Register](https://www.theregister.com/2025/07/24/amazon_q_ai_prompt/); [BleepingComputer](https://bleepingcomputer.com/news/security/amazon-ai-coding-agent-hacked-to-inject-data-wiping-commands)
- [В] Nx «s1ngularity» (26.08.2025): 8 вредоносных версий nx в npm через украденный токен. Postinstall `telemetry.js` искал локальные Claude Code, Gemini CLI и Amazon Q и запускал их с `--dangerously-skip-permissions` / `--yolo` / `--trust-all-tools` и промптом «найди кошельки, SSH-ключи, .env». Утекло 2 349 секретов (по данным Nx) — [Okta](https://www.okta.com/blog/threat-intelligence/the-s1ngularity-attack--when-attackers-prompt-your-ai-agents-to/); [OX Security](https://www.ox.security/blog/nx-supply-chain-breach-how-s1ngularity-weaponized-ai/)
- [В] Shai-Hulud (сент. 2025): самораспространяющийся npm-червь, 500+ пакетов. Собирал токены, в том числе через установленный им TruffleHog. CISA рекомендует: аудит lockfile, пиннинг на релизы до 16.09.2025, ротация кредов, MFA, мониторинг egress, branch protection, secret scanning — [Anvilogic (пересказ CISA)](https://www.anvilogic.com/threat-reports/cisa-shai-hulud-npm-worm); волна «2.0» в ноябре 2025 — [Upwind](https://www.upwind.io/?p=14565)

**Выдуманные пакеты (slopsquatting)**
- [В] USENIX Security 2025, «We Have a Package for You!». 2,23 млн сэмплов, 16 моделей. 19,7% сэмплов содержат хотя бы один несуществующий пакет. У открытых моделей в среднем 21,7%, у коммерческих 5,2%, у GPT-4 Turbo 3,59%. 43% выдуманных имён повторяются при повторных запросах, поэтому их можно заранее зарегистрировать — [CSA research note](https://labs.cloudsecurityalliance.org/research/csa-research-note-slopsquatting-ai-supply-chain-20260419/) (сам препринт arXiv 2406.10279 я не открывал, он старше 2025, конференция 2025)

**Зависимости: cooldown, lockfile, пиннинг**
- [В] Минимальный возраст релиза появился в менеджерах пакетов: pnpm `minimumReleaseAge` (10.16, сент. 2025), Yarn `npmMinimalAgeGate` (4.10), Bun 1.3, npm `min-release-age` (11.10.0, фев. 2026), uv `exclude-newer` с относительным сроком (0.9.17, дек. 2025), pip `--uploaded-prior-to` (26.0). По подсчёту автора, у 8 из 10 разобранных атак окно было меньше недели, так что 7-дневный cooldown отсёк бы большинство — [nesbitt.io](https://nesbitt.io/2026/03/04/package-managers-need-to-cool-down); [Willison о нём](https://simonwillison.net/2026/Mar/24/package-managers-need-to-cool-down/)
- [В] Побочный эффект cooldown: pnpm 11 `minimumReleaseAge` ломает CI для PR от Dependabot — [classmethod](https://dev.classmethod.jp/en/articles/pnpm-11-minimum-release-age-dependabot-ci-failure/)

**Секреты**
- [П] (вендор) Claude Code, deny-правила для файлов: `Read(./.env)`, `Read(./secrets/**)`. `.claudeignore` «has no effect». Правила Read/Edit deny действуют на встроенные инструменты, на распознанные команды (`cat`, `head`, `sed`, `tee`) и на редиректы. На `grep -r` и на скрипт Python/Node, который сам открывает файлы, они **не действуют**: «For OS-level enforcement… enable the sandbox» — [permissions](https://code.claude.com/docs/en/permissions)
- [П] (вендор) Порядок правил: deny → ask → allow, первое совпадение решает. «An allow rule can't carve an exception out of a deny rule». Bash-deny не ловит ту же программу, вызванную по пути или внутри `sh -c`. Поэтому для сети его надо дополнять allowlist песочницы — [permissions](https://code.claude.com/docs/en/permissions)
- [П] (вендор) `sandbox.credentials`: файлы и переменные окружения в режиме `deny` или `mask`. При `mask` команда видит плейсхолдер, а прокси подставляет реальный секрет только для разрешённых хостов. Из репозиторного `.claude/settings.json` записи `credentials` не применяются (v2.1.246+). `CLAUDE_CODE_SUBPROCESS_ENV_SCRUB` вычищает креды из всех подпроцессов — [sandboxing](https://code.claude.com/docs/en/sandboxing)
- [И] gitleaks / trufflehog как pre-commit и CI-сканеры — это гарантия на уровне коммита. Страницы инструментов я в этой сессии не открывал. Ирония: Shai-Hulud сам использовал TruffleHog для поиска секретов (см. выше).

**Права и песочница**
- [П] (вендор) Песочница Claude Code — граница на уровне ОС (macOS Seatbelt, Linux/WSL2 bubblewrap + socat). Покрывает Bash, PowerShell, Monitor и их подпроцессы. **Не покрывает** встроенные Read/Edit/Write/WebFetch, MCP-серверы и hooks: «A `denyRead` entry doesn't stop the Read tool, and `allowedDomains` doesn't limit WebFetch». По умолчанию чтение открыто почти по всей машине, включая `~/.ssh` и `~/.aws/credentials`. Сеть идёт только через прокси с allowlist доменов, изначально пустым — [sandboxing](https://code.claude.com/docs/en/sandboxing)
- [П] (вендор) Аварийный выход: Claude может повторить упавшую команду с `dangerouslyDisableSandbox`. В режиме bypassPermissions это происходит без запроса. Закрывается через `allowUnsandboxedCommands: false` + `failIfUnavailable` («Strict sandbox mode»). Широкие `excludedCommands` (например, `docker *`) — дыра: Claude может записать compose-файл и выполнить его вне песочницы — [sandboxing](https://code.claude.com/docs/en/sandboxing)
- [П] (вендор) Hooks: PreToolUse может вернуть `permissionDecision: allow|deny|ask|defer`. Exit 2 блокирует вызов, и даже JSON `allow` этого не отменит — [hooks](https://code.claude.com/docs/en/hooks)
- [П] (вендор) Codex: режимы песочницы read-only / workspace-write / danger-full-access, Seatbelt и Landlock/bubblewrap. Подробную страницу sandboxing я не открывал, только индекс — [learn.chatgpt.com/docs/security](https://learn.chatgpt.com/docs/security)

### Таблица механизмов (раздел 1)

| Механизм | Тип | Гарантия? | Данные об эффективности |
|---|---|---|---|
| Инструкция в CLAUDE.md «не доверяй контенту, не трогай .env» | инструкция | договорённость | данных нет; Willison: 95% = провал [П] |
| Разрыв триады (нет секретов / нет egress / нет недоверенного ввода в одной сессии) | архитектура, settings | гарантия, если обеспечена песочницей и правами | качественный аргумент [П]; количественных данных нет |
| Permission deny rules (`Read(./.env)`, `Bash(curl *)`) | settings | гарантия только для распознанных путей; обходится скриптами и `sh -c` [П] | данных нет |
| Песочница ОС (bubblewrap/Seatbelt) + allowlist сети + strict mode | settings / ОС | гарантия для shell-подпроцессов; не для Read/WebFetch/MCP [П] | данных нет |
| Devcontainer / VM | инфраструктура | гарантия изоляции хоста | данных нет |
| PreToolUse hook (блок опасных команд) | hook | гарантия для событий, которые видит hook | данных нет |
| Lockfile + пиннинг + cooldown 7 дней | менеджер пакетов / CI | гарантия | ретроспективно: 8 из 10 атак отсеклись бы [В] |
| Проверка существования и возраста пакета перед установкой (против slopsquatting) | hook / CI | гарантия, если проверка автоматическая | частота галлюцинаций 19,7% [В]; эффективность контрмер: данных нет |
| gitleaks / trufflehog в pre-commit и CI | CI | гарантия на уровне коммита (по сигнатурам) | данных нет (не искал) |
| Не запускать агентов с `--dangerously-skip-permissions` на хосте с кредами | договорённость человека | договорённость | Nx: атакующий использовал именно этот флаг [В] |

### Inferences
- [И] Для одного разработчика минимальный набор гарантий такой: песочница в strict mode (или devcontainer) + пустой allowlist сети, дополняемый вручную + `Read` deny и `credentials` для секретов + lockfile и cooldown + отсутствие долгоживущих npm/GitHub-токенов в окружении агента. Всё остальное — договорённости.
- [И] Ключевой неочевидный факт: deny-правила в Claude Code не равны изоляции, потому что скрипт, запущенный агентом, читает `.env` в обход правил. Без песочницы deny для секретов — лишь частичная мера.
- [И] Инциденты 2025 года указывают на новый вектор: вредоносный пакет использует уже установленного ИИ-агента как «интеллектуальный стилер». Поэтому сама установка агентских CLI с флагами обхода прав увеличивает поверхность атаки.

### Gaps
- Количественных данных о доле пойманных prompt injection у конкретных мер (песочница, hooks, классификатор auto mode) в открытых источниках не нашёл.
- Первоисточники Nx (postmortem nx.dev) и AWS-бюллетень отрисовываются JS, текст я не получил. Цифры взяты из вторичных источников.
- Документацию Codex sandboxing подробно не открывал. Детали сетевого доступа по умолчанию в Codex не проверены.
- Anthropic/OpenAI guidance по prompt injection (отдельные статьи вендоров 2025–2026) отдельно не открывал. Опираюсь на документацию Claude Code.

---

## 2. Как человек узнаёт весь код, написанный агентом

### Takeaway
Есть одно рандомизированное исследование (Anthropic, янв. 2026). При делегировании генерации кода понимание падает на ~17 п.п. При вопросах на понимание или режиме «сгенерировал → расспросил» оно сохраняется. Механизмы «понимания» почти все — договорённости. Гарантировать можно лишь процедурные гейты: мелкий diff, обязательное ревью, тест на объяснение. Само понимание они не гарантируют.

### Cited Findings
- [П] (вендор) Anthropic RCT, 29.01.2026. n=52, в основном junior, незнакомая библиотека Trio. Квиз у группы с ИИ 50% против 67% у группы без ИИ (Cohen's d=0.738, p=0.01), сильнее всего просел дебаг. Ускорение ~2 мин, статистически незначимо. Понимание сохраняли паттерны «generation-then-comprehension», «hybrid code-explanation» и «conceptual inquiry». Оговорки: мерили немедленное понимание, выборка малая — [anthropic.com/research](https://www.anthropic.com/research/AI-assistance-coding-skills)
- [В] Подгруппы того же исследования: кто задавал ИИ концептуальные вопросы, набрал ≥65%, кто делегировал генерацию — <40% — [InfoQ](https://infoq.com/news/2026/02/ai-coding-skill-formation/) (в основном тексте Anthropic эти цифры я не нашёл, см. «Сомнительное»)
- [В] «Comprehension debt» (Osmani, 14.03.2026): разрыв между объёмом кода и тем, что реально понимает человек. Порождает ложную уверенность — [addyosmani.com](https://addyosmani.com/blog/comprehension-debt/). Storey (апр. 2026): «cognitive debt» (эрозия общего понимания) и «intent debt» (нет зафиксированного обоснования решений). Пример: студенческая команда на 7-й неделе не могла объяснить решения системы — [LeadDev](https://leaddev.com/ai/ai-coding-creates-two-kinds-of-debt-youre-only-measuring-one); [arXiv 2603.22106](https://www.alphaxiv.org/abs/2603.22106) (не открывал)
- [П] (вендор) Claude Code output styles: **Explanatory** добавляет блоки `★ Insight` с обоснованием выборов, в разговоре, а не в файлах. **Learning** — то же плюс `TODO(human)`: в местах с реальным дизайн-решением Claude оставляет человеку написать несколько строк, останавливается и ждёт. Документация прямо говорит: «An output style gives Claude instructions to follow. It doesn't guarantee that something always happens». Стиль действует на основной разговор и fork, на остальных субагентов — нет — [output-styles](https://code.claude.com/docs/en/output-styles)
- [П] Ghostty: «If you can't explain what your changes do and how they interact with the greater system without the aid of AI tools, do not contribute» — по сути «explain-back» как условие приёма — [AI_POLICY.md](https://raw.githubusercontent.com/ghostty-org/ghostty/main/AI_POLICY.md)
- [П] LLVM: «Contributors must read and review all LLM-generated code or text before they ask other project members to review it»; автор должен уметь отвечать на вопросы на ревью — [llvm.org AIToolPolicy](https://llvm.org/docs/AIToolPolicy.html)
- [П] (вендор) Claude Code checkpoints снимают снапшот файлов перед правками и позволяют откатиться (`/rewind`). Удалённые действия (БД, API, деплой) не покрывают — [how-claude-code-works](https://code.claude.com/docs/en/how-claude-code-works)

### Таблица механизмов (раздел 2)

| Механизм | Тип | Гарантия? | Данные |
|---|---|---|---|
| Мелкие diff (лимит строк на коммит или PR) | CI-check или hook на размер diff | гарантия размера; понимание не гарантирует | данных нет |
| Обязательное построчное ревью человеком перед коммитом (без автокоммита агентом) | права (`ask` на `git commit`) + процесс | гарантия «был показан»; понимание — договорённость | данных нет |
| Explain-back: человек объясняет изменение своими словами или проходит квиз от агента | skill / ритуал | договорённость | RCT: понимание выше у тех, кто расспрашивает [П] |
| Walkthrough от агента (тур по diff, диаграмма) | skill / output style | договорённость | данных нет |
| Output style Explanatory / Learning (`TODO(human)`) | output style | договорённость [П] | прямых данных о самих стилях нет; RCT поддерживает механизм «писать руками / расспрашивать» [И] |
| Часть кода пишется руками (ядро домена, обработка ошибок) | процесс | договорённость | RCT: ручная группа понимала лучше [П] |
| ADR / «intent» в коммит-сообщениях (против intent debt) | артефакт | договорённость, проверяемо CI на наличие | данных нет |

### Inferences
- [И] Для владельца, который обязан понимать весь код, единственная доказательная опора — RCT Anthropic. Пассивное делегирование вредит пониманию, активное расспрашивание его сохраняет. Значит, explain-back и Learning-стиль — это механизмы с правдоподобным, но косвенным обоснованием.
- [И] Гарантией можно сделать только гейты (размер diff, запрет автокоммита, обязательный шаг ревью). Понимание остаётся договорённостью человека с самим собой.

### Gaps
- Нет данных о долгосрочном влиянии (месяцы) и о влиянии на опытных разработчиков. Нет исследований эффективности конкретно Learning/Explanatory-стилей.
- Исследований об оптимальном размере diff для ревью ИИ-кода в 2025–2026 не искал и не нашёл. Классические данные (например, SmartBear/Cisco) старше 2025.

---

## 3. Разделение работы человека и ИИ, атрибуция, учёт ручных правок

### Takeaway
Сложился стандарт: трейлер `Assisted-by:` (Linux, LLVM, Fedora) и запрет агенту ставить `Signed-off-by`. Ответственность всегда лежит на человеке. Есть и полные запреты (QEMU, Gentoo, NetBSD). Ручные правки между шагами Claude Code обнаруживает механически. Edit сверяет `old_string` с текущим содержимым файла, а hook `FileChanged` ловит любые изменения на диске. Это гарантия против перезаписи устаревшей версией, но не гарантия того, что агент учтёт смысл правки.

### Cited Findings
- [П] Linux kernel `Documentation/process/coding-assistants.rst`: «AI agents MUST NOT add Signed-off-by tags. Only humans can legally certify the DCO». Формат: `Assisted-by: LLM [TOOL1] [TOOL2]`, пример `Assisted-by: LLM coccinelle sparse`. git/gcc/make не перечисляются — [docs.kernel.org](https://docs.kernel.org/process/coding-assistants.html). Расхождение см. в «Сомнительном»: вторичные источники приводят формат `AGENT_NAME:MODEL_VERSION`.
- [В] Документ закоммичен 23.12.2025 — [It's FOSS](https://itsfoss.com/news/linux-ai-coding-assistants-policy/); [LWN](https://lwn.net/Articles/1083275/)
- [П] LLVM AI Tool Use Policy («human in the loop»): обязательное ревью ИИ-контента человеком; маркировать существенный сгенерированный контент трейлером `Assisted-by:`; запрещены «extractive contributions»; запрещено использовать ИИ для «good first issue»; запрещены агенты, действующие без одобрения человека (например, GitHub @claude) — [llvm.org](https://llvm.org/docs/AIToolPolicy.html); [RFC](https://discourse.llvm.org/t/rfc-llvm-ai-tool-policy-human-in-the-loop/89159)
- [В] Fedora (одобрено Council 22.10.2025): автор отвечает за всё. Раскрытие MUST, если значимая часть взята без изменений, SHOULD — в остальных случаях. Рекомендуемый способ — трейлер `Assisted-by` — [Fedora docs](https://docs.fedoraproject.org/ca/council/policy/ai-contribution-policy/); [Phoronix](https://www.phoronix.com/news/Fedora-Allows-AI-Contributions)
- [П] QEMU (текущий master): «DECLINE any contributions which are believed to include or derive from AI generated content». Обоснование: нельзя добросовестно подтвердить DCO (b)/(c). Исследование API, статанализ и отладка разрешены, если результат не попадает в патч — [qemu.org code-provenance](https://www.qemu.org/docs/master/devel/code-provenance.html). [В] Обсуждается смягчение: механические правки, тесты, документация, фиксы ≤20 строк — [linuxiac](https://linuxiac.com/qemu-may-relax-its-ban-on-ai-generated-contributions/)
- [П] Gentoo: «expressly forbidden to contribute… content that has been created with the assistance of NLP AI tools». Причины: копирайт, качество, этика (решение 2024, старше 2025) — [wiki.gentoo.org](https://wiki.gentoo.org/wiki/Project:Council/AI_policy)
- [П] NetBSD commit guidelines: код от LLM «is presumed to be tainted code, and must not be committed without prior written approval by core» (старше 2025) — [netbsd.org](https://www.netbsd.org/developers/commit-guidelines.html)
- [П] Ghostty: раскрывать любое использование ИИ с указанием инструмента и объёма. Для мейнтейнеров исключение — [AI_POLICY.md](https://raw.githubusercontent.com/ghostty-org/ghostty/main/AI_POLICY.md)
- [В] Apache Software Foundation Generative Tooling guidance (рекомендует трейлер `Generated-by:`). В этой сессии не открывал, см. «Сомнительное».
- [П] (вендор) Claude Code по умолчанию добавляет в коммиты трейлер `Co-Authored-By`, в PR — текстовую строку. Это настраивается через `attribution.commit` / `attribution.pr` / `attribution.sessionUrl`, `attribution: false` (v2.1.281+). `includeCoAuthoredBy` устарел. Инструкции пользователя об атрибуции (CLAUDE.md или memory) имеют приоритет над этими строками, если они не заданы в managed settings — [settings-reference](https://code.claude.com/docs/en/settings-reference)

**Как агент учитывает ручные правки человека**
- [П] (вендор) Edit tool: read-before-edit плюс точное и уникальное совпадение `old_string`. Если файл изменился на диске после чтения, правка проходит только тогда, когда `old_string` точно совпадает с текущим содержимым. В результате помечается, что в файле есть другие изменения, чтобы Claude перечитал его. Иначе Claude перечитывает файл перед правкой. Для старых моделей (Opus 4.6, Haiku 4.5 и ранее) чтение обязательно всегда. До v2.1.208 любая правка изменённого после чтения файла отклонялась — [tools-reference](https://code.claude.com/docs/en/tools-reference)
- [П] (вендор) Write: новые модели могут перезаписать непрочитанный файл, если чтение не требовало бы запроса прав. До v2.1.228 чтение было обязательно для всех моделей — [tools-reference](https://code.claude.com/docs/en/tools-reference)
- [П] (вендор) Hook `FileChanged` срабатывает от filesystem watcher, то есть на любое изменение, в том числе внешним процессом. Matcher задаёт буквальные имена файлов, без regex — [hooks](https://code.claude.com/docs/en/hooks)
- [П] (вендор) После `/compact` Claude Code перечитывает с диска до 5 недавно изменённых файлов и делает свежий снапшот git status — [context-window](https://code.claude.com/docs/en/context-window)

### Таблица механизмов (раздел 3)

| Механизм | Тип | Гарантия? | Данные |
|---|---|---|---|
| Трейлер `Assisted-by` / `Co-Authored-By` от агента | settings `attribution` | гарантия добавления, если коммитит Claude Code; ручные коммиты не покрыты | данных нет |
| Проверка трейлера в commit-msg hook или CI | git hook / CI | гарантия | данных нет |
| Запрет агенту `Signed-off-by` / коммита без человека | правило `ask` на `git commit` / hook | гарантия | данных нет |
| Раздельные коммиты «человек» и «агент» | процесс | договорённость | данных нет |
| Детекция ручных правок: сверка Edit с текущим содержимым | встроено | гарантия от перезаписи устаревшего текста; смысл правки — нет | данных нет |
| `git diff` / `git status` в начале каждого шага (hook UserPromptSubmit → additionalContext) | hook | гарантия доставки диффа в контекст; учёт — договорённость | данных нет |
| `FileChanged` hook | hook | гарантия срабатывания для перечисленных файлов | данных нет |

### Inferences
- [И] Для одного разработчика атрибуция важна не юридически, а чтобы потом отличать «что писал я, что агент». Минимальная гарантия: Claude Code ставит трейлер, а commit-msg hook проверяет, что у коммитов, сделанных в сессии агента, трейлер есть.
- [И] Правки человека «в промежутке» Claude Code сам не пересказывает модели. Надёжно передать модели их смысл может hook, который вставляет `git diff` с момента последнего шага.

### Gaps
- Эмпирических данных о том, как часто агенты затирают ручные правки, не нашёл.
- Политику ASF и точную формулировку Linux-формата трейлера в текущей версии не проверил окончательно (см. «Сомнительное»).

---

## 4. Контекст агента: AGENTS.md / CLAUDE.md, skills, rules, память, гигиена — и данные

### Takeaway
Данные противоречивы, но сходятся к одному выводу: **минимальный, написанный человеком** файл инструкций умеренно помогает (+4% к успеху, −29% времени), а сгенерированный LLM или раздутый вредит (−0.5…−3% к успеху, +20% к стоимости). Число инструкций упирается в потолок: деградация начинается уже на десятках-сотнях, и чем длиннее контекст, тем хуже. Skills удобны для экономии контекста, но модель их часто не вызывает: в эвале Vercel в 56% случаев skill не был вызван.

### Cited Findings
- [П] Gloaguen et al., ETH/LogicStar, arXiv 2602.11988 (12.02.2026), «Evaluating AGENTS.md». AGENTbench: 138 задач, 12 репозиториев. Плюс SWE-bench Lite. Агенты: Claude Code/Sonnet-4.5, Codex/GPT-5.2 и GPT-5.1 mini, Qwen Code. Файлы, сгенерированные LLM, дают −2…−3% к успеху (на SWE-bench Lite −0.5%). Файлы, написанные разработчиками, дают в среднем +4%. Стоимость растёт на 20–23%, шагов на задачу больше на 2.45–3.92. Агенты в целом следуют инструкциям, исследуют больше. Рекомендация: не использовать сгенерированные LLM файлы и описывать «only minimal requirements (e.g., specific tooling)» — [arXiv abs](https://arxiv.org/abs/2602.11988); [HTML](https://arxiv.org/html/2602.11988v1)
- [П] Lulla et al., arXiv 2601.20404 (28.01.2026; JAWs@ICSE 2026). 10 репозиториев, 124 PR, Codex. С AGENTS.md медианное время на 28.64% ниже, выходных токенов на 16.58% меньше, завершение задач сопоставимо — [arXiv](https://arxiv.org/abs/2601.20404v1)
- [П] (вендор) Vercel, 27.01.2026. Эвал на API Next.js 16, которых нет в обучающих данных. Базовая линия 53%, skill по умолчанию 53%, skill с явной инструкцией его вызвать 79%, сжатый (8 КБ) индекс документации прямо в AGENTS.md 100%. Объяснение: нет «точки решения» для агента, текст всегда в контексте. По вторичному пересказу, в 56% случаев skill не вызывался (см. «Сомнительное») — [vercel.com](https://vercel.com/blog/agents-md-outperforms-skills-in-our-agent-evals)
- [В] IFScale (Distyl AI, arXiv 2507.11538, NeurIPS 2025). 500 инструкций на включение ключевых слов, 20 моделей. Лучшие фронтир-модели на 500 инструкциях выдают 68%. Три паттерна деградации: пороговый (o3, gemini-2.5-pro), линейный (gpt-4.1, claude-sonnet-4), экспоненциальный (gpt-4o, llama-4-scout). Есть смещение в пользу ранних инструкций — [arXiv](https://arxiv.org/abs/2507.11538); [HTML](https://arxiv.org/html/2507.11538v1)
- [П] Chroma «Context Rot» (14.07.2025): 18 моделей (GPT-4.1, Claude 4, Gemini 2.5, Qwen3). «model performance degrades as input length increases, often in surprising and non-uniform ways». Даже один дистрактор снижает качество. Сфокусированный промпт в LongMemEval значительно лучше полного — [trychroma.com](https://www.trychroma.com/research/context-rot)
- [В] «Lost in the Middle» (Liu et al., 2023, старше 2025): факты в середине длинного контекста извлекаются хуже, чем в начале и конце — пересказ в [morphllm](https://www.morphllm.com/context-rot); оригинал не открывал
- [П] (вендор) Anthropic «Effective context engineering» (29.09.2025): «smallest possible set of high-signal tokens». Баланс между «too brittle» и «too vague». Just-in-time retrieval. У Claude Code гибрид: CLAUDE.md подаётся заранее, glob/grep — по требованию. Для длинных задач: compaction, структурированные заметки, субагенты — [anthropic.com/engineering](https://www.anthropic.com/engineering/effective-context-engineering-for-ai-agents)
- [П] (вендор) Claude Code memory: целевой размер — «under 200 lines per CLAUDE.md file. Longer files consume more context and reduce adherence». `@imports` не снижают стоимость контекста, импортированные файлы тоже грузятся при старте (до 4 хопов). Если инструкция многошаговая или касается части кода, её место — в skill или path-scoped rule. Auto memory: `~/.claude/projects/<project>/memory/MEMORY.md`, при старте загружаются первые 200 строк или 25 КБ, топик-файлы читаются по требованию, хранится локально на машине — [memory](https://code.claude.com/docs/en/memory)
- [П] (вендор) AGENTS.md в Claude Code (v2.1.277+): читается, если нет CLAUDE.md. Режим `claude-md-and-agents-md` грузит оба — [memory](https://code.claude.com/docs/en/memory)
- [П] (вендор) Codex: AGENTS.md конкатенируются от корня вниз, ближайший к cwd идёт последним и «переопределяет». Есть `AGENTS.override.md`. Лимит `project_doc_max_bytes` 32 KiB, загрузка «once per run» — [learn.chatgpt.com](https://learn.chatgpt.com/docs/agent-configuration/agents-md)
- [П] (вендор) Cursor: 4 типа правил (Always / Apply Intelligently / по glob / вручную), «Keep rules under 500 lines». Поддерживаются вложенные AGENTS.md. User Rules не применяются к Inline Edit и Tab — [cursor.com/docs](https://cursor.com/docs/context/rules)
- [П] (вендор) Skills, открытый стандарт Agent Skills ([agentskills.io](https://agentskills.io)). При старте грузится только листинг (имя + description, усечение до 1 536 символов, бюджет ~1% окна). Тело грузится при вызове. `disable-model-invocation: true` означает только ручной вызов. Есть поле `paths` — [skills](https://code.claude.com/docs/en/skills)
- [П] (вендор) `/doctor` предлагает урезать CLAUDE.md: убрать выводимое из кода (структуру каталогов, зависимости, обзор архитектуры) и оставить подводные камни, обоснования и отличия от умолчаний (v2.1.206+) — [memory](https://code.claude.com/docs/en/memory)

### Таблица механизмов (раздел 4)

| Механизм | Тип | Гарантия попадания в контекст | Гарантия выполнения | Данные |
|---|---|---|---|---|
| Корневой CLAUDE.md / AGENTS.md | инструкция | да, при старте и после compact [П] | нет | +4% (человеческий), −2…3% (LLM), +20% стоимости [П]; −28.6% времени [П] |
| `@import` | инструкция | да (при старте) | нет | данных нет |
| `.claude/rules/` без `paths` | инструкция | да | нет | данных нет |
| Skills (автовызов) | skill | только листинг; тело — по решению модели | нет | Vercel: 53% = как без документации, с явной инструкцией 79% [П] |
| Skill с `disable-model-invocation` + `/name` | skill | да, при ручном вызове | нет | данных нет |
| Auto memory | память | первые 200 строк / 25 КБ | нет | данных нет |
| Субагент с собственным промптом | subagent | да, в его контексте | нет | данных нет |

### Inferences
- [И] Практический порог «сколько инструкций слишком много» эмпирически не установлен для coding-агентов. IFScale показывает заметную деградацию на сотнях инструкций, но задача там синтетическая. Вендорские ориентиры: <200 строк на CLAUDE.md (Anthropic), <500 строк на правило (Cursor), 32 KiB на всю цепочку (Codex).
- [И] Сочетание данных: в CLAUDE.md держать только то, что агент не выведет сам (команды, неочевидные запреты, «почему»). Остальное — в path-scoped rules и skills. Критичные знания, которые нельзя оставлять на выбор модели, — в постоянный контекст в сжатом виде (вывод Vercel) или в hooks.

### Gaps
- Нет исследований, напрямую сравнивающих path-scoped rules и монолитный файл.
- Все исследования AGENTS.md сделаны на существующих OSS-репозиториях. Для нового маленького сервиса данных нет.

---

## 5. Состояние задачи между сессиями и при компактации

### Takeaway
Надёжно переживает `/compact` и рестарт только то, что лежит **на диске**: корневой CLAUDE.md, auto memory, файл плана, git. Всё сказанное только в разговоре сжимается в саммари и может потеряться. Anthropic в своём harness для долгих агентов использует `claude-progress.txt` + `feature list` в JSON + git-коммиты + «одна фича за сессию». Количественных данных об эффективности такого подхода нет.

### Cited Findings
- [П] (вендор) Что переживает компактацию в Claude Code. Системный промпт и output style: да. Корневой CLAUDE.md и rules без `paths`: перечитываются с диска. Auto memory: перечитывается. Git status: свежий снапшот. План из plan mode: перечитывается с диска. Path-scoped rules и вложенные CLAUDE.md: только при следующем чтении подходящего файла. Файлы: до 5 последних изменённых (файлы >5 000 токенов возвращаются ссылкой). Тела вызванных skills: ≤5 000 токенов на skill, ≤25 000 в сумме, при переполнении отбрасываются самые старые. Контекст от hooks: сжимается в саммари. SessionStart hooks с matcher `compact` запускаются заново. Листинг skills после compact **не** восстанавливается — [context-window](https://code.claude.com/docs/en/context-window)
- [П] (вендор) Саммари сохраняет запросы и намерение, ключевые концепции, изученные и изменённые файлы с фрагментами, ошибки и их фиксы, незавершённые задачи, текущую работу. Полные выводы инструментов и промежуточные рассуждения теряются — [context-window](https://code.claude.com/docs/en/context-window)
- [П] (вендор) «If an instruction disappeared after compaction, it was given only in conversation… Add conversation-only instructions to CLAUDE.md to make them persist». `/compact <фокус>`, `/autocompact <токены>` — [memory](https://code.claude.com/docs/en/memory); [context-window](https://code.claude.com/docs/en/context-window)
- [П] (вендор) Сессии хранятся в JSONL в `~/.claude/projects/`. Возобновление: `claude --continue`, `--resume [name|id|path]`, `/resume` — [sessions](https://code.claude.com/docs/en/sessions)
- [П] (вендор) Anthropic «Effective harnesses for long-running agents» (26.11.2025). Initializer-агент создаёт `init.sh`, `claude-progress.txt` и первый коммит. Coding-агент берёт одну фичу за сессию, оставляет «clean state», в начале сессии читает git log и progress-файл. Список фич хранится в JSON с полем `passes`, потому что «the model is less likely to inappropriately change or overwrite JSON files compared to Markdown files». Наблюдённые сбои: попытка сделать всё за раз, преждевременное «готово», фича помечена завершённой без теста. **Количественных метрик в статье нет** — [anthropic.com/engineering](https://www.anthropic.com/engineering/effective-harnesses-for-long-running-agents)
- [В] Beads (Steve Yegge): трекер задач в git, JSONL в `.beads/`, ID-хеши, приоритеты, граф зависимостей. Позиционируется как замена markdown-TODO. По утверждению одного вторичного источника, «вдохновил Claude Code Tasks» — [morphllm](https://www.morphllm.com/beads-agent-memory); [pkg.go.dev](https://pkg.go.dev/github.com/steveyegge/beads@v0.21.9)

### Таблица механизмов (раздел 5)

| Хранилище состояния | Переживает compact | Переживает рестарт / resume | Тип | Данные |
|---|---|---|---|---|
| Только разговор | частично (саммари) | resume — да (транскрипт) | — | данных нет |
| Корневой CLAUDE.md (решения, «что сделано» — не рекомендуется, раздувает) | да | да | инструкция | см. раздел 4 |
| Progress-файл (`progress.md` / `claude-progress.txt`), подключённый через SessionStart hook (matchers `startup|resume|compact`) | да (hook) | да | артефакт + hook = гарантия доставки | данных нет |
| Feature list JSON с `passes` | читается по требованию / через hook | да | артефакт | обоснование качественное [П] |
| Git (коммиты как журнал, ветки) | git status перечитывается | да | артефакт | данных нет |
| План plan mode | да | да (файл) | встроено | данных нет |
| Auto memory | да | да (локально на машине) | встроено | данных нет |
| Beads / issue-трекер | через инструмент или hook | да | внешний инструмент | данных нет |

### Inferences
- [И] Гарантия «агент увидит состояние после compact» достигается сочетанием файла состояния и SessionStart-hook с matcher `compact`. Одна строчка в CLAUDE.md «читай progress.md» — это договорённость.
- [И] Решения (ADR) и «что сделано» лучше разделять. Решения — долгоживущие, и CLAUDE.md может их импортировать или упоминать. Прогресс — короткоживущий, держится в отдельном файле или в git.

### Gaps
- Нет исследований, сравнивающих progress-файлы, Beads и встроенные Tasks.
- Структуру встроенной системы Tasks/Todo в Claude Code в этой сессии не открывал.

---

## 6. Согласованность инструкций: противоречия, отмена устаревшего, линтеры

### Takeaway
Вендор прямо говорит: при противоречии «Claude may pick one arbitrarily». Встроенный механизм проверки — `/doctor prompt-audit` (это LLM-аудит, не детерминированная проверка). Есть сторонние линтеры (agnix, ailint, ctxlint). Детерминированная гарантия возможна только через CI-проверку: grep по отменённым формулировкам, мёртвым путям и отсутствующим командам. Данных об эффективности нет.

### Cited Findings
- [П] (вендор) «if two instructions contradict each other, Claude may pick one arbitrarily. Review your CLAUDE.md files, nested CLAUDE.md…, and `.claude/rules/` periodically to remove outdated or conflicting instructions». Между user- и project-rules: «Neither set overrides the other… Claude may follow either one» — [memory](https://code.claude.com/docs/en/memory)
- [П] (вендор) `/doctor prompt-audit` ищет инструкции для старых моделей, ссылки на несуществующие файлы и команды, взаимные противоречия. Охватывает CLAUDE.md, AGENTS.md, rules, skills, commands, subagents, output styles. Выдаёт отчёт с предложенными правками и ничего не меняет без запроса — [memory](https://code.claude.com/docs/en/memory)
- [П] (вендор) Отдельный источник конфликтов — встроенные инструкции Claude Code о коммитах и PR. Их отключает `includeGitInstructions`, а `attribution` задаёт текст атрибуции — [memory](https://code.claude.com/docs/en/memory)
- [П] (вендор) Правки CLAUDE.md посреди сессии не применяются сразу (связано с prompt caching). Значит, отменённое правило живёт в контексте до рестарта или compact — [prompt-caching](https://code.claude.com/docs/en/prompt-caching)
- [П] (вендор) Codex: вложенные AGENTS.md конкатенируются, более близкий «overrides» за счёт позиции, а не явного разрешения конфликта — [learn.chatgpt.com](https://learn.chatgpt.com/docs/agent-configuration/agents-md)
- [В] Линтеры инструкций. **agnix** (Rust, LSP, по данным источника 399 правил, CLAUDE.md / SKILL.md / hooks / MCP) — [codex.danielvaughan.com](https://codex.danielvaughan.com/2026/04/13/agnix-linting-codex-cli-agent-configurations/). **ailint** (кросс-файловая согласованность) — [github](https://github.com/jamesmhall/ailint). **ctxlint** (устаревшие команды, мёртвые пути) — [producthunt](https://www.producthunt.com/p/ctxlint). Утилита с форума Cursor (drift AGENTS.md, конфликтующие вложенные файлы) — [forum.cursor.com](https://forum.cursor.com/t/built-a-small-cli-to-detect-agents-md-drift-missing-commands-dead-paths-conflicting-nested-files/159582). Ни один я не запускал.
- [П] (вендор) Хук `InstructionsLoaded` логирует, какие CLAUDE.md и rules загружены, когда и почему. Можно использовать для аудита фактически загруженного набора — [memory](https://code.claude.com/docs/en/memory)

### Таблица механизмов (раздел 6)

| Механизм | Тип | Гарантия? | Данные |
|---|---|---|---|
| Единый источник истины (AGENTS.md, импортируемый из CLAUDE.md, или наоборот) | структура | гарантия отсутствия дубликатов, если соблюдается | данных нет |
| ADR с разделом «отменяет правила: …» + CI-grep по отменённым формулировкам | артефакт + CI | гарантия для перечисленных строк | данных нет |
| CI-проверка мёртвых путей и команд в инструкциях (ctxlint / agnix / скрипт) | CI | гарантия для проверяемых классов | данных нет |
| `/doctor prompt-audit` | встроенный LLM-аудит | нет (LLM, по запросу) | данных нет |
| Периодический ручной обзор | процесс | договорённость | данных нет |
| `InstructionsLoaded` hook → лог | hook | гарантия наблюдаемости | данных нет |

### Inferences
- [И] Схема «ADR перечисляет отменяемые правила, а CI грепает их остатки» в публичных источниках как готовая практика мне не попалась. Это сборка из известных частей, а не задокументированный паттерн.
- [И] Самый дешёвый детерминированный шаг для одного разработчика — один источник инструкций плюс CI-скрипт, который проверяет существование упомянутых путей и команд.

### Gaps
- Нет данных об эффективности линтеров инструкций и `/doctor prompt-audit`.
- Не нашёл исследований о том, как модели разрешают противоречия в инструкциях агента (кроме вендорской фразы «arbitrarily»).

---

## 7. Доставка правила в нужный момент vs однократное чтение при старте

### Takeaway
Есть градация. Правило, прочитанное при старте, со временем «тонет» (context rot, деградация в многоходовых диалогах на 39%). Path-scoped rules, вложенные CLAUDE.md и skills с `paths` подгружаются по триггеру чтения файла, но после compact пропадают до следующего триггера. **Гарантию доставки** в момент действия дают только hooks: `additionalContext` в PreToolUse, PostToolUse, UserPromptSubmit и SessionStart. **Гарантию выполнения** даёт только блокирующий hook (deny / exit 2).

### Cited Findings
- [П] (вендор) Path-scoped rules срабатывают, «when Claude uses the Read, Write, or Edit tool on a file matching the pattern, not on every tool use». Вложенные CLAUDE.md подгружаются, когда Claude читает файлы в подкаталоге — [memory](https://code.claude.com/docs/en/memory)
- [П] (вендор) «Path-scoped rules and nested CLAUDE.md files load into message history when their trigger file is read, so compaction summarizes them away… If a rule must persist across compaction, drop the `paths:` frontmatter or move it to the project-root CLAUDE.md» — [context-window](https://code.claude.com/docs/en/context-window)
- [П] (вендор) Где события hooks добавляют `additionalContext`: SessionStart и SubagentStart — в начале; UserPromptSubmit и UserPromptExpansion — рядом с промптом; PreToolUse, PostToolUse, PostToolUseFailure, PostToolBatch — «next to the tool result»; Stop и SubagentStop — в конце хода, разговор продолжается; PostModelSwitch. Обычный stdout при exit 0 в контекст не попадает. Вывод больше 10 000 символов сохраняется в файл, модель получает превью — [hooks](https://code.claude.com/docs/en/hooks); [context-window](https://code.claude.com/docs/en/context-window)
- [П] (вендор) «If the instruction is something that must run at a specific point, such as before every commit or after each file edit, write it as a hook… Hooks… apply regardless of what Claude decides to do» — [memory](https://code.claude.com/docs/en/memory)
- [П] (вендор) Тело skill входит в разговор одним сообщением и остаётся там. Claude Code не перечитывает файл skill на последующих ходах. Автовызов не гарантирован — [skills](https://code.claude.com/docs/en/skills)
- [П] (вендор) Субагенты: свой системный промпт, свой контекст. Auto memory основного диалога в них не грузится (кроме fork). Output style на не-fork субагентов не действует — [memory](https://code.claude.com/docs/en/memory); [output-styles](https://code.claude.com/docs/en/output-styles)
- [П] (вендор) Codex грузит AGENTS.md «once per run». Аналогов path-scoped подгрузки в просмотренной странице нет — [learn.chatgpt.com](https://learn.chatgpt.com/docs/agent-configuration/agents-md). Cursor: правила по glob «When file matches a specified pattern» — [cursor.com](https://cursor.com/docs/context/rules)
- [В] Деградация в длинных диалогах: «LLMs Get Lost in Multi-Turn Conversation» (Laban et al., arXiv 2505.06120, ICLR 2026). Падение в среднем на 39% на шести задачах при многоходовой постановке. Главная составляющая — рост ненадёжности, модели «get lost and do not recover» — [arXiv](https://arxiv.org/abs/2505.06120v1)
- [В] IFScale: смещение в пользу ранних инструкций — [arXiv](https://arxiv.org/abs/2507.11538). [П] Chroma: деградация растёт с длиной ввода — [trychroma](https://www.trychroma.com/research/context-rot)

### Таблица механизмов (раздел 7)

| Механизм | Когда попадает в контекст | Переживает compact | Гарантия доставки | Гарантия выполнения | Данные |
|---|---|---|---|---|---|
| Корневой CLAUDE.md | старт | да | да | нет | см. разд. 4 |
| `.claude/rules` с `paths` / вложенный CLAUDE.md | при Read/Write/Edit подходящего файла | до следующего триггера — нет | да, при триггере через Read/Write/Edit; Bash-правки не триггерят [И] | нет | данных нет |
| Skill (авто) | по решению модели | тело — да (с лимитами) | нет | нет | Vercel 53% vs 79% с явной инструкцией [П] |
| Hook UserPromptSubmit → additionalContext | каждый промпт | вставляется заново | да | нет | данных нет |
| Hook PreToolUse → additionalContext (например, правила миграций при `Edit` на `migrations/**`) | перед конкретным действием | вставляется заново | да | нет | данных нет |
| Hook PreToolUse deny / exit 2 | — | — | — | да (блок) | данных нет |
| Субагент с узким промптом (ревьюер, мигратор) | на весь его контекст | свой контекст | да | нет | данных нет |
| SessionStart hook с `compact` | после compact | да | да | нет | данных нет |

### Inferences
- [И] Правила, привязанные к действию («перед миграцией сделай X»), надёжнее доставлять PreToolUse-хуком с matcher по инструменту и пути, а не path-scoped rule. Rule срабатывает на Read/Write/Edit и пропадает после compact. Хук срабатывает на каждое действие и не зависит от истории.
- [И] Цена хуков: каждое срабатывание добавляет токены, а значит, и шум (context rot). Хук-напоминание стоит делать коротким и узко нацеленным.
- [И] Свидетельство «распада инструкций» на длинной сессии косвенное: синтетические бенчмарки (multi-turn −39%, context rot). Прямых измерений «агент забыл правило из CLAUDE.md через N ходов» я не нашёл.

### Gaps
- Нет прямых измерений instruction decay у coding-агентов на длинных сессиях.
- Не проверял, триггерит ли path-scoped rule правка файла через Bash (`sed -i`). По документации триггер — только Read/Write/Edit, поэтому, скорее всего, нет [И].

---

## 8. Документация: целостность, без воды, для агента vs для человека

### Takeaway
Целостность документации гарантируют только проверки, исполняемые в CI: doc tests, link checkers, генерация OpenAPI из кода или кода из OpenAPI, проверка путей и команд. Доков, «написанных агентом», это не касается, если их не проверяют. Для документации, предназначенной агенту, данные говорят: сгенерированный LLM контекст вредит, минимальный человеческий помогает (см. разд. 4). Тот же вывод подтверждает вендор: убирать из CLAUDE.md то, что выводится из кода. Формат llms.txt широко распространяется, но почти не читается AI-краулерами (данные 2026). Для coding-агента внутри репозитория он нерелевантен.

### Cited Findings
- [П] (вендор) `/doctor` урезает CLAUDE.md: «cuts content Claude can derive from the codebase, such as directory layouts, dependency lists, and architecture overviews, and keeps pitfalls, rationale, and conventions that differ from tool defaults» — [memory](https://code.claude.com/docs/en/memory)
- [П] Gloaguen et al.: сгенерированные LLM контекст-файлы снижают успех и повышают стоимость; рекомендуются «only minimal requirements» — [arXiv](https://arxiv.org/html/2602.11988v1)
- [П] (вендор) Vercel: сжатие индекса документации на 80% без потери 100%-го результата. Документацию стоит проектировать под извлечение агентом — [vercel.com](https://vercel.com/blog/agents-md-outperforms-skills-in-our-agent-evals)
- [В] llms.txt: число сайтов выросло с 4 088 (июнь 2025) до 36 120 (май 2026), но по логам Ahrefs (137 тыс. доменов) 97% файлов не получили ни одного запроса в мае 2026. SE Ranking (~300 тыс. доменов) не нашёл связи с цитированием — [ppc.land](https://ppc.land/llms-txt-adoption-rises-8-8x-but-97-of-files-get-zero-ai-requests/); [SEJ](https://searchenginejournal.com/97-of-llms-txt-files-got-no-requests-ahrefs-data-shows/579478)
- [П] (вендор) Пример использования llms.txt как индекса для агентов: Claude Code публикует `code.claude.com/docs/llms.txt`, и каждая страница документации отсылает к нему — [llms.txt](https://code.claude.com/docs/llms.txt)
- [П] (вендор) Anthropic: «too vague» vs «too brittle»; just-in-time retrieval вместо предзагрузки — [anthropic.com](https://www.anthropic.com/engineering/effective-context-engineering-for-ai-agents)
- [И] Diátaxis (tutorials / how-to / reference / explanation) — классическая рамка для документации для людей, старше 2025: [diataxis.fr](https://diataxis.fr/) (в этой сессии не открывал). Для агента наиболее полезны reference и how-to с командами; explanation («почему») нужен и человеку, и агенту (ср. «keeps pitfalls, rationale» у `/doctor`).
- [И] Docs-as-code инструменты (doc tests в Rust, Python doctest, линк-чекеры вроде lychee, генерация OpenAPI) общеизвестны и старше 2025. Первоисточники в этой сессии не открывал, и данных об их эффективности именно в агентной разработке я не нашёл.

### Таблица механизмов (раздел 8)

| Механизм | Тип | Гарантия? | Данные |
|---|---|---|---|
| Doc tests (исполняемые примеры) | CI | гарантия, что примеры работают | данных нет (не искал) |
| Link checker / проверка путей и команд в docs и инструкциях | CI | гарантия для ссылок и путей | данных нет |
| OpenAPI генерируется из кода (или код из спеки) + diff-check в CI | CI | гарантия соответствия контракта | данных нет |
| Агент обновляет доки в том же PR (правило в CLAUDE.md) | инструкция | договорённость | данных нет |
| Stop / PostToolUse hook: «изменён публичный API → напомнить о доке» | hook | гарантия напоминания, не обновления | данных нет |
| Минимальные доки для агента (команды, подводные камни, «почему»), без выводимого из кода | инструкция | договорённость | +4% / −3% (человеческие / LLM-генерированные) [П] |
| llms.txt | артефакт | — | 97% файлов не запрашиваются [В]; для агента в репозитории неприменимо [И] |
| Разделение docs/ для людей (Diátaxis) и AGENTS.md для агента | структура | договорённость | данных нет |

### Inferences
- [И] Против «воды» работают два независимых аргумента. Эмпирический: LLM-сгенерированный контекст снижает успех. Вендорский: `/doctor` вырезает выводимое из кода. Значит, документацию, которую написал агент, нужно ревьюить на «можно ли вывести это из кода → удалить».
- [И] Для backend-сервиса самая сильная гарантия целостности — контракт (OpenAPI или схема), проверяемый в CI против кода. Остальные документы держатся на договорённостях плюс link и path-check.

### Gaps
- Эмпирических данных о влиянии doc tests и OpenAPI-генерации на качество работы агентов в 2025–2026 не нашёл.
- Diátaxis, lychee, gitleaks и прочие «классические» инструменты в этой сессии не открывал.

---

## Сомнительное / не проверено

- **Формат Linux `Assisted-by`.** Текущий docs.kernel.org [П] показывает `Assisted-by: LLM [TOOL1] [TOOL2]`, а вторичные источники ([It's FOSS](https://itsfoss.com/news/linux-ai-coding-assistants-policy/), [lilting.ch](https://lilting.ch/en/articles/linux-kernel-ai-coding-assistant-policy)) — `Assisted-by: AGENT_NAME:MODEL_VERSION [TOOL1] [TOOL2]`. Возможно, «LLM» на странице — это плейсхолдер, или формулировку меняли между версиями. Перед цитированием сверить с исходным .rst в git.
- **Vercel «56% случаев skill не вызывался».** Цифра взята из поисковой выдачи. В пересказе страницы, который я получил через WebFetch, её нет, хотя таблица 53/53/79/100 подтверждена.
- **Anthropic RCT: «≥65% у тех, кто задавал концептуальные вопросы, <40% у делегировавших».** Это из вторичной выдачи (InfoQ и другие), в основном тексте Anthropic я эти цифры не нашёл. Также в выдаче встречается «17% lower»: это разница в процентных пунктах (67→50), а в относительном выражении снижение ~25%.
- **IFScale.** Цифра 68% на 500 инструкциях и паттерны деградации взяты из выдачи, PDF я не открывал.
- **«Lost in the Middle» (2023)** и цифра «30%+ падения» — только вторичный пересказ (morphllm).
- **Amazon Q.** Даты, CVE-2025-8217 и «malformed, не угроза» взяты из прессы. Страница AWS-бюллетеня не отрендерилась.
- **Nx s1ngularity.** 2 349 секретов и перечень флагов — только из вторичных источников (Okta, OX, groundy). Postmortem nx.dev не прочитан.
- **Shai-Hulud.** «500+ пакетов» и рекомендации CISA — из пересказа (Anvilogic, ThaiCERT). Оригинал алерта CISA не открывал.
- **Slopsquatting.** Цифры (19,7%, 5,2% / 21,7%, 43%) — из пересказа CSA. Оригинал USENIX/arXiv не открывал.
- **Даты и версии cooldown** в менеджерах пакетов (npm 11.10.0 и т.д.) — из блога Nesbitt, changelog'и не сверял.
- **Beads «17.9K звёзд», «вдохновил Claude Code Tasks»** — утверждение одного вторичного источника (morphllm), не подтверждено.
- **ASF Generative Tooling guidance** (`Generated-by:`) — по памяти, не открывал.
- **agnix «399 правил»** и возможности других линтеров — из статей и маркетплейсов, сами инструменты не запускал.
- **Codex sandbox modes** — по индексной странице. Детали (сеть по умолчанию выключена, approval policies) не проверены.
- **llms.txt: Ahrefs 97% и SE Ranking** — пересказ в SEO-изданиях, первичные отчёты не открывал.
