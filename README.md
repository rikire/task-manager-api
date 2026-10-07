# Task Manager API — REST API для задач и их статусов

Тестовое задание Skyeng (Backend): API для управления задачами и статусами на Symfony 8.1, PHP 8.4,
PostgreSQL 18 и Docker Compose. Статус: приложение поднимается, проверки и CI работают; правила слоёв и
описание архитектуры дописываются; эндпоинтов задач и статусов пока нет — план и порядок в
[docs/roadmap.md](docs/roadmap.md).

## Быстрый старт

Нужен только Docker с Compose v2 (amd64 или arm64).

```sh
git clone https://github.com/rikire/task-manager-api.git
cd task-manager-api
docker compose up -d
```

Первый запуск собирает образ приложения локально — несколько минут. Миграции применяются при старте
автоматически. Убедиться, что всё поднялось: `docker compose ps` — `db` и `app` в состоянии running,
`migrate` завершился с кодом 0. Остановить: `docker compose down` (с удалением данных — `down -v`).

- API: http://localhost:8080
- Документация и контракт OpenAPI: http://localhost:8080/api/doc (JSON — `/api/doc.json`); контракт
  генерируется из кода, его копия будет закоммичена в `docs/api/openapi.yaml`, CI сверяет их

Если порт 8080 занят: `cp .env.example .env` и поменяйте `HTTP_PORT`. Файл `.env` читает Docker Compose,
приложение — нет; без него используются значения по умолчанию из `compose.yaml` (образец переменных —
[.env.example](.env.example)).

## Как устроен репозиторий

| Где | Что |
|---|---|
| [docs/README.md](docs/README.md) | реестр документов и префиксов ID — точка входа в документацию |
| [docs/task/](docs/task/README.md) | текст задания и таблица: какой пункт задания куда попал |
| [docs/roadmap.md](docs/roadmap.md) | план изменений, вехи, что вне рамок |
| [docs/architecture/README.md](docs/architecture/README.md) | обзор архитектуры |
| [docs/adr/](docs/adr/) | архитектурные решения с вариантами и компромиссами |
| [openspec/](openspec/) | требования и изменения (OpenSpec) |
| [AGENTS.md](AGENTS.md), [.claude/](.claude/) | правила и инструменты для ИИ-агента |
| [docs/pre-init/](docs/pre-init/README.md) | как проектировался процесс разработки до начала кода |

## Разработка

Предустановки: git, Docker, [mise](https://mise.jdx.dev) (ставит инструменты процесса по `mise.lock`),
make; для работы с агентом — Claude Code, gh, bubblewrap и socat (песочница, в которой агент выполняет
команды).

```sh
make setup   # инструменты из mise.lock, git-хуки; безопасно запускать повторно
make dev     # стек для разработки: исходники смонтированы, есть dev-инструменты
make test    # только тесты
make check   # всё, что проверяет pre-commit: стиль, PHPStan, тесты, аудит, OpenSpec, тесты хуков
make help    # все команды
```

После `make setup` один раз запустите `claude` в папке проекта и подтвердите доверие к ней — без этого
Claude Code не применяет настройки и хуки проекта.

Цели `test`, `check` и другие сами поднимают стек для разработки, если он не запущен.

Процесс работы над изменением: изменение в [OpenSpec](https://github.com/Fission-AI/OpenSpec)
(инструмент для требований: интервью, спеки, задачи) → тесты, которые сначала падают → реализация →
проверки → приёмка человеком и PR. В `main` вливается только PR с зелёным CI: `commits` проверяет
каждый коммит (формат, секреты, правила), `clean-clone` поднимает проект как проверяющий и прогоняет
`make check`.
