# Блок 2. Эмпирика AI-assisted / агентной разработки (2025–2026): продуктивность, качество, стабильность, понимание кода, критика хайпа

Дата сбора: 2026-10-03. Контекст применения: один разработчик + AI-агент, новый небольшой backend-сервис, человек обязан понимать весь код.

Маркировка: **[П]** сверено с первоисточником (страница открыта мной); **[В]** вторичный источник (пресса, пересказ, поисковая сводка); **[И]** моя интерпретация. **(вендор)** — исследование компании, продающей AI-инструмент или аналитику по нему. **(старше 2025)** — источник до 2025 года.

---

## Сводная таблица исследований

| # | Исследование | Кто / когда | Дизайн | Выборка | Ключевой результат | Независимость | Применимость к solo+агент, новый маленький сервис [И] |
|---|---|---|---|---|---|---|---|
| 1 | METR, опытные OSS-разработчики | METR, июль 2025 | RCT (рандомизация на уровне задач) | 16 разработчиков, 246 задач, репо 22k+ звёзд, 1M+ строк | **на 19% медленнее** с AI; ожидали +24%, после считали +20% | независимое (некоммерческая орг.) | низкая-средняя: зрелые огромные репо, эксперты в своём коде; противоположность greenfield |
| 2 | METR, апдейт дизайна | METR, 24.02.2026 | RCT, повтор | 57 разработчиков (10 вернувшихся, 47 новых) | вернувшиеся: время −18% (ДИ −38%…+9%); новые: −4% (ДИ −15%…+9%); сильный selection bias | независимое | средняя: показывает сдвиг к ускорению, но статистически слабый |
| 3 | METR, анализ транскриптов Claude Code | METR, 17.02.2026 | наблюдательное, LLM-judge | 5 305 транскриптов, 7 сотрудников | фактор экономии времени ~1.5x–13x — «мягкая верхняя граница» | независимое, но внутреннее | средняя: близко к solo+агент, но очень смещено вверх |
| 4 | METR, SWE-bench PR vs мейнтейнеры | METR, 10.03.2026 | экспертная оценка | 296 AI-PR, 4 мейнтейнера, 3 репо | ~половина прошедших тесты PR не была бы смёржена; разрыв 24.2 п.п. | независимое | высокая: «тесты прошли» ≠ «код годный» |
| 5 | METR, algorithmic vs holistic | METR, 12.08.2025 | экспертная оценка | 15 PR Claude 3.7 Sonnet | 38% проходят тесты, **0/15 мёржабельны** без доработки; ~26–42 мин доработки | независимое | высокая |
| 6 | Google internal RCT (Paradis et al.) | Google, ICSE SEIP 2025, данные лета 2024 | RCT | 96 инженеров Google | время на задачу **−~21%**, широкий ДИ | (вендор: Google продаёт AI-инструменты; внутренние инструменты) | средняя: одна enterprise-задача, completion-эпоха (не агенты) |
| 7 | Cui, Demirer et al. (Microsoft/Accenture/Fortune 100) | SSRN 2024 → Management Science 2025 | 3 полевых RCT | 4 867 разработчиков | **+26.08%** завершённых задач (PR); джуны +27–39%, сеньоры +8–13%; качество не измерено | смешанное (соавторы из Microsoft; Copilot) | низкая: автодополнение Copilot 2023 г., большие компании |
| 8 | Stanford, Denisov-Blanch (~100k разработчиков) | доклад AI Engineer 2025 | наблюдательное, модель оценки коммитов | 100k+ инженеров, 600+ компаний; разбивка по 136 командам/27 компаниям | нетто **+15–20%** в среднем; greenfield-простые +30–40%, brownfield-сложные 0–10% | академическое, но не рецензированное (доклад) | **высокая**: greenfield, популярный язык → верхний диапазон |
| 9 | DORA 2024 | Google DORA, окт. 2024 (старше 2025) | опрос + моделирование | ~39k (по данным DORA; размер в блоге не указан) | +25% adoption → throughput −1.5%, stability −7.2% | Google (вендор частично) | средняя: командные метрики доставки |
| 10 | DORA 2025 State of AI-assisted SD + AI Capabilities Model | Google DORA, сент.–дек. 2025 | опрос + интервью | ~5 000 профессионалов, 100+ ч интервью | 90% используют AI; >80% чувствуют рост продуктивности; AI ↔ throughput теперь «+», ↔ нестабильность по-прежнему «+»; «AI — усилитель» | Google (вендор частично) | средняя: принципы (small batches, VCS, тесты) переносимы |
| 11 | GitClear AI Code Quality 2025 | GitClear, янв./фев. 2025 | анализ репозиториев (корреляционный) | 211 млн изменённых строк, 2020–2024 | дубли-блоки ×8 в 2024; moved/refactor 25%→<10% | (вендор аналитики кода) | средняя: тренд отрасли, не причинность |
| 12 | CodeRabbit AI vs Human | CodeRabbit, 17.12.2025 | анализ PR AI-ревьюером | 470 OSS PR (320 AI, 150 human) | **~1.7x** больше замечаний в AI-PR (10.83 vs 6.45) | (вендор: AI code review) | средняя; методика разметки слабая |
| 13 | Veracode GenAI Code Security | Veracode, 30.07.2025 | бенчмарк генерации | 80 задач, 100+ LLM, 4 языка | **45%** образцов с уязвимостями OWASP Top 10; Java 72%; безопасность не растёт с моделями | (вендор: AppSec) | **высокая** для backend |
| 14 | SusVibes («Is Vibe Coding Safe?») | arXiv 2512.03262, ICML 2026 | бенчмарк агентов | 200 задач, 77 CWE, Python | SWE-Agent+Claude 4 Sonnet: 61% функционально верно, **10.5% безопасно** | академическое | **высокая** |
| 15 | AIDev dataset | Li, Zhang, Hassan, арXiv 2507.15003, июль 2025 | наблюдательное, GitHub | 456k PR от 5 агентов, 61k репо | агенты быстрее, но PR принимают реже; код «структурно проще» | академическое | средняя |
| 16 | Why Agentic-PRs Get Rejected | Nakashima et al., arXiv 2602.04226, фев. 2026 | качественный анализ | 654 отклонённых PR (из 932 791) | acceptance: Codex 85.8%, Cursor 74.6%, Claude Code 71.3%, Devin 55.5%, Copilot 55.0%, human 82.6% | академическое | средняя |
| 17 | All Smoke, No Alarm (тесты агентов) | Banik et al., arXiv 2606.18168, июнь 2026 | наблюдательное | 86 156 тест-патчей из 33 596 агентных PR | **80.2%** тест-патчей без явных/со слабыми оракулами | академическое | **высокая**: «тесты есть» ≠ «проверяют» |
| 18 | SWE-Bench Illusion | arXiv 2506.12286, NeurIPS 2025 | диагностика контаминации | SWE-bench Verified vs другие | 76% угадывание файла бага только по issue vs ≤53% вне SWE-bench | академическое (часть авторов Microsoft) | косвенная: не верить бенчмаркам |
| 19 | OpenAI: отказ от SWE-bench Verified | OpenAI, фев. 2026 | аудит | 138 задач | 59.4% задач с дефектными тестами; утечка в обучение | (вендор, но против собственного маркетинга) | косвенная |
| 20 | Anthropic: AI и формирование навыков | Shen, Tamkin (Anthropic), 29.01.2026, arXiv 2601.20245 | RCT | 52 (в основном junior) | квиз **50% vs 67%** (−17 п.п.), сильнее всего — отладка; ускорение незначимо | (вендор, но результат против своего продукта) | **высокая**: «человек должен понимать весь код» |
| 21 | Anthropic: как AI меняет работу в Anthropic | Anthropic, 02.12.2025 | опрос + интервью + телеметрия | 132 опрошенных, 53 интервью, 200k транскриптов | самооценка +50% продуктивности; 0–20% задач полностью делегируемы; «парадокс надзора» | (вендор) | средняя: самооценка |
| 22 | MIT «Your Brain on ChatGPT» | Kosmyna et al., MIT Media Lab, июнь 2025 | лабораторный эксперимент + EEG | 54 (сессия 4 — 18) | у LLM-группы самая слабая связность мозга, хуже цитируют своё, ниже чувство авторства | академическое, **препринт**, про эссе, не код | низкая-косвенная |
| 23 | Microsoft/CMU, critical thinking | Lee et al., CHI 2025 | опрос | 319 работников, 936 примеров | больше доверия к GenAI → меньше критического мышления | (Microsoft Research) | косвенная |
| 24 | Stack Overflow Dev Survey 2025 | SO, июль 2025 | опрос | ~49k | 84% используют/планируют; доверяют точности 32.7%, не доверяют 45.7%; 66% «почти правильно» | независимое (SO сам вендор данных) | контекст настроений |
| 25 | JetBrains State of Dev Ecosystem 2025 | JetBrains, окт. 2025 | опрос | 24 534 | 85% регулярно используют AI; опасения: качество, непонимание сложного кода, деградация навыков | (вендор IDE/AI) | контекст |
| 26 | Fastly senior devs | Fastly, июль 2025 | опрос | 791 (США) | 32% сеньоров vs 13% джунов шипят >50% AI-кода; 28% часто правят так, что выигрыш съедается | (вендор, не AI-инструмент) | средняя |
| 27 | Faros AI Productivity Paradox 2025 / AI Engineering Report 2026 | Faros AI | телеметрия | 10k разработчиков / 22k разработчиков, 4k команд | 2025: +21% задач, +98% PR, review time +91%, PR size +154%; 2026: bugs/PR +28%, incidents/PR ×3, churn +861% | (вендор аналитики) | средняя: командные эффекты, у solo нет ревью-очереди |
| 28 | Jellyfish AI Impact | Jellyfish, июнь 2025 | телеметрия | 2.16 млн PR, 259 компаний | cycle time high-AI PR на 16% быстрее; баг-PR 8–9% без связи с adoption | (вендор аналитики) | низкая-средняя |
| 29 | Atlassian State of DevEx 2025 | Atlassian, 2025 | опрос | 3 500 | 68% экономят >10 ч/нед с AI, 50% теряют >10 ч/нед на трение; кодинг — 16% времени | (вендор) | низкая |
| 30 | Uplevel | Uplevel Data Labs, сент. 2024 (старше 2025) | телеметрия, квази-эксперимент | ~800 разработчиков | Copilot: без улучшения cycle time/throughput, +41% багов | (вендор аналитики) | низкая |
| 31 | GitHub: Copilot и качество кода | GitHub, нояб. 2024 (старше 2025) | RCT | 202 разработчика | +53.2% вероятность пройти все 10 тестов; читаемость +3.62% | **(вендор)** | низкая: одна задача (API-эндпоинты), спонсор = продавец |
| 32 | Prather et al. «The Widening Gap» | ICER 2024 (старше 2025) | качественное, eye tracking | 21 студент | слабые студенты получают «иллюзию компетентности» | академическое | косвенная |
| 33 | Pearce «Asleep at the Keyboard»; Perry et al. | 2021–2023 (старше 2025) | бенчмарк; user study | 89 сценариев; ~47 участников | ~40% программ Copilot уязвимы; с ассистентом код менее безопасен, но уверенность выше | академическое | исторический фон |

