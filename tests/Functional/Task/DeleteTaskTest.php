<?php

declare(strict_types=1);

namespace App\Tests\Functional\Task;

use App\Tests\Functional\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Uid\Uuid;

/** DELETE /api/tasks/{id} (REQ-TASK-delete; ADR-0007 D4, D5). */
final class DeleteTaskTest extends ApiTestCase
{
    #[Group('REQ-TASK-delete.deleted')]
    #[Group('ADR-0007-api-conventions')]
    public function testDeletesTask(): void
    {
        $id = $this->createTask('Удалить меня');
        $kept = $this->createTask('Оставить');

        $response = $this->send('DELETE', '/api/tasks/'.$id, debug: false);

        self::assertSame(204, $response->getStatusCode());
        self::assertSame('', (string) $response->getContent());
        self::assertMatchesContract($response, '/api/tasks/{id}', 'DELETE');
        self::assertProblem($this->send('GET', '/api/tasks/'.$id, debug: false), 404);
        $list = self::decode($this->send('GET', '/api/tasks'));
        self::assertArrayHasKey('items', $list);
        self::assertIsArray($list['items']);
        self::assertSame([$kept], array_column($list['items'], 'id'));
    }

    #[Group('REQ-TASK-delete.not-found')]
    public function testAnswersNotFoundForUnknownOrAlreadyDeletedTask(): void
    {
        $id = $this->createTask('Один раз');
        self::assertSame(204, $this->send('DELETE', '/api/tasks/'.$id, debug: false)->getStatusCode());

        foreach ([$id, Uuid::v7()->toRfc4122()] as $missing) {
            $response = $this->send('DELETE', '/api/tasks/'.$missing, debug: false);
            self::assertProblem($response, 404);
            self::assertMatchesContract($response, '/api/tasks/{id}', 'DELETE');
        }
    }

    #[Group('REQ-TASK-delete.not-found')]
    #[Group('ADR-0007-api-conventions')]
    public function testAnswersNotFoundForMalformedId(): void
    {
        self::assertProblem($this->send('DELETE', '/api/tasks/abc', debug: false), 404);
    }

    private function createTask(string $title): string
    {
        $response = $this->send('POST', '/api/tasks', json_encode(['title' => $title], \JSON_THROW_ON_ERROR), ['CONTENT_TYPE' => 'application/json']);
        self::assertSame(201, $response->getStatusCode());
        $task = self::decode($response);
        self::assertArrayHasKey('id', $task);
        self::assertIsString($task['id']);

        return $task['id'];
    }
}
