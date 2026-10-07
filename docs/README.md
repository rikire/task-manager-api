# Документация проекта

Точка входа: какие документы есть, для кого они и где правила, а где описание. Отсюда же — реестр
префиксов ID. Ссылки вида «решение 23» и «[23]» ведут на документы решений `docs/pre-init/NN-*.md` —
так проектировался процесс до начала кода; структура и язык документов — [23](pre-init/23-documentation.md).

## Реестр документов

«Нормативный» — документ задаёт правила, на него можно ссылаться в `Source:`; «описательный» —
объясняет, но не обязывает ([08](pre-init/08-normative-descriptive.md)).

| Документ | Для кого | Вид | Как ведётся |
|---|---|---|---|
| [README.md](../README.md) | человек, проверяющий | описательный | вручную |
| [AGENTS.md](../AGENTS.md), [CLAUDE.md](../CLAUDE.md) | агент | нормативный | вручную, правки — решение человека, отдельным коммитом |
| [.claude/rules/](../.claude/rules/), [.claude/skills/](../.claude/skills/) | агент | нормативный | вручную, так же |
| [docs/task/](task/README.md) | оба | описательный (первоисточник требований) | вручную; текст задания, из которого удалена инструкция, адресованная ИИ-агенту (подробности — там же), и таблица покрытия |
| [docs/constraints.md](constraints.md) | оба | нормативный | вручную, каждая запись подтверждается человеком ([36](pre-init/36-constraints-and-ids.md)) |
| [docs/roadmap.md](roadmap.md) | человек | описательный | агент предлагает, человек утверждает ([24](pre-init/24-planning-tracking.md)); колонку «Статус» генерирует `scripts/roadmap.py`, вручную не правится |
| [docs/architecture/README.md](architecture/README.md) | человек | описательный | вручную, урезанный arc42 ([11](pre-init/11-architecture-design.md)) |
| [docs/architecture/asvs-l1.md](architecture/asvs-l1.md) | оба | описательный (выборка требований безопасности; правила — в `RUL-SEC-…`) | вручную, классификацию принимает человек |
| [docs/adr/](adr/) | оба | нормативный — раздел «Decision and rationale» | агент пишет черновик, принимает человек коммитом ([12](pre-init/12-adr.md)) |
| [docs/registers/debt.md](registers/debt.md) | оба | описательный | вручную: `DEBT` и `IMP` ([10](pre-init/10-debt-polish-headroom.md)) |
| [docs/registers/failures.md](registers/failures.md) | оба | описательный | вручную: `FAIL` ([22](pre-init/22-process-learning.md)) |
| [openspec/specs/](../openspec/specs/), [openspec/changes/](../openspec/changes/) | оба | нормативный — требования (`SHALL`) | OpenSpec ([02](pre-init/02-sdd-framework.md)) |
| [docs/pre-init/](pre-init/README.md) | человек | история решений | заморожена 2026-10-07 на шаге 14 [чек-листа инициализации](pre-init/35-init-checklist.md), не правится |
| [docs/api/curl-examples.md](api/curl-examples.md) | человек, проверяющий | описательный | вручную: примеры добавляет изменение, которое вводит эндпоинт; 2–3 — в README ([33](pre-init/33-readme.md)) |
| [docs/api/openapi.yaml](api/openapi.yaml) | оба | описательный — копия контракта API, источник — код | генерируется (`make openapi`); приложение отдаёт тот же контракт на `/api/doc.json`, `make openapi-check` в CI сверяет ([ADR-0003](adr/ADR-0003-api-contract.md)) |

Не сделаны: индекс ADR и матрица трассировки (требование → тест → код) — скрипты-кандидаты из
[решения 34](pre-init/34-core-vs-roadmap.md).

## Префиксы ID

Формат и правила (слаги, неизменность ID) — [09](pre-init/09-traceability.md) и
[36](pre-init/36-constraints-and-ids.md).

| Префикс | Что | Допустимые значения | Где определяется |
|---|---|---|---|
| `REQ-<CAP>-<slug>` | требование; `.<slug>` — его сценарий | `<CAP>`: `TASK`, `STATUS` | спеки OpenSpec |
| `QAS-<ATTR>-<slug>` | сценарий качества | `<ATTR>`: `DEPLOY`, `MAINT` | капабилити `quality` |
| `CON-<AREA>-<slug>` | внешнее ограничение — предел, заданный извне (заданием, рекрутером, окружением), например `CON-PLAN-deadline` — сдача до 2026-10-09 | `<AREA>`: `STACK`, `PLAN`, `DELIV` | [docs/constraints.md](constraints.md) |
| `RUL-<AREA>-<slug>` | правило для агента | `<AREA>`: `ARCH`, `CODE`, `SEC`, `TEST` | [.claude/rules/](../.claude/rules/) |
| `ADR-NNNN-<slug>` | архитектурное решение | — | [docs/adr/](adr/) |
| `DEBT-NNN-<slug>`, `IMP-NNN-<slug>` | долг, запас улучшения | — | [docs/registers/debt.md](registers/debt.md) |
| `FAIL-NNN-<slug>` | сбой агента | — | [docs/registers/failures.md](registers/failures.md) |

Новое значение `<CAP>`, `<ATTR>` или `<AREA>` добавляется сюда в том же коммите, где появляется.

## Алиасы переименований

Старый ID остаётся в истории git; по этой таблице его можно найти.

| Было | Стало | Когда |
|---|---|---|
| `R-<CAP>-<slug>` | `REQ-<CAP>-<slug>` | 2026-10-06, решение 36 |
| `Q-<ATTR>-<slug>` | `QAS-<ATTR>-<slug>` | 2026-10-06, решение 36 |
| `C-<AREA>-<slug>` | `RUL-<AREA>-<slug>` | 2026-10-06, решение 36 |
