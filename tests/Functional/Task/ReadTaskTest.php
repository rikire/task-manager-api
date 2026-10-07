<?php

declare(strict_types=1);

namespace App\Tests\Functional\Task;

use App\Tests\Functional\ApiTestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Uid\Uuid;

/** GET /api/tasks/{id} and the task schema (REQ-TASK-read; ADR-0007 D1, D2, D5). */
final class ReadTaskTest extends ApiTestCase
{
    #[Group('REQ-TASK-read.get')]
    public function testReadsTask(): void
    {
        $created = self::decode($this->send('POST', '/api/tasks', '{"title": "Отчет"}', ['CONTENT_TYPE' => 'application/json']));
        self::assertArrayHasKey('id', $created, 'The task was not created');
        self::assertIsString($created['id']);

        $response = $this->sendValid('GET', '/api/tasks/'.$created['id'], '/api/tasks/{id}');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($created, self::decode($response));
    }

    #[Group('REQ-TASK-read.not-found')]
    public function testAnswersNotFoundForUnknownId(): void
    {
        $response = $this->send('GET', '/api/tasks/'.Uuid::v7()->toRfc4122(), debug: false);

        self::assertProblem($response, 404);
        self::assertMatchesContract($response, '/api/tasks/{id}', 'GET');
    }

    #[Group('REQ-TASK-read.not-found')]
    #[Group('ADR-0007-api-conventions')]
    public function testAnswersNotFoundForMalformedOrUppercaseId(): void
    {
        self::assertProblem($this->send('GET', '/api/tasks/abc', debug: false), 404);
        self::assertProblem($this->send('GET', '/api/tasks/'.strtoupper(Uuid::v7()->toRfc4122()), debug: false), 404);
    }

    /** A status that tasks use cannot disappear under them: the database refuses (ADR-0007 D1, D2). */
    #[Group('ADR-0007-api-conventions')]
    public function testTaskStatusIsForeignKeyWithRestrict(): void
    {
        $connection = self::getContainer()->get(Connection::class);
        self::assertInstanceOf(Connection::class, $connection);
        self::assertContains('task', $connection->createSchemaManager()->listTableNames(), 'No table "task": the migration is missing');

        $foreignKeys = $connection->fetchAllAssociative(<<<'SQL'
            SELECT a.attname AS column_name, c.confrelid::regclass::text AS referenced_table, c.confdeltype AS on_delete
            FROM pg_constraint c JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
            WHERE c.conrelid = 'task'::regclass AND c.contype = 'f'
            SQL);

        self::assertSame([['column_name' => 'status_id', 'referenced_table' => 'status', 'on_delete' => 'r']], $foreignKeys);
    }
}
