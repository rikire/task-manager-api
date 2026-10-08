<?php

declare(strict_types=1);

namespace App\Tests\Functional\Status;

use App\Tests\Functional\ApiTestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;

/** DELETE /api/statuses/{id} (REQ-STATUS-delete; ADR-0007 D2, D5). Responses are checked against the contract. */
final class DeleteStatusTest extends ApiTestCase
{
    private const array SEEDED = [
        'new' => '019b76da-a800-7000-8000-000000000001',
        'in_progress' => '019b76da-a800-7000-8000-000000000002',
        'done' => '019b76da-a800-7000-8000-000000000003',
    ];

    #[Group('REQ-STATUS-delete.deleted')]
    #[Group('ADR-0007-api-conventions')]
    public function testDeletesUnusedStatus(): void
    {
        $created = self::decode($this->send('POST', '/api/statuses', '{"name": "code_review", "title": "Ревью"}', ['CONTENT_TYPE' => 'application/json']));
        self::assertArrayHasKey('id', $created);
        self::assertIsString($created['id']);

        $response = $this->delete($created['id']);

        self::assertSame(204, $response->getStatusCode());
        self::assertSame('', (string) $response->getContent());
        self::assertProblem($this->send('GET', '/api/statuses/'.$created['id'], debug: false), 404);
        self::assertNotContains('code_review', $this->listedNames());
    }

    /** Only `new` is protected (ADR-0007 D2): a seeded status no task uses can go. */
    #[Group('REQ-STATUS-delete.deleted')]
    public function testDeletesUnusedSeededStatus(): void
    {
        self::assertSame(204, $this->delete(self::SEEDED['in_progress'])->getStatusCode());
        self::assertSame(['new', 'done'], $this->listedNames());
    }

    #[Group('REQ-STATUS-delete.in-use')]
    #[Group('ADR-0007-api-conventions')]
    public function testRefusesStatusUsedByTasks(): void
    {
        $this->insertTask('done');

        $problem = self::assertProblem($this->delete(self::SEEDED['done']), 409);

        self::assertSame('Status "done" is used by tasks.', $problem['detail']);
        self::assertContains('done', $this->listedNames());
        self::assertSame(self::SEEDED['done'], $this->connection()->fetchOne('SELECT status_id FROM task'));
    }

    /** @return iterable<string, array{bool}> */
    public static function withAndWithoutTasks(): iterable
    {
        yield 'a task is new' => [true];
        yield 'no task is new' => [false];
    }

    /** The `new` check comes first, so its detail wins even when tasks use `new` (ADR-0007 D2). */
    #[DataProvider('withAndWithoutTasks')]
    #[Group('REQ-STATUS-delete.initial')]
    #[Group('ADR-0007-api-conventions')]
    public function testRefusesStatusNew(bool $taskUsesIt): void
    {
        if ($taskUsesIt) {
            $this->insertTask('new');
        }

        $problem = self::assertProblem($this->delete(self::SEEDED['new']), 409);

        self::assertSame('Status "new" cannot be deleted.', $problem['detail']);
        self::assertContains('new', $this->listedNames());
        if ($taskUsesIt) {
            self::assertSame(self::SEEDED['new'], $this->connection()->fetchOne('SELECT status_id FROM task'));
        }
    }

    /** @return iterable<string, array{string}> */
    public static function missingIds(): iterable
    {
        yield 'unknown' => ['01a00000-0000-7000-8000-0000000000ff'];
        yield 'not a UUID' => ['abc'];
        yield 'uppercase id of an existing status' => [strtoupper(self::SEEDED['done'])];
    }

    #[DataProvider('missingIds')]
    #[Group('REQ-STATUS-delete.not-found')]
    #[Group('ADR-0007-api-conventions')]
    public function testAnswersNotFound(string $id): void
    {
        $response = $this->send('DELETE', '/api/statuses/'.$id, debug: false);

        self::assertProblem($response, 404);
        if ('abc' !== $id && strtolower($id) === $id) {
            self::assertMatchesContract($response, '/api/statuses/{id}', 'DELETE');
        }
        self::assertSame(['new', 'in_progress', 'done'], $this->listedNames());
    }

    #[Group('REQ-STATUS-delete.not-found')]
    public function testAnswersNotFoundForStatusAlreadyDeleted(): void
    {
        self::assertSame(204, $this->delete(self::SEEDED['in_progress'])->getStatusCode());

        self::assertProblem($this->delete(self::SEEDED['in_progress']), 404);
    }

    #[Group('ADR-0005-validation')]
    public function testAnswersMethodNotAllowed(): void
    {
        foreach (['PUT', 'PATCH'] as $method) {
            self::assertProblem($this->send($method, '/api/statuses/'.self::SEEDED['done'], '{}', ['CONTENT_TYPE' => 'application/json'], debug: false), 405);
        }
    }

    /** Sends DELETE with debug off and checks the response against the DELETE operation (ADR-0003). */
    private function delete(string $id): Response
    {
        $response = $this->send('DELETE', '/api/statuses/'.$id, debug: false);
        self::assertMatchesContract($response, '/api/statuses/{id}', 'DELETE');

        return $response;
    }

    /** @return list<string> */
    private function listedNames(): array
    {
        $list = self::decode($this->send('GET', '/api/statuses'));
        self::assertArrayHasKey('items', $list);
        self::assertIsArray($list['items']);

        return array_values(array_map(static fn (mixed $status): string => \is_array($status) && \is_string($status['name']) ? $status['name'] : '', $list['items']));
    }

    private function insertTask(string $status): void
    {
        $this->connection()->insert('task', [
            'id' => '01a00000-0000-7000-8000-000000000001', 'title' => 'Отчет', 'description' => null,
            'status_id' => self::SEEDED[$status],
            'created_at' => '2026-01-01 10:00:00', 'updated_at' => '2026-01-01 10:00:00',
        ]);
    }

    private function connection(): Connection
    {
        $connection = self::getContainer()->get(Connection::class);
        self::assertInstanceOf(Connection::class, $connection);

        return $connection;
    }
}
