# 32. Инструменты под стек

**Статус:** принято 2026-10-06, человек. Версии проверены на Packagist 2026-10-06; совместимость между
собой перепроверяется при инициализации. **Область:** 6 «Проверка». Изменено 2026-10-06 при проверке совместимости (Packagist): PHPStan ^2.3, Psalm ^6.19.

## Потребность

Решения 04–30 описали проверки без привязки к языку. Стек задан задачей ([31](31-task-requirements.md)):
PHP 8+, Symfony 6+, PostgreSQL, Docker Compose. Нужно выбрать конкретные инструменты под каждую
проверку.

## Решение

### 1. Инструменты

| Что нужно | Инструмент | Комментарий |
|---|---|---|
| Тесты ([05](05-tdd.md), [06](06-test-quality.md)) | PHPUnit | стандарт Symfony; в конфиге: тест без проверок валит прогон (`failOnRisky`), падение на предупреждениях, случайный порядок (`executionOrder=random`) |
| Изоляция БД в тестах | DAMA DoctrineTestBundle 8.6 | каждый тест в транзакции с откатом |
| Фабрики тестовых данных | Zenstruck Foundry 2.13 | тест называет только важные ему поля |
| Метка «тест → сценарий» ([09](09-traceability.md)) | атрибут PHPUnit `#[Group('REQ-…')]`, `#[Group('internal')]` | список групп выгружается — из него строится трассировка |
| Мутационное тестирование ([06](06-test-quality.md)) | Infection 0.35 | `--git-diff-lines` — только изменённые строки; порог `--min-covered-msi` |
| Статический анализ, типы ([26](26-code-quality.md)) | PHPStan ^2.3 (текущие `phpstan-symfony` 2.1 и strict-rules 2.1 требуют ^2.3), максимальный уровень + `phpstan-symfony`, `phpstan-doctrine`, strict-rules | закрывает критерий «строгий тайпчекер» |
| Мёртвый код | `shipmonk/dead-code-detector` 1.4 (расширение PHPStan) | |
| Когнитивная сложность | `tomasvotruba/cognitive-complexity` 1.3 (расширение PHPStan) | порог — после замеров |
| Форматтер и стиль | PHP-CS-Fixer 3.95, набор `@Symfony` | основа — стиль-гайд Symfony ([26](26-code-quality.md) §3) |
| Правила зависимостей ([11](11-architecture-design.md)) | Deptrac 4.7 | слои и направление зависимостей |
| Дубли | jscpd (через mise) | `sebastian/phpcpd` заброшен (последний релиз 2020) |
| N+1 и число запросов ([14](14-code-security.md)) | профайлер Symfony в функциональных тестах (`getCollector('db')->getQueryCount()`) | готового детектора «падать при N+1» для Doctrine нет; тест вызывает эндпоинт списка с 1 и с N записями и проверяет, что число запросов не растёт |
| SAST ([14](14-code-security.md)) | Psalm ^6.19 (без 7.x: 7.0.0-rc1 вышел 2026-10-05), только taint-анализ (`--taint-analysis`), в CI | путь данных от ввода до SQL и вывода; типы проверяет PHPStan. Рассмотрены Semgrep CE (шаблоны; межфайловый поток — в платной версии), SonarQube Cloud и Snyk Code (внешние сервисы); CodeQL, по памяти, PHP не поддерживает (не перепроверено) |
| Аудит зависимостей | `composer audit` | встроен в Composer |
| Сканер секретов ([15](15-agent-security.md)) | gitleaks (через mise) | pre-commit и CI |
| Глотание ошибок ([26](26-code-quality.md)) | правило PHPStan | готовое правило найти при инициализации; если его нет — небольшое своё с объяснением, почему готового нет |
| Маркеры TODO и `DEBUG:` ([10](10-debt-polish-headroom.md), [30](30-code-comments.md)) | короткий скрипт | ID есть в реестре; `DEBUG:` в коммите запрещён |
| Контракт API ([23](23-documentation.md)) | рекомендация: NelmioApiDocBundle 5.13 (контракт OpenAPI генерируется из кода, отдаётся по `/api/doc`) + `league/openapi-psr7-validator` в функциональных тестах (каждый ответ проверяется по контракту) | источник один — код; окончательно — ADR на старте архитектуры |
| Markdown, ссылки, коммиты ([19](19-agent-writing.md), [23](23-documentation.md), [25](25-work-history.md)) | markdownlint-cli2, lychee, commitlint (через mise) | |
| Задержка на свежие релизы ([15](15-agent-security.md)) | — | есть ли такая настройка в Composer, не проверено; если нет — `composer audit` и одобрение каждой зависимости человеком |

### 2. Где запускаются

PHP-инструменты — в контейнере приложения через `docker compose exec` ([29](29-environment.md));
jscpd, gitleaks, линтеры markdown и коммитов — на хосте через mise. Успевает ли форматтер в хуке после
каждой правки через `docker compose exec` — замерить при настройке; если медленно, форматирование
переносится в Stop-хук.

### 3. Решается ADR на старте архитектуры

- Версия Symfony и PHP (задача: Symfony 6+; последняя — 8.1, требует PHP 8.4+).
- Как запускается PHP: PHP-FPM + nginx или FrankenPHP.
- Подход к контракту API (рекомендация — выше).

### 4. Стиль и скиллы под стек

При инициализации агент ищет Symfony Coding Standards, Symfony Best Practices и скиллы под Symfony и PHP
и приносит карточки кандидатов ([20](20-skills.md), [26](26-code-quality.md) §3).

## Обоснование

- Версии и статус пакетов — Packagist, 2026-10-06 (в т.ч. `sebastian/phpcpd` помечен заброшенным).
- Настройки строгости PHPUnit, Infection `--git-diff-lines` и `--min-covered-msi`, DAMA
  DoctrineTestBundle — [ч.5 «Качество тестов»](research/report/05-architecture-testing-verification.md)
  и заметки исследования.
- Deptrac — инструмент правил зависимостей для PHP из
  [ч.5 «Проектирование архитектуры»](research/report/05-architecture-testing-verification.md).
- Psalm taint вместо альтернатив, тест числа запросов вместо детектора N+1, связка Nelmio + валидатор
  — решения человека по предложению агента.

## Гарантия

Как у соответствующих проверок в решениях 04–30: инструменты дают **Г запуска**; полнота — в пределах
их правил.

## Где будет реализовано

- `composer.json` (dev-зависимости), `phpunit.xml.dist`, `phpstan.neon`, `psalm.xml`,
  `.php-cs-fixer.dist.php`, `deptrac.yaml`, `infection.json5`.
- `mise.toml` — jscpd, gitleaks, markdownlint-cli2, lychee, commitlint.
- Makefile — цели, вызывающие эти инструменты; хуки и CI — по решениям 04, 16, 28.