---

## Вопрос 1. Продуктивность: ускоряет ли AI разработку на самом деле?

### Takeaway
Независимые RCT дают разброс от −19% (опытные разработчики в своих огромных OSS-репо, начало 2025) до +21–26% (enterprise-задачи, автодополнение); к 2026 METR видит вероятный сдвиг к ускорению, но статистически слабо. Самый релевантный для solo+greenfield источник (Stanford) показывает наибольший выигрыш (+30–40%) именно на простых greenfield-задачах и падение до 0–10% на сложных brownfield; самооценка ускорения систематически завышена.

### Cited Findings

**METR RCT, июль 2025 (независимое)**
- [П] 16 опытных разработчиков крупных open-source репозиториев (22k+ звёзд, 1M+ строк), 246 задач (баги, фичи, рефакторинг) средней длительностью ~2 ч; задачи рандомизированы на «AI разрешён/запрещён»; инструменты — в основном Cursor Pro с Claude 3.5/3.7 Sonnet; оплата $150/ч — [METR blog, 10.07.2025](https://metr.org/blog/2025-07-10-early-2025-ai-experienced-os-dev-study/)
- [П] Результат: с AI задачи занимали **на 19% дольше**; разработчики ожидали ускорения на 24%, а после эксперимента считали, что ускорились на 20% — [METR](https://metr.org/blog/2025-07-10-early-2025-ai-experienced-os-dev-study/)
- [П] METR явно НЕ утверждает, что AI не помогает большинству разработчиков, что результат обобщается за пределы OSS, что будущие модели не ускорят, или что AI нельзя использовать эффективнее: «We view this result as a snapshot of early-2025 AI capabilities in one relevant setting» — [METR](https://metr.org/blog/2025-07-10-early-2025-ai-experienced-os-dev-study/)
- [П] Sean Goedecke (позитивная оценка дизайна): реальные SOTA-инструменты, реальные задачи; главный caveat — разработчики с ~5 годами и ~1 500 коммитами в своих репо и так очень быстры на обычных задачах; репо в основном библиотеки/компиляторы («pure software») с высокой планкой качества; AI, вероятно, помогает больше в незнакомой территории — [seangoedecke.com, 11.07.2025](https://www.seangoedecke.com/impact-of-ai-study/)

**METR, обновление 2026 (независимое)**
- [П] Повторное исследование: 57 разработчиков (10 вернувшихся + 47 новых), оплата снижена с $150 до $50/ч. Оценка изменения времени: вернувшиеся −18% (ДИ −38%…+9%), новые −4% (ДИ −15%…+9%). Оба ДИ включают ноль — [METR, 24.02.2026](https://metr.org/blog/2026-02-24-uplift-update/)
  - [И] Знак: METR выражает эффект как изменение времени выполнения, т.е. −18% ≈ на 18% быстрее; вторичные пересказы так и трактуют ([блог Rob Bowley](https://blog.robbowley.net/2026/04/04/metrs-developer-productivity-research-2026-update/)). Однако на первоисточнике формулировка была «speedup −18%», знак стоит перепроверить перед цитированием.
- [П] Сильный selection bias: «30% to 50% of developers told us that they were choosing not to submit some tasks because they did not want to do them without AI»; часть разработчиков отказалась участвовать, не желая работать без AI. METR: данные — «only very weak evidence for the size of this increase» — [METR](https://metr.org/blog/2026-02-24-uplift-update/)
- [П] METR меняет дизайн: более короткие интенсивные эксперименты, наблюдательные данные, опросы, фиксированные задачи, рандомизация на уровне разработчика — [METR](https://metr.org/blog/2026-02-24-uplift-update/)

**METR, анализ транскриптов Claude Code, фев. 2026**
- [П] 5 305 транскриптов Claude Code от 7 сотрудников METR (январь 2026); LLM-judge оценивал время без AI (валидация на 34 человеческих оценках, r_log=0.83). Фактор экономии времени ~1.5x–13x. Авторы называют это «a soft upper bound»: подмена задач (делают низкоценные задачи, которых без AI не делали бы), отбор задач, специализация сотрудников. Больше параллельных агентов ↔ больше экономия (лучший — в среднем 2.32 агента одновременно) — [METR notes, 17.02.2026](https://metr.org/notes/2026-02-17-exploratory-transcript-analysis-for-estimating-time-savings-from-coding-agents/)

**Google internal RCT (вендор-частично)**
- [П] Paradis et al., 96 инженеров Google, сложная enterprise-задача; AI сократил время примерно на 21%, «our confidence interval is large»; авторы предупреждают, что эффект на внутренних инструментах лета 2024 может не переноситься на другие инструменты и время — [arXiv 2410.12944](https://arxiv.org/abs/2410.12944v3)
- [В] Три функции: code completion, smart paste, NL→code; задача — сервис логирования, правка 10 файлов, 474 строки; ICSE 2025 SEIP; разработчики, больше кодящие в день, ускорялись сильнее — [поисковая сводка / ICSE](https://conf.researchr.org/details/icse-2025/icse-2025-software-engineering-in-practice/26/How-much-does-AI-impact-development-speed-An-enterprise-based-randomized-controlled-)

**Cui, Demirer et al. — три полевых эксперимента (Microsoft, Accenture, Fortune 100)**
- [В] Объединённые данные 3 экспериментов, 4 867 разработчиков: **+26.08%** завершённых задач у группы с доступом к Copilot; опубликовано в Management Science (DOI 10.1287/mnsc.2025.00535) — [SSRN 4945566](https://papers.ssrn.com/abstract=4945566) (SSRN вернул 403, сверено по [MIT Sloan](https://mitsloan.mit.edu/ideas-made-to-matter/how-generative-ai-affects-highly-skilled-workers))
- [П] Джуны/новички +27–39%, сеньоры +8–13%; длительность: Microsoft 7 мес., Accenture 4 мес., анонимная компания 2 мес.; adoption ~60% даже через год; исследователи «were unable to evaluate the quality of the work produced with Copilot» — [MIT Sloan](https://mitsloan.mit.edu/ideas-made-to-matter/how-generative-ai-affects-highly-skilled-workers)
- [И] Метрика — количество PR/задач, не ценность и не качество; инструмент — автодополнение 2023 года, не агенты. Часть соавторов — сотрудники Microsoft (владельца GitHub).

**Stanford, Yegor Denisov-Blanch (~100k разработчиков)**
- [П] 100k+ инженеров, 600+ компаний, в основном приватные репо; модель, обученная на оценках панели из 10–15 экспертов, оценивает «функциональность, доставленную во времени», выделяя rework/refactor. Видимый рост выхода 30–40%, нетто после rework и багов **15–20%** — [ai.engineer talk](https://www.ai.engineer/talks/tbDDYKRFjhk-ai-developer-productivity)
- [П] По 136 командам / 27 компаниям: low-complexity greenfield 30–40%; high-complexity greenfield 10–15%; low-complexity brownfield 15–20%; high-complexity brownfield 0–10%. Популярные языки (Python, Java, JS, TS) — выше; редкие (COBOL, Haskell, Elixir) — может замедлять. Чем больше кодовая база, тем меньше выигрыш. Самооценки расходятся с измеренным на ~30 перцентильных пунктов — [ai.engineer](https://www.ai.engineer/talks/tbDDYKRFjhk-ai-developer-productivity)
- [И] Ограничения: доклад, не рецензированная статья; модель оценки — проприетарная; «AI-использование» определяется на уровне команды/периода, не причинно. Тем не менее это самый прямой ориентир для «новый маленький сервис на Python/TS»: ожидаемый выигрыш — верхний диапазон, но падает по мере роста кодовой базы.

**Вендорские телеметрии**
- [П] (вендор) Faros AI 2025: 1 255 команд, 10k+ разработчиков; индивидуальные выигрыши «often stall before reaching the organizational level» — [faros.ai/research](https://faros.ai/research). [В] Детали: команды с высоким adoption +21% задач, +98% смёрженных PR, но review time +91%, PR size +154%, баги +9% на разработчика, без улучшения DORA-метрик на уровне компании — [Augment Code guide](https://www.augmentcode.com/guides/ai-productivity-paradox-engineering-delivery)
- [П] (вендор) Faros AI Engineering Report Q2 2026: 22 000 разработчиков, 4 000 команд, 2 года телеметрии: tasks throughput +33.7%, epics +66.2%, PR merge rate +16.2%, но deployments/нед −11.7%, PR size +51%, bugs/PR +28%, bugs/developer +54%, incidents/PR +242.7% (≈×3), median review time ×5, code churn +861% — [faros.ai/research/ai-acceleration-whiplash](https://faros.ai/research/ai-acceleration-whiplash)
- [П] (вендор) Jellyfish: 2.16 млн смёрженных PR, 259 компаний, 21 209 инженеров, только пользователи Copilot, июнь 2024 — июнь 2025: доля PR с AI 14%→51.5%; high-AI PR на 16% быстрее по cycle time (95.5→83.8 ч); доля баг-PR 8–9% без связи с уровнем adoption — [Jellyfish blog](https://jellyfish.co/blog/ai-impact-data-june-2025/)
- [П] (вендор) Atlassian State of DevEx 2025: опрос 3 500 разработчиков и менеджеров в 6 странах; 99% сообщают об экономии времени, 68% — >10 ч/нед; 50% теряют >10 ч/нед на организационное трение; кодинг — лишь 16% времени — [Atlassian](https://www.atlassian.com/blog/developer/developer-experience-report-2025)
- [В] (вендор) Uplevel (2024, старше 2025): ~800 разработчиков, Copilot не улучшил cycle time и throughput; +41% багов в PR — [DevOps.com](https://devops.com/study-finds-no-devops-productivity-gains-from-generative-ai/)

**Вендорские исследования самих продавцов AI**
- [П] (вендор) Anthropic, внутреннее (авг. 2025, опубл. 02.12.2025): 132 опрошенных, 53 интервью, 200k транскриптов Claude Code; доля работы с Claude 28%→59%, **самооценка** продуктивности +20%→+50%; полностью делегируемы 0–20% задач; 27% AI-работы — задачи, которые иначе не делались бы; автономность Claude Code: число подряд идущих действий 9.8→21.2, человеческих ходов 6.2→4.1 — [Anthropic](https://www.anthropic.com/research/how-ai-is-transforming-work-at-anthropic)
- [В] (вендор, старше 2025) GitHub RCT 2024: 202 разработчика с 5+ годами опыта, задача — API-эндпоинты; с Copilot на 53.2% выше шанс пройти все 10 юнит-тестов (61% vs 39%); читаемость +3.62%, надёжность +2.94% — [GitHub blog](https://github.blog/news-insights/research/does-github-copilot-improve-code-quality-heres-what-the-data-says/)

**Критика хайпа «10x»**
- [П] Colton Voege (через Simon Willison): «AI helps many engineers do certain tasks 20-50% faster, but the nature of software bottlenecks mean this doesn't translate to a 20% productivity increase and certainly not a 10x increase.» Willison: у него AI ускоряет именно кодинг в 2–5 раз, но это лишь часть работы — [simonwillison.net, 06.08.2025](https://simonwillison.net/2025/Aug/6/not-10x); оригинал — [colton.dev](https://colton.dev/blog/curing-your-ai-10x-engineer-imposter-syndrome/)

### Inferences
- [И] Общая картина: «AI ускоряет набор кода, но не обязательно доставку». Где узкое место — ревью, понимание, интеграция — выигрыш съедается (Atlassian: кодинг — 16% времени; Faros: review time ×2–×5).
- [И] Для solo+агент на новом маленьком сервисе обстоятельства благоприятнее всего (greenfield, малая кодовая база, популярный язык, нет очереди ревью у других людей) → разумно ожидать реального ускорения, но (а) оно падает по мере роста кода, (б) самооценка ненадёжна — нужно мерить (время до рабочего, проверенного изменения), а не «ощущение».
- [И] Узкое место solo-разработчика — его собственное ревью и понимание кода агента; именно туда переезжает «review time ×N» из командных телеметрий.
- [И] METR 2026 и транскрипты METR — сигнал, что с агентами 2026 года эффект, вероятно, положительный, но чистой оценки нет: лучшие независимые данные статистически неубедительны.

### Gaps
- Нет независимого RCT конкретно для «один разработчик + агент, greenfield-сервис» с измерением качества и понимания.
- Не удалось открыть полный PDF DORA 2025, полный текст Cui et al. (SSRN 403) и отчёт GitClear (403) — часть цифр [В].
- Знак/формулировку эффекта METR 2026 стоит перепроверить по PDF ([копия](https://modern-genai-se.github.io/f2026/assets/paperPDFs/becker-metr-design-update.pdf)).

---

## Вопрос 2. Стабильность и доставка (DORA)

### Takeaway
DORA 2024: рост AI-adoption ассоциирован со снижением throughput (−1.5%) и стабильности (−7.2%). DORA 2025: связь с throughput стала положительной, а с нестабильностью осталась; главный тезис — «AI — усилитель» существующих практик; эффект зависит от small batches, VCS-дисциплины, автотестов. Отдельного отчёта DORA 2026 я не нашёл.

### Cited Findings
- [П] DORA 2024 (старше 2025): >75% используют AI хотя бы для одной ежедневной задачи; >33% отмечают умеренный/сильный рост продуктивности; +25% adoption → документация +7.5%, качество кода +3.4%, скорость ревью +3.1%, **но throughput −1.5%, стабильность −7.2%**; 39% мало или совсем не доверяют AI-коду — [Google Cloud blog](https://cloud.google.com/blog/products/devops-sre/announcing-the-2024-dora-report)
- [В] Объяснение DORA 2024: с AI растёт размер батча изменений, а крупные changesets рискованнее — [DX newsletter / Laura Tacho](https://getdx.com/blog/2024-dora-report-summary-laura-tacho/); [cusy](https://cusy.io/en/blog/dora-report-2024)
- [П] DORA 2025: почти 5 000 респондентов + 100+ часов качественных данных; 90% используют AI на работе; >80% считают, что AI повысил продуктивность; 30% мало/не доверяют AI-коду; «AI doesn't fix a team; it amplifies what's already there»; «Without robust control systems, like strong automated testing, mature version control practices, and fast feedback loops, an increase in change volume leads to instability» — [Google Cloud blog](https://cloud.google.com/blog/products/ai-machine-learning/announcing-the-2025-dora-report)
- [В] DORA 2025: связь AI с throughput сменилась с отрицательной на положительную, связь с нестабильностью сохраняется («AI adoption not only fails to fix instability, it is currently associated with increasing instability») — [cusy](https://cusy.io/en/blog/dora-report-2025.html); [RedMonk](https://redmonk.com/rstephens/2025/12/18/dora2025/)
- [П] DORA AI Capabilities Model (24.09.2025), 7 способностей-усилителей: (1) clear and communicated AI stance; (2) healthy data ecosystems; (3) AI-accessible internal data; (4) strong version control practices — частые коммиты усиливают индивидуальную эффективность, частое использование rollback улучшает работу AI-команд; (5) **working in small batches** — усиливает эффект на продукт и снижает трение; (6) user-centric focus — без него AI может давать чистый минус для команды; (7) quality internal platforms — [Google Cloud blog](https://cloud.google.com/blog/products/ai-machine-learning/introducing-doras-inaugural-ai-capabilities-model); [techstrong.ai](https://techstrong.ai/features/ai-doesnt-fix-whats-already-broken-what-doras-new-model-tells-us-about-getting-ai-right/)
- [П] В 2025 DORA выпустил три отчёта: Impact of GenAI (март), State of AI-assisted SD (сент.), AI Capabilities Model (дек.) — [DORA 2025 year in review](https://dora.dev/insights/dora-2025-year-in-review/)

### Inferences
- [И] Для solo-проекта переносимы капабилити 4 и 5: маленькие коммиты/изменения от агента, частые коммиты как точки отката, обязательный быстрый автотест-контур. Остальные (платформы, data ecosystems, AI stance) — организационные и к одиночке почти неприменимы.
- [И] Механизм нестабильности (больше объём изменений → больше батч → больше риск) действует и у одиночки: агент легко генерирует большой diff; дисциплина «маленький diff, который я прочитал» — прямая контрмера.
- [И] DORA — опросы с самооценкой метрик доставки; это корреляции, не причинность.

### Gaps
- Отчёта DORA 2026 в открытом доступе не нашёл (на 2026-10-03); полный PDF DORA 2025 не открыт — числовые коэффициенты 2025 года по throughput/instability не проверены.

---

## Вопрос 3. Качество и безопасность кода, PR агентов, бенчмарки, тесты

### Takeaway
Почти все источники (и независимые, и вендорские) сходятся: AI-код чаще содержит дубли, логические ошибки, проблемы обработки ошибок и уязвимости; «проходит тесты» сильно хуже предсказывает «годен к merge» (разрыв ~24 п.п., половина прошедших тесты PR отклоняется мейнтейнерами); тесты, которые пишут агенты, в 80% случаев слабо что-то проверяют; безопасность сгенерированного кода не растёт вместе с размером моделей.

### Cited Findings

**Дубли/churn (GitClear, вендор)**
- [В] (вендор) 211 млн изменённых строк, янв. 2020 — дек. 2024; в 2024 частота дублированных блоков (5+ строк) выросла ×8; доля moved/refactored строк упала с ~25% (2021) до <10% (2024); 2024 — первый год, когда copy/paste превысил moved — [i-programmer](https://www.i-programmer.info/news/105-artificial-intelligence/17871-gitclear-reveals-ais-negative-impact-on-code-quality.html); [TechCrunch](https://techcrunch.com/2025/02/21/report-ai-coding-assistants-arent-a-panacea) (страница GitClear вернула 403)
- [И] Корреляция с эпохой AI, а не атрибуция конкретных строк AI; GitClear продаёт аналитику кода.

**CodeRabbit (вендор AI-ревью)**
- [П] 470 OSS PR (320 «AI-co-authored», 150 «human-only»); AI-PR: 10.83 замечания на PR vs 6.45 (~1.7x); логика/корректность +75%; читаемость >3x; обработка ошибок ~2x; безопасность до 2.74x; форматирование 2.66x. Ограничение: авторство определялось по сигналам; «We cannot guarantee all the PRs we labelled as human authored were actually authored only by humans» — [CodeRabbit, 17.12.2025](https://coderabbit.ai/blog/state-of-ai-vs-human-code-generation-report)
- [И] Замечания генерировал сам AI-ревьюер CodeRabbit — круговая методика; маленькая выборка.

**Безопасность**
- [П] (вендор) Veracode 2025: 100+ LLM, Java/Python/C#/JS, проверка на OWASP Top 10; 45% образцов провалили security-тесты; Java 72%, C# 45%, JS 43%, Python 38%; XSS (CWE-80) не защищён в 86% случаев; «While the models got better at writing functional or syntactically correct code, they were no better at writing secure code. Security performance remained flat, regardless of model size» — [Veracode](https://www.veracode.com/blog/genai-code-security-report/)
- [В] Veracode: 80 задач; log injection (CWE-117) не защищён в 88% — [Veracode press release](https://www.veracode.com/press-release/ai-generated-code-poses-major-security-risks-in-nearly-half-of-all-development-tasks-veracode-research-reveals/)
- [В] SusVibes (arXiv 2512.03262, ICML 2026): 200 feature-request задач из реальных Python-репо, 77 CWE; SWE-Agent + Claude 4 Sonnet: 61% функционально верны, только 10.5% безопасны; >80% функционально верных решений уязвимы; подсказки об уязвимостях в запросе не помогают — [arXiv 2512.03262](https://arxiv.org/html/2512.03262v2)
- [В] (старше 2025) Pearce et al. «Asleep at the Keyboard» (2021): 89 сценариев, ~40% программ Copilot потенциально уязвимы — [NYU](https://engineering.nyu.edu/news/its-gpt-3-code-fun-fast-and-full-flaws); Perry et al. (Stanford, 2022–23): участники с ассистентом писали менее безопасный код и при этом чаще считали его безопасным — [arXiv 2211.03622](https://webcf.waybackmachine.org/web/20221113074609/https://arxiv.org/pdf/2211.03622.pdf)

**PR агентов в реальных репозиториях**
- [П] AIDev (Li, Zhang, Hassan, июль 2025): 456k+ PR от Codex, Devin, Copilot, Cursor, Claude Code в 61k репо; агенты быстрее, но «their PRs are accepted less frequently»; код агентов «structurally simpler» — [arXiv 2507.15003](https://arxiv.org/abs/2507.15003)
- [П] Nakashima et al. (фев. 2026), AIDev 932 791 агентных PR (янв.–авг. 2025): acceptance Codex 85.8%, Cursor 74.6%, Claude Code 71.3%, Devin 55.5%, Copilot 55.0%, люди 82.6%; 67.9% отклонённых PR без внятной причины; агент-специфичные причины: «слишком большой PR», «нет доверия к AI-коду», «эксперимент» — [arXiv 2602.04226](https://arxiv.org/html/2602.04226v1)
- [И] Высокая acceptance Codex, скорее всего, артефакт: многие Codex-PR открываются в собственных репо автора после локального цикла — сравнение агентов «в лоб» некорректно.

**Тесты, написанные агентами**
- [П] Banik et al., «All Smoke, No Alarm» (16.06.2026): 86 156 тест-патчей из 33 596 агентных PR, 2 807 репо, 5 агентов; **80.2% тест-патчей содержат слабые или никаких явных оракулов** (тест выполняет код, но не проверяет поведение); сильные оракулы повышают шанс merge (OR=1.28, p<0.001) — [arXiv 2606.18168](https://arxiv.org/abs/2606.18168)

**Тесты прошли ≠ годно (METR)**
- [П] METR, авг. 2025: Claude 3.7 Sonnet — 38% (±19%) успеха по тестам, но 0 из 15 вручную проверенных PR мёржабельны без существенной доработки; доработка ~26 мин для прошедших тесты и ~42 мин в среднем (≈ треть исходного времени мейнтейнера 1.3 ч); провалы: покрытие тестами 91–100%, документация 75–89%, lint/format/typing 73–75%, качество кода 50–64% — [METR](https://metr.org/blog/2025-08-12-research-update-towards-reconciling-slowdown-with-time-horizons/)
- [П] METR, 10.03.2026: 4 мейнтейнера 3 репо (scikit-learn, Sphinx, pytest) оценили 296 AI-PR; автогрейдер в среднем на 24.2 п.п. выше решения мейнтейнеров; ~половина прошедших тесты PR (агенты с середины 2024 до конца 2025) не была бы смёржена; даже исходные человеческие golden-патчи смёржены лишь в 68%; причины: качество кода, поломка другого кода, неполное решение. Оговорка: у агентов одна попытка без обратной связи — [METR notes](https://metr.org/notes/2026-03-10-many-swe-bench-passing-prs-would-not-be-merged-into-main/)

**Критика SWE-bench**
- [В] «The SWE-Bench Illusion» (NeurIPS 2025): модели угадывают путь к файлу бага только по тексту issue с точностью до 76% на SWE-bench Verified против ≤53% на репо вне бенчмарка; дословное воспроизведение (5-gram) до 35% vs ≤18% на других бенчмарках → признаки запоминания — [arXiv 2506.12286](https://arxiv.org/html/2506.12286v4)
- [В] OpenAI (фев. 2026) прекратила отчитываться по SWE-bench Verified: аудит 138 задач — 59.4% с дефектными тестами (35.5% требуют имён функций, которых нет в задаче; 18.8% проверяют посторонние фичи); модели воспроизводят точные фиксы (утечка в обучение); рекомендует SWE-bench Pro, где модели с ~70% на Verified получают ~23% — [OpenAI](https://openai.com/index/why-we-no-longer-evaluate-swe-bench-verified/) (страница вернула 403, цифры по [aiweekly](https://aiweekly.co/node/5874) и поисковой сводке)
- [В] METR Time Horizon 1.1 (янв. 2026): набор расширен с 170 до 228 задач; время удвоения горизонта ~188 дней за весь период, ~129 дней (ДИ 105–157) с 2023 г.; набор не меряет надёжно горизонты >16 ч — [evals.alignment.org](https://evals.alignment.org/blog/2026-1-29-time-horizon-1-1/); [Anatol Wegner](https://buttondown.com/anatol/archive/are-ai-time-horizons-still-doubling/)
- [И] Time horizon — длина задач, которые агент решает с 50% успехом по *алгоритмическим* критериям; с учётом разрыва «тесты vs merge» реальная автономность ниже.

### Inferences
- [И] Для backend-сервиса: безопасность — самый жёсткий риск; ни модели, ни подсказки в промпте его не закрывают (Veracode, SusVibes) → нужны детерминированные проверки (SAST, линтеры безопасности, ревью на CWE: инъекции, XSS, логирование ввода).
- [И] Тесты от агента надо ревьюить на наличие осмысленных утверждений (оракулов) — «зелёный CI» от агентских тестов слабый сигнал. Полезно писать/формулировать ключевые проверки поведения самому до генерации реализации.
- [И] Дубли и отсутствие рефакторинга (GitClear) — риск, который маленький проект ощутит при росте; явные шаги рефакторинга нужно планировать.

### Gaps
- Нет независимых измерений дефектности кода агентов последних поколений (конец 2025 — 2026) в greenfield-проектах одиночек.
- GitClear отчёта за 2026 не нашёл (не искал отдельно — время); первоисточники GitClear и OpenAI не открылись (403).

---

## Вопрос 4. Опросы разработчиков: использование и доверие

### Takeaway
Использование AI почти всеобщее (84–90%), но доверие к точности падает: в SO 2025 недоверяющих (≈46%) больше, чем доверяющих (≈33%); главная боль — «почти правильные» решения и долгая отладка AI-кода. Сеньоры шипят больше AI-кода и больше его правят.

### Cited Findings
- [П] Stack Overflow 2025 (~49k ответов): 84% используют или планируют AI (2024: 76%); 51% профессионалов — ежедневно; доверие к точности: высокое 3.1%, некоторое 29.6%, некоторое недоверие 26.1%, сильное недоверие 19.6%; позитивное отношение упало с 70%+ (2023–24) до 60%; 66% — «AI solutions that are almost right, but not quite»; 45.2% — отладка AI-кода дольше; агенты ежедневно используют 14.1%, 37.9% не планируют; 72.2% не занимаются vibe coding; 75.3% пойдут к человеку, «когда не доверяют ответу AI» — [survey.stackoverflow.co/2025/ai](https://survey.stackoverflow.co/2025/ai)
- [В] SO 2025: опрос 29.05–23.06.2025, 49 009 ответов из 166 стран; доверие к точности упало до 29% с 40% — [InfoWorld](https://infoworld.com/article/4031673/ai-use-among-software-developers-grows-but-trust-remains-an-issue-stack-overflow-survey.html). [И] «29% vs 33%» — расхождение вторичных источников, вероятно, из-за разных базовых выборок (все vs профессионалы); опираться на первоисточник: 3.1%+29.6%=32.7% доверяют.
- [П] (вендор) JetBrains 2025: 24 534 разработчика, 194 страны, апр.–июнь 2025; 85% регулярно используют AI; 62% — хотя бы одного ассистента/агента; ~9 из 10 экономят ≥1 ч/нед, каждый пятый ≥8 ч; топ-опасения: нестабильное качество, «limited understanding of complex code and logic», приватность/безопасность, **негативное влияние на собственные навыки**, нехватка контекста — [JetBrains Research blog](https://blog.jetbrains.com/research/2025/10/state-of-developer-ecosystem-2025/)
- [П] Fastly (июль 2025, 791 разработчик США): >50% отгружаемого кода — AI у 32% сеньоров (10+ лет) vs 13% джунов; «значительно быстрее» — 26% сеньоров vs 13% джунов; 28% часто правят AI-вывод настолько, что выигрыш времени съедается; лишь 14% редко правят — [Fastly blog](https://www.fastly.com/blog/senior-developers-ship-more-ai-code)
- [П] DORA 2025: 30% мало/не доверяют AI-коду (DORA 2024: 39%) — [Google Cloud](https://cloud.google.com/blog/products/ai-machine-learning/announcing-the-2025-dora-report); [2024](https://cloud.google.com/blog/products/devops-sre/announcing-the-2024-dora-report)

### Inferences
- [И] Опросы — самооценка; они измеряют настроения, а не эффект. Полезный сигнал для брифа: «почти правильно» и «отладка дольше» — это ровно та цена, которую платит человек, обязанный понимать весь код.
- [И] Fastly согласуется с Cui et al. и Anthropic-RCT: опыт определяет, кто выигрывает — опытный человек способен быстро отличить «почти правильно» от правильного.

### Gaps
- Stack Overflow 2026 / JetBrains 2026 не искал отдельно — могли выйти (SO обычно публикует в июле).

---

## Вопрос 5. Понимание кода и навыки человека (comprehension, deskilling)

### Takeaway
Самое прямое доказательство — RCT Anthropic (янв. 2026): при изучении новой библиотеки с AI понимание ниже на 17 п.п. (50% vs 67%), особенно отладка, а ускорение статистически незначимо; при этом паттерн использования решает — вопросы на понимание сохраняют обучение, делегирование его разрушает. Остальное (MIT EEG, Microsoft/CMU) — косвенные свидетельства не про код или на самооценке.

### Cited Findings
- [П] (вендор, но против собственного интереса) Shen & Tamkin, Anthropic, 29.01.2026, [arXiv 2601.20245](https://arxiv.org/abs/2601.20245): RCT, 52 преимущественно junior-инженера, задача — две фичи на незнакомой async-библиотеке Trio. Квиз: AI-группа **50%**, без AI **67%**; больше всего разрыв на вопросах по **отладке**; AI-группа закончила ~на 2 мин быстрее — незначимо — [Anthropic](https://www.anthropic.com/research/AI-assistance-coding-skills)
- [П] Паттерны с высоким баллом (≥65%): generation-then-comprehension (n=2) — сгенерировал, затем расспросил; hybrid code-explanation (n=3) — код вместе с объяснениями; conceptual inquiry (n=7) — только концептуальные вопросы, ошибки исправлял сам. Низкий балл (<40%): полное делегирование (n=4), progressive reliance (n=4), iterative debugging через AI (n=4). Ограничения: малая выборка, измерено только немедленное понимание — [Anthropic](https://www.anthropic.com/research/AI-assistance-coding-skills)
- [П] (вендор) Anthropic internal: «paradox of supervision» — для надзора за Claude нужны именно те навыки, которые атрофируются при делегировании; инженеры теряют попутное обучение — [Anthropic, 02.12.2025](https://www.anthropic.com/research/how-ai-is-transforming-work-at-anthropic)
- [П] Addy Osmani, «Comprehension debt» (14.03.2026): «the growing gap between how much code exists in your system and how much of it any human being genuinely understands»; в отличие от техдолга «breeds false confidence» — тесты зелёные, PR выглядят чисто; рекомендации: понимание как непреложное требование, явно формулировать желаемое поведение до реализации, держать системную ментальную модель, различать «тесты прошли» и «понимаю» — [addyosmani.com](https://addyosmani.com/blog/comprehension-debt/); также [O'Reilly Radar](https://www.oreilly.com/radar/comprehension-debt-the-hidden-cost-of-ai-generated-code/)
- [В] Osmani цитирует Margaret-Anne Storey: студенческая команда на 7-й неделе не могла объяснить, почему приняты решения — «The theory of the system had evaporated» — [Medium/Osmani](https://medium.com/@addyosmani/comprehension-debt-the-hidden-cost-of-ai-generated-code-285a25dac57e)
- [П] MIT «Your Brain on ChatGPT» (Kosmyna et al.): **препринт arXiv, 10.06.2025, не рецензирован; задача — написание эссе, не код**; 54 участника в 3 группах (LLM / поисковик / только мозг), 18 вернулись на 4-ю сессию; у «только мозг» самые сильные и распределённые сети по EEG, у LLM — самые слабые; LLM-пользователи хуже цитировали собственные эссе; ощущение авторства минимально у LLM-группы — [MIT Media Lab](https://www.media.mit.edu/publications/your-brain-on-chatgpt/)
- [В] В 4-й сессии перешедшие LLM→«мозг» показали сниженную alpha/beta-связность (недововлечённость) — [поисковая сводка / brainonllm.com](https://www.brainonllm.com/)
- [П] Lee et al. (Microsoft Research + CMU), CHI 2025: опрос 319 работников умственного труда, 936 примеров; «higher confidence in GenAI is associated with less critical thinking, while higher self-confidence is associated with more critical thinking»; критическое мышление смещается к верификации, интеграции ответа и «task stewardship» — [Microsoft Research](https://www.microsoft.com/en-us/research/publication/the-impact-of-generative-ai-on-critical-thinking-self-reported-reductions-in-cognitive-effort-and-confidence-effects-from-a-survey-of-knowledge-workers/)
- [В] (старше 2025) Prather et al., ICER 2024, «The Widening Gap»: 21 лабораторная сессия с новичками (наблюдение, интервью, eye tracking); слабые студенты с GenAI получали «иллюзию компетентности», новые метакогнитивные трудности; сильные ускорялись, используя AI для кода, который и так собирались написать — [arXiv 2405.17739](https://arxiv.org/abs/2405.17739v1)
- [П] Perry et al. (старше 2025): с ассистентом уверенность в безопасности кода выше, а безопасность — ниже (см. Вопрос 3) — тот же эффект «ложной уверенности».
- [П] JetBrains 2025: «негативное влияние на собственные навыки» — в топ-5 опасений разработчиков — [JetBrains](https://blog.jetbrains.com/research/2025/10/state-of-developer-ecosystem-2025/)

### Inferences
- [И] Для требования «человек понимает весь код» Anthropic-RCT — ключевой аргумент: режим «агент пишет, я принимаю» снижает понимание, особенно навык отладки, без значимого выигрыша по времени при обучении. Режимы, сохраняющие понимание: просить объяснений, задавать концептуальные вопросы, после генерации разбирать код, отлаживать самому.
- [И] Переносимость: исследование про изучение новой библиотеки джунами; для опытного разработчика в знакомом стеке эффект может быть слабее, но «paradox of supervision» и comprehension debt описывают тот же механизм у опытных.
- [И] MIT-исследование корректно цитировать только как косвенное (эссе, малая выборка, препринт); вирусные пересказы «ChatGPT разрушает мозг» — преувеличение.

### Gaps
- Нет долгосрочных (месяцы) исследований деградации навыков профессиональных разработчиков при работе с агентами.
- Нет исследований «понимания» кодовой базы, полностью написанной агентом, её автором-человеком.

---

## Вопрос 6. Что реально работает vs хайп; критика тяжёлых процессов

### Takeaway
Свидетельства сходятся на нескольких устойчивых практиках: маленькие изменения/батчи, сильная автоматическая проверка (но не только тесты от агента), частые коммиты и откаты, активное (а не делегирующее) использование AI для сохранения понимания, детерминированные проверки безопасности. Заявления о «10x» и бенчмарк-оптимизм не подтверждаются независимыми данными.

### Cited Findings
- [П] DORA: small batches, strong version control (частые коммиты, rollback), качественная платформа и user-centric focus — способности, усиливающие пользу AI — [Google Cloud](https://cloud.google.com/blog/products/ai-machine-learning/introducing-doras-inaugural-ai-capabilities-model)
- [П] Причина отклонения агентных PR «too large» встречается только у агентов — [arXiv 2602.04226](https://arxiv.org/html/2602.04226v1); у Faros PR size +51…154% при росте review time — [Faros](https://faros.ai/research/ai-acceleration-whiplash)
- [П] Сильные оракулы в тестах коррелируют с принятием PR (OR 1.28) — [arXiv 2606.18168](https://arxiv.org/abs/2606.18168)
- [П] Паттерны «генерация + разбор», «концептуальные вопросы» сохраняют понимание — [Anthropic](https://www.anthropic.com/research/AI-assistance-coding-skills)
- [П] Наибольший выигрыш — greenfield, низкая сложность, популярный язык; падает с ростом кодовой базы — [Stanford talk](https://www.ai.engineer/talks/tbDDYKRFjhk-ai-developer-productivity)
- [П] «10x» не подтверждается: узкие места не в наборе кода — [Willison/Voege](https://simonwillison.net/2025/Aug/6/not-10x); кодинг — 16% времени — [Atlassian](https://www.atlassian.com/blog/developer/developer-experience-report-2025)
- [П] Бенчмарки переоценивают: тесты vs merge — разрыв 24 п.п. — [METR](https://metr.org/notes/2026-03-10-many-swe-bench-passing-prs-would-not-be-merged-into-main/); контаминация SWE-bench Verified — [arXiv 2506.12286](https://arxiv.org/html/2506.12286v4)
- [П] Восприятие ускорения ненадёжно: +20% ощущаемого при −19% реального — [METR](https://metr.org/blog/2025-07-10-early-2025-ai-experienced-os-dev-study/); расхождение самооценки и измерения ~30 перцентилей — [Stanford](https://www.ai.engineer/talks/tbDDYKRFjhk-ai-developer-productivity)

### Inferences
- [И] О тяжёлых процессах (SDD и пр., кратко — подробно у другого исследователя): эмпирических исследований, показывающих, что тяжёлые спецификационные процессы улучшают исход для одиночки с агентом, я не нашёл. Эмпирика поддерживает *лёгкие* элементы: явное описание ожидаемого поведения до генерации (Osmani), маленькие батчи и быстрый feedback loop (DORA), осмысленные тесты-оракулы. Избыточная документация, генерируемая агентом, сама становится кодом, который надо понимать (comprehension debt распространяется и на спецификации).
- [И] Ни одно независимое исследование не измеряло «solo + агент + новый маленький сервис» напрямую; рекомендации — экстраполяция.

### Gaps
- Нет контролируемых сравнений «лёгкий процесс vs тяжёлый spec-driven процесс» для агентной разработки.

---

## Сомнительное / не проверено

- **«AI пишет 25–30% (или 90%) кода»**: заявления CEO (Google — «более четверти нового кода», Microsoft — «20–30%», Dario Amodei, март 2025 — «через 3–6 месяцев AI будет писать 90% кода») — это корпоративные заявления без публичной методики (что считается «написанным AI»: принятые автодополнения? строки?). Не использовать как эмпирику. [И] Источники в этой сессии не открывал.
- **«MIT: 95% GenAI-пилотов проваливаются»** — относится к бизнес-внедрениям GenAI в целом, не к разработке ПО; вне темы. [И] не проверено.
- **«83% пользователей ChatGPT не смогли процитировать своё эссе»** (MIT) — популярная цифра из пересказов; на странице публикации в аннотации есть лишь качественное «struggled to accurately quote their own work», процента нет — [MIT Media Lab](https://www.media.mit.edu/publications/your-brain-on-chatgpt/). Не цитировать число без проверки по PDF.
- **Доверие к AI в SO 2025: «29%» vs «33%»** — расхождение вторичных источников; первоисточник: 3.1% + 29.6% = 32.7% — [SO](https://survey.stackoverflow.co/2025/ai).
- **Uplevel «+41% багов»** — пресс-пересказы, полный отчёт не открыт; методика (как определялись баги, контрольная группа) непрозрачна — [DevOps.com](https://devops.com/study-finds-no-devops-productivity-gains-from-generative-ai/).
- **GitHub «55% faster»** (Peng et al., 2023, старше 2025, вендор) — одна задача (HTTP-сервер на JS), часто цитируется как общий эффект; в этой сессии не проверял.
- **Faros 2026 «code churn +861%», «incidents/PR +242.7%»** — числа с маркетинговой страницы вендора аналитики; определения метрик и методика сравнения (до/после или high vs low adoption) не раскрыты на странице — [Faros](https://faros.ai/research/ai-acceleration-whiplash).
- **CodeRabbit «1.7x больше багов»** — в заголовках СМИ подаётся как «багов», в отчёте это «issues», найденные AI-ревьюером самого вендора; разметка AI/human по косвенным сигналам — [CodeRabbit](https://coderabbit.ai/blog/state-of-ai-vs-human-code-generation-report).
- **AIDev acceptance «Codex 85.8% > люди 82.6%»** — вероятно артефакт workflow (PR в собственные репо); не использовать как «Codex лучше людей» — [arXiv 2602.04226](https://arxiv.org/html/2602.04226v1).
- **OpenAI SWE-bench Verified аудит (59.4%, ~70%→~23% на Pro)** — первоисточник вернул 403; цифры по вторичным пересказам — [OpenAI](https://openai.com/index/why-we-no-longer-evaluate-swe-bench-verified/).
- **METR 2026 «−18%»** — направление знака см. Вопрос 1; ДИ включает ноль — нельзя подавать как «METR доказал ускорение на 18%».
