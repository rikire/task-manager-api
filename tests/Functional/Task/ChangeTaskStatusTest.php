<?php

declare(strict_types=1);

namespace App\Tests\Functional\Task;

use App\Tests\Functional\ApiTestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;

/**
 * PATCH /api/tasks/{id}/status (REQ-TASK-status-change; ADR-0005, ADR-0007 D2, D3, D5; the proposal's matrix).
 * Tasks are inserted with SQL and dates in the past, so a moved or a kept `updated_at` is visible although
 * dates have whole seconds.
 */
final class ChangeTaskStatusTest extends ApiTestCase
{
    private const string TASK = '01a00000-0000-7000-8000-000000000001';
    private const string PAST = '2026-01-01 10:00:00';
    private const string PAST_JSON = '2026-01-01T10:00:00Z';
    private const array STATUS_IDS = [
        'new' => '019b76da-a800-7000-8000-000000000001',
        'done' => '019b76da-a800-7000-8000-000000000003',
    ];

    #[Group('REQ-TASK-status-change.changed')]
    #[Group('ADR-0007-api-conventions')]
    public function testChangesStatus(): void
    {
        $this->insertTask('new');
        $before = gmdate('Y-m-d\TH:i:s\Z');

        $response = $this->patch(self::TASK, '{"status": "done"}');

        self::assertSame(200, $response->getStatusCode(), (string) $response->getContent());
        $task = self::decode($response);
        self::assertSame(['done', self::PAST_JSON], [$task['status'], $task['created_at']]);
        self::assertIsString($task['updated_at']);
        self::assertGreaterThanOrEqual($before, $task['updated_at']);
        self::assertLessThanOrEqual(gmdate('Y-m-d\TH:i:s\Z'), $task['updated_at']);
        self::assertSame($task, self::decode($this->sendValid('GET', '/api/tasks/'.self::TASK, '/api/tasks/{id}')));
    }

