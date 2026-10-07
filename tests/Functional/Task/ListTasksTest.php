<?php

declare(strict_types=1);

namespace App\Tests\Functional\Task;

use App\Tests\Functional\ApiTestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;

/**
 * GET /api/tasks and its status filter (REQ-TASK-list; ADR-0007 D4, D5). Tasks are inserted with SQL and
 * fixed ascending ids: no endpoint changes a task's status yet (design, tasks 2.1).
 */
final class ListTasksTest extends ApiTestCase
{
    /** Ids of the seeded statuses (migration Version20261007120000). */
    private const array STATUS_IDS = [
        'new' => '019b76da-a800-7000-8000-000000000001',
        'in_progress' => '019b76da-a800-7000-8000-000000000002',
        'done' => '019b76da-a800-7000-8000-000000000003',
    ];

    #[Group('REQ-TASK-list.all')]
    public function testListsAllTasksInIdOrder(): void
    {
        $this->insertTask('01a00000-0000-7000-8000-000000000001', 'Первая', 'new');
        $this->insertTask('01a00000-0000-7000-8000-000000000002', 'Вторая', 'done');

        $response = $this->sendValid('GET', '/api/tasks', '/api/tasks');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([['Первая', 'new'], ['Вторая', 'done']], self::titlesAndStatuses($response));
    }

    #[Group('REQ-TASK-list.all')]
    #[Group('ADR-0007-api-conventions')]
    public function testOrdersByIdNotByInsertion(): void
    {
        $this->insertTask('01a00000-0000-7000-8000-000000000009', 'Позже по id', 'new');
        $this->insertTask('01a00000-0000-7000-8000-000000000001', 'Раньше по id', 'new');

        self::assertSame(['Раньше по id', 'Позже по id'], array_column(self::titlesAndStatuses($this->sendValid('GET', '/api/tasks', '/api/tasks')), 0));
    }

    #[Group('REQ-TASK-list.all')]
    public function testListsNothingWhenThereAreNoTasks(): void
    {
        self::assertSame(['items' => []], self::decode($this->sendValid('GET', '/api/tasks', '/api/tasks')));
    }

    #[Group('REQ-TASK-list.filtered')]
    public function testFiltersByStatus(): void
    {
        $this->insertTask('01a00000-0000-7000-8000-000000000001', 'Новая', 'new');
        $this->insertTask('01a00000-0000-7000-8000-000000000002', 'Готовая', 'done');
        $this->insertTask('01a00000-0000-7000-8000-000000000003', 'Тоже готовая', 'done');

        $done = $this->sendValid('GET', '/api/tasks?status=done', '/api/tasks');
        $new = $this->sendValid('GET', '/api/tasks?status=new', '/api/tasks');

        self::assertSame([['Готовая', 'done'], ['Тоже готовая', 'done']], self::titlesAndStatuses($done));
        self::assertSame([['Новая', 'new']], self::titlesAndStatuses($new));
    }

    #[Group('REQ-TASK-list.filtered')]
    #[Group('ADR-0007-api-conventions')]
    public function testIgnoresUnknownQueryParameters(): void
    {
        $this->insertTask('01a00000-0000-7000-8000-000000000001', 'Новая', 'new');
        $this->insertTask('01a00000-0000-7000-8000-000000000002', 'Готовая', 'done');

        self::assertCount(2, self::titlesAndStatuses($this->get('/api/tasks?foo=1')));
        self::assertSame([['Готовая', 'done']], self::titlesAndStatuses($this->get('/api/tasks?status=done&utm_source=mail')));
    }

    /** @return iterable<string, array{string}> */
    public static function unknownStatuses(): iterable
    {
        yield 'archived' => ['archived'];
        yield '50 characters, no such status' => [str_repeat('a', 50)];
    }

    #[DataProvider('unknownStatuses')]
    #[Group('REQ-TASK-list.unknown-status')]
    #[Group('ADR-0007-api-conventions')]
    public function testRefusesUnknownStatus(string $name): void
    {
        $problem = self::assertProblem($this->get('/api/tasks?status='.$name), 422);

        self::assertSame(\sprintf('Unknown status "%s".', $name), $problem['detail']);
        self::assertArrayNotHasKey('violations', $problem);
    }

    /** @return iterable<string, array{string}> */
    public static function malformedStatuses(): iterable
    {
        yield 'uppercase' => ['/api/tasks?status=Done'];
        yield 'empty' => ['/api/tasks?status='];
        yield '51 characters' => ['/api/tasks?status='.str_repeat('a', 51)];
        yield 'array' => ['/api/tasks?status%5B%5D=done'];
    }

    #[DataProvider('malformedStatuses')]
    #[Group('REQ-TASK-list.unknown-status')]
    #[Group('ADR-0007-api-conventions')]
    public function testReportsMalformedStatusAsViolation(string $uri): void
    {
        $problem = self::assertProblem($this->get($uri), 422);

        self::assertArrayHasKey('violations', $problem);
        self::assertIsList($problem['violations']);
        $fields = [];
        foreach ($problem['violations'] as $violation) {
            self::assertIsArray($violation);
            self::assertArrayHasKey('propertyPath', $violation);
            self::assertIsString($violation['propertyPath']);
            $fields[] = $violation['propertyPath'];
        }
        self::assertSame(['status'], array_values(array_unique($fields)));
    }

    #[Group('REQ-TASK-list.all')]
    public function testListQueryCountDoesNotGrowWithTasks(): void
    {
        $this->insertTask('01a00000-0000-7000-8000-000000000001', 'Одна', 'new');
        $withOne = $this->queryCountOf('GET', '/api/tasks');
        foreach (['02', '03', '04', '05', '06'] as $suffix) {
            $this->insertTask('01a00000-0000-7000-8000-0000000000'.$suffix, 'Ещё', 'in_progress');
        }

        self::assertSame($withOne, $this->queryCountOf('GET', '/api/tasks'));
        self::assertCount(6, self::titlesAndStatuses($this->get('/api/tasks')), 'The list did not answer, so the count proves nothing');
    }

    /** Sends GET with debug off and checks the response against the GET /api/tasks operation (ADR-0003). */
    private function get(string $uri): Response
    {
        $response = $this->send('GET', $uri, debug: false);
        self::assertMatchesContract($response, '/api/tasks', 'GET');

        return $response;
    }

    private function insertTask(string $id, string $title, string $status): void
    {
        $connection = self::getContainer()->get(Connection::class);
        self::assertInstanceOf(Connection::class, $connection);
        self::assertContains('task', $connection->createSchemaManager()->listTableNames(), 'No table "task": the migration is missing');
        $connection->insert('task', [
            'id' => $id,
            'title' => $title,
            'description' => null,
            'status_id' => self::STATUS_IDS[$status],
            'created_at' => '2026-10-07 12:00:00',
            'updated_at' => '2026-10-07 12:00:00',
        ]);
    }

    /** @return list<array{string, string}> title and status name of each listed task, in order */
    private static function titlesAndStatuses(Response $response): array
    {
        $body = self::decode($response);
        self::assertArrayHasKey('items', $body);
        self::assertIsList($body['items']);
        $tasks = [];
        foreach ($body['items'] as $task) {
            self::assertIsArray($task);
            self::assertSame(['id', 'title', 'description', 'status', 'created_at', 'updated_at'], array_keys($task));
            self::assertIsString($task['title']);
            self::assertIsString($task['status']);
            $tasks[] = [$task['title'], $task['status']];
        }

        return $tasks;
    }
}
