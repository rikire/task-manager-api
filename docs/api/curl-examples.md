# Примеры запросов к API (curl)

Готовые команды `curl` для каждого эндпоинта API управления задачами: успешный запрос и основные
ошибки. Нужны проверяющему, чтобы руками пройти API после запуска, и нам — как ручная проверка вехи «CRUD
работает» ([роадмап](../roadmap.md#вехи)). Два-три самых показательных примера дублируются в
[README](../../README.md) ([решение 33](../pre-init/33-readme.md)).

Точное описание запросов и ответов — контракт OpenAPI: приложение отдаёт его на `/api/doc` (интерфейс) и
`/api/doc.json` ([ADR-0003](../adr/ADR-0003-api-contract.md)). Этот файл его не заменяет: здесь —
команды, которые можно скопировать.

## Перед началом

Стек поднят по разделу «Быстрый старт» README. Адрес по умолчанию — `http://localhost:8080` (порт
меняется переменной `HTTP_PORT`, см. `compose.yaml`):

```bash
API=http://localhost:8080
```

## Формат примера

Каждый пример добавляется тем изменением OpenSpec, которое вводит эндпоинт, и ссылается на сценарий
требования (`REQ-…`):

````markdown
### <Что делаем> — `<МЕТОД> <путь>`

Сценарий: `REQ-<CAP>-<slug>.<scenario>`.

```bash
curl -s -i -X <МЕТОД> "$API/<путь>" -H 'Content-Type: application/json' -d '<тело>'
```

Ожидаемо: `<код>`, <что в теле>.
````

`-i` показывает код ответа и заголовки: по ним видно, что API отвечает правильным HTTP-кодом
([ADR-0005](../adr/ADR-0005-validation.md)).

## Статусы

### Список статусов — `GET /api/statuses`

Сценарий: `REQ-STATUS-initial.fresh-database`, `REQ-STATUS-read.list`.

```bash
curl -s -i "$API/api/statuses"
```

Ожидаемо: `200`, `{"items": [...]}` — сначала `new`, `in_progress`, `done`, затем остальные в порядке
создания. Кириллица в `title` приходит экранированной (`\u041d...`) — это корректный JSON.

### Один статус — `GET /api/statuses/{id}`

Сценарий: `REQ-STATUS-read.get`. Id начальных статусов фиксированы миграцией; `in_progress`:

```bash
curl -s -i "$API/api/statuses/019b76da-a800-7000-8000-000000000002"
```

Ожидаемо: `200`, `{"id": "019b76da-…-000000000002", "name": "in_progress", "title": "В работе"}`.

### Статуса нет — `GET /api/statuses/{id}`

Сценарий: `REQ-STATUS-read.not-found`.

```bash
curl -s -i "$API/api/statuses/019b76da-a800-7000-8000-0000000000ff"
curl -s -i "$API/api/statuses/abc"
```

Ожидаемо: `404`, тело ошибки в JSON; для существующего формата id в `detail` —
`Status "019b76da-…-0000000000ff" not found.`, для `abc` — `Not Found`.

### Создать статус — `POST /api/statuses`

Сценарий: `REQ-STATUS-create.created`.

```bash
curl -s -i -X POST "$API/api/statuses" -H 'Content-Type: application/json' \
  -d '{"name": "code_review", "title": "  Ревью кода "}'
```

Ожидаемо: `201`, заголовок `Location: /api/statuses/<id>`, тело `{"id": "…", "name": "code_review",
"title": "Ревью кода"}` — пробелы по краям `title` обрезаны.

### Неверные значения — `POST /api/statuses`

Сценарий: `REQ-STATUS-create.invalid-values`.

```bash
curl -s -i -X POST "$API/api/statuses" -H 'Content-Type: application/json' \
  -d '{"name": "Code Review", "title": ""}'
```

Ожидаемо: `422`, в `violations` — ошибки на `name` и `title`.

### Лишнее поле — `POST /api/statuses`

Сценарий: `REQ-STATUS-create.unknown-field`.

```bash
curl -s -i -X POST "$API/api/statuses" -H 'Content-Type: application/json' \
  -d '{"name": "qa", "title": "Тестирование", "color": "red"}'
```

Ожидаемо: `422`, в `violations` — только `color` («This attribute was not expected.»).

### Имя занято — `POST /api/statuses`

Сценарий: `REQ-STATUS-create.duplicate-name`.

```bash
curl -s -i -X POST "$API/api/statuses" -H 'Content-Type: application/json' \
  -d '{"name": "done", "title": "Сделано"}'
```

Ожидаемо: `409`, `detail` — `Status "done" already exists.`

## Задачи

### Создать задачу — `POST /api/tasks`

Сценарий: `REQ-TASK-create.created`.

```bash
curl -s -i -X POST "$API/api/tasks" -H 'Content-Type: application/json' \
  -d '{"title": "Подготовить отчет", "description": "Отчет по продажам за май"}'
```

Ожидаемо: `201`, `Location: /api/tasks/<id>`, задача со `status: "new"` и равными `created_at` и
`updated_at` в формате `2026-10-07T12:00:00Z`. Запомните id для следующих примеров:

```bash
TASK=$(curl -s -X POST "$API/api/tasks" -H 'Content-Type: application/json' -d '{"title": "Пример"}' \
  | sed 's/.*"id":"\([^"]*\)".*/\1/')
```

### Неверные поля — `POST /api/tasks`

Сценарии: `REQ-TASK-create.invalid`, `REQ-TASK-create.status-field`.

```bash
curl -s -i -X POST "$API/api/tasks" -H 'Content-Type: application/json' -d '{"title": ""}'
curl -s -i -X POST "$API/api/tasks" -H 'Content-Type: application/json' -d '{"title": "Отчет", "status": "done"}'
```

Ожидаемо: `422`; в `violations` — `title` в первом случае и `status` («This attribute was not expected.») во
втором: статус новой задачи всегда `new`.

### Одна задача — `GET /api/tasks/{id}`

Сценарии: `REQ-TASK-read.get`, `REQ-TASK-read.not-found`.

```bash
curl -s -i "$API/api/tasks/$TASK"
curl -s -i "$API/api/tasks/019b76da-a800-7000-8000-0000000000ff"
```

Ожидаемо: `200` с задачей; `404`, `detail` — `Task "…" not found.`

### Список и фильтр — `GET /api/tasks`

Сценарии: `REQ-TASK-list.all`, `REQ-TASK-list.filtered`, `REQ-TASK-list.unknown-status`.

```bash
curl -s -i "$API/api/tasks"
curl -s -i "$API/api/tasks?status=new"
curl -s -i "$API/api/tasks?status=archived"
```

Ожидаемо: `200`, `{"items": [...]}` по порядку создания; `200`, только задачи со статусом `new`; `422`,
`detail` — `Unknown status "archived".`

### Сменить статус — `PATCH /api/tasks/{id}/status`

Сценарии: `REQ-TASK-status-change.changed`, `.same-status`, `.invalid`, `.unknown-status`, `.not-found`.

```bash
curl -s -i -X PATCH "$API/api/tasks/$TASK/status" -H 'Content-Type: application/json' -d '{"status": "done"}'
curl -s -i -X PATCH "$API/api/tasks/$TASK/status" -H 'Content-Type: application/json' -d '{"status": "done"}'
curl -s -i -X PATCH "$API/api/tasks/$TASK/status" -H 'Content-Type: application/json' -d '{"status": "Done"}'
curl -s -i -X PATCH "$API/api/tasks/$TASK/status" -H 'Content-Type: application/json' -d '{"status": "done", "color": "red"}'
curl -s -i -X PATCH "$API/api/tasks/$TASK/status" -H 'Content-Type: application/json' -d '{"status": "archived"}'
curl -s -i -X PATCH "$API/api/tasks/019b76da-a800-7000-8000-0000000000ff/status" \
  -H 'Content-Type: application/json' -d '{"status": "done"}'
curl -s -i -X PATCH "$API/api/tasks/abc/status" -H 'Content-Type: application/json' -d '{"status": "done"}'
```

Ожидаемо: `200`, `status: "done"` и новый `updated_at`; повторно — `200`, задача не меняется (тот же
`updated_at`); `422` с ошибкой на `status`; `422` с ошибкой только на `color`; `422`, `detail` —
`Unknown status "archived".`; `404` и `404`. Переходы
свободные: `{"status": "new"}` вернёт задачу обратно.

### Удалить задачу — `DELETE /api/tasks/{id}`

Сценарии: `REQ-TASK-delete.deleted`, `REQ-TASK-delete.not-found`.

```bash
curl -s -i -X DELETE "$API/api/tasks/$TASK"
curl -s -i -X DELETE "$API/api/tasks/$TASK"
```

Ожидаемо: `204` без тела; повторно — `404`.
