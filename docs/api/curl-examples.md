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

## Задачи

Появятся с изменениями `task-create-and-read`, `task-status-change`, `task-filter-by-status`,
`task-delete`.
