<?php

declare(strict_types=1);

namespace App\Tests\Task\Infrastructure\Persistence;

use App\Status\Domain\StatusName;
use App\Status\Domain\StatusRepository;
use App\Task\Domain\TaskId;
use App\Task\Domain\TaskRepository;
use App\Task\Domain\UnknownStatus;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The race of ADR-0007 D2 on the adapter (the request-level race is not tested): the status a task is moved to
 * is deleted before the change is saved; the foreign key refuses it and the client gets UnknownStatus (422).
 */
final class DoctrineTaskRepositoryTest extends KernelTestCase
{
    private const string TASK = '01a00000-0000-7000-8000-000000000001';
    private const string STATUS = '01a00000-0000-7000-8000-0000000000aa';

    #[Group('REQ-TASK-status-change.status-deleted')]
    public function testStatusDeletedBeforeTheChangeIsSavedIsUnknownStatus(): void
    {
        $connection = self::getContainer()->get(Connection::class);
        self::assertInstanceOf(Connection::class, $connection);
        $connection->insert('status', ['id' => self::STATUS, 'name' => 'temporary', 'title' => 'Временный']);
        $connection->insert('task', [
            'id' => self::TASK, 'title' => 'Отчет', 'description' => null,
            'status_id' => '019b76da-a800-7000-8000-000000000001',
            'created_at' => '2026-01-01 10:00:00', 'updated_at' => '2026-01-01 10:00:00',
        ]);
        $tasks = self::getContainer()->get(TaskRepository::class);
        $statuses = self::getContainer()->get(StatusRepository::class);
        self::assertInstanceOf(TaskRepository::class, $tasks);
        self::assertInstanceOf(StatusRepository::class, $statuses);
        $task = $tasks->get(new TaskId(self::TASK));
        $temporary = $statuses->findByName(new StatusName('temporary'));
        self::assertNotNull($temporary);
        self::assertTrue($task->changeStatus($temporary, new \DateTimeImmutable('@'.time())), 'The status did not change');

        $connection->delete('status', ['id' => self::STATUS]);

        $this->expectException(UnknownStatus::class);
        $this->expectExceptionMessage('Unknown status "temporary".');
        $tasks->saveStatusChange($task);
    }
}