    #[Group('REQ-TASK-status-change.same-status')]
    public function testKeepsTaskWhenStatusIsTheSame(): void
    {
        $this->insertTask('done');

        $response = $this->patch(self::TASK, '{"status": "done"}');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['done', self::PAST_JSON], [self::decode($response)['status'], self::decode($response)['updated_at']]);
        self::assertSame(self::PAST, $this->storedTask()['updated_at']);
    }

    /** Free transitions (owner, 2026-10-07): a finished task may go back to `new`. */
    #[Group('REQ-TASK-status-change.changed')]
    public function testAllowsAnyTransition(): void
    {
        $this->insertTask('done');

        $response = $this->patch(self::TASK, '{"status": "new"}');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('new', self::decode($response)['status']);
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidBodies(): iterable
    {
        yield 'uppercase name' => ['{"status": "Done"}', 'status'];
        yield 'empty name' => ['{"status": ""}', 'status'];
        yield 'null' => ['{"status": null}', 'status'];
        yield '51 characters' => ['{"status": "'.str_repeat('a', 51).'"}', 'status'];
        yield 'empty object' => ['{}', 'status'];
        yield 'empty body' => ['', 'status'];
        yield 'number' => ['{"status": 42}', 'status'];
        yield 'array' => ['{"status": ["done"]}', 'status'];
        yield 'unknown field, reported alone' => ['{"status": "Done", "color": "red"}', 'color'];
    }

    #[DataProvider('invalidBodies')]
    #[Group('REQ-TASK-status-change.invalid')]
    #[Group('ADR-0007-api-conventions')]
    public function testReportsInvalidBody(string $body, string $field): void
    {
        $this->insertTask('new');

        $problem = self::assertProblem($this->patch(self::TASK, $body), 422);

        self::assertArrayHasKey('violations', $problem);
        self::assertIsList($problem['violations']);
        $fields = [];
        foreach ($problem['violations'] as $violation) {
            self::assertIsArray($violation);
            self::assertArrayHasKey('propertyPath', $violation);
            self::assertIsString($violation['propertyPath']);
            $fields[] = $violation['propertyPath'];
        }
        self::assertSame([$field], array_values(array_unique($fields)));
        self::assertSame(self::STATUS_IDS['new'], $this->storedTask()['status_id']);
    }

    /** @return iterable<string, array{string}> */
    public static function unknownStatuses(): iterable
    {
        yield 'archived' => ['archived'];
        yield '50 characters' => [str_repeat('a', 50)];
    }

    #[DataProvider('unknownStatuses')]
    #[Group('REQ-TASK-status-change.unknown-status')]
    #[Group('ADR-0007-api-conventions')]
    public function testRefusesUnknownStatus(string $name): void
    {
        $this->insertTask('new');

        $problem = self::assertProblem($this->patch(self::TASK, json_encode(['status' => $name], \JSON_THROW_ON_ERROR)), 422);

        self::assertSame(\sprintf('Unknown status "%s".', $name), $problem['detail']);
        self::assertSame([self::STATUS_IDS['new'], self::PAST], [$this->storedTask()['status_id'], $this->storedTask()['updated_at']]);
    }

    /** @return iterable<string, array{string, string}> */
    public static function missingTasks(): iterable
    {
        yield 'unknown task, existing status' => ['01a00000-0000-7000-8000-0000000000ff', 'done'];
        yield 'unknown task and unknown status: the task wins' => ['01a00000-0000-7000-8000-0000000000ff', 'archived'];
    }

    #[DataProvider('missingTasks')]
    #[Group('REQ-TASK-status-change.not-found')]
    public function testAnswersNotFoundForUnknownTask(string $id, string $status): void
    {
        self::assertProblem($this->patch($id, json_encode(['status' => $status], \JSON_THROW_ON_ERROR)), 404);
    }

    #[Group('REQ-TASK-status-change.not-found')]
    #[Group('ADR-0007-api-conventions')]
    public function testAnswersNotFoundForMalformedId(): void
    {
        self::assertProblem($this->send('PATCH', '/api/tasks/abc/status', '{"status": "done"}', ['CONTENT_TYPE' => 'application/json'], debug: false), 404);
    }

    /** @return iterable<string, array{string, int, string}> */
    public static function unsupportedBodies(): iterable
    {
        yield 'not an object: string' => ['"x"', 422, 'application/json'];
        yield 'not an object: number' => ['42', 422, 'application/json'];
        yield 'not an object: null' => ['null', 422, 'application/json'];
        yield 'not an object: array' => ['[]', 422, 'application/json'];
        yield 'malformed JSON' => ['{"status":', 400, 'application/json'];
        yield 'text/plain' => ['{"status": "done"}', 415, 'text/plain'];
    }

    #[DataProvider('unsupportedBodies')]
    #[Group('ADR-0005-validation')]
    public function testRejectsUnsupportedBody(string $body, int $code, string $contentType): void
    {
        $this->insertTask('new');

        self::assertProblem($this->patch(self::TASK, $body, $contentType), $code);
        self::assertSame(self::STATUS_IDS['new'], $this->storedTask()['status_id']);
    }

    /** Symfony maps application/merge-patch+json to the JSON format; the owner accepted it (2026-10-07). */
    #[Group('REQ-TASK-status-change.changed')]
    public function testAcceptsMergePatchContentType(): void
    {
        $this->insertTask('new');

        $response = $this->patch(self::TASK, '{"status": "done"}', 'application/merge-patch+json');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('done', self::decode($response)['status']);
    }

    #[Group('ADR-0005-validation')]
    public function testAnswersMethodNotAllowed(): void
    {
        $this->insertTask('new');

        self::assertProblem($this->send('GET', '/api/tasks/'.self::TASK.'/status', debug: false), 405);
        self::assertProblem($this->send('POST', '/api/tasks/'.self::TASK.'/status', '{}', ['CONTENT_TYPE' => 'application/json'], debug: false), 405);
    }

    /** Sends PATCH with debug off and checks the response against the PATCH operation (ADR-0003). */
    private function patch(string $id, string $body, string $contentType = 'application/json'): Response
    {
        $response = $this->send('PATCH', '/api/tasks/'.$id.'/status', $body, ['CONTENT_TYPE' => $contentType], debug: false);
        self::assertMatchesContract($response, '/api/tasks/{id}/status', 'PATCH');

        return $response;
    }

    private function insertTask(string $status): void
    {
        $this->connection()->insert('task', [
            'id' => self::TASK,
            'title' => 'Отчет',
            'description' => null,
            'status_id' => self::STATUS_IDS[$status],
            'created_at' => self::PAST,
            'updated_at' => self::PAST,
        ]);
    }

    /** @return array<string, mixed> */
    private function storedTask(): array
    {
        $task = $this->connection()->fetchAssociative('SELECT status_id, updated_at FROM task WHERE id = ?', [self::TASK]);
        self::assertIsArray($task);

        return $task;
    }

    private function connection(): Connection
    {
        $connection = self::getContainer()->get(Connection::class);
        self::assertInstanceOf(Connection::class, $connection);

        return $connection;
    }
}
