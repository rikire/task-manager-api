<?php

declare(strict_types=1);

namespace App\Tests\Status\Infrastructure\Persistence;

use App\Status\Domain\Status;
use App\Status\Domain\StatusName;
use App\Status\Domain\StatusNameTaken;
use App\Status\Domain\StatusNotDeletable;
use App\Status\Domain\StatusRepository;
use App\Status\Domain\StatusTitle;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The adapter against the real test database (design D4, D5): ids are UUID v7, a saved status can be read
 * back, and a duplicate name ends as StatusNameTaken — the unique index is the one check, so two
 * concurrent requests are refused the same way, never with a 500 (design D4).
 */
final class DoctrineStatusRepositoryTest extends KernelTestCase
{
    #[Group('REQ-STATUS-create.created')]
    public function testIssuesTimeOrderedUuidV7(): void
    {
        $first = $this->repository()->nextId()->value;
        $second = $this->repository()->nextId()->value;

        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $first);
        self::assertGreaterThan($first, $second, 'UUID v7 grows with time: list order is creation order');
    }

    #[Group('REQ-STATUS-create.created')]
    public function testSavesStatus(): void
    {
        $id = $this->repository()->nextId();

        $this->repository()->save(new Status($id, new StatusName('code_review'), new StatusTitle('Ревью кода')));

        self::assertSame(
            ['name' => 'code_review', 'title' => 'Ревью кода'],
            $this->connection()->fetchAssociative('SELECT name, title FROM status WHERE id = ?', [$id->value]),
        );
    }

    #[Group('REQ-STATUS-create.duplicate-name')]
    public function testTurnsUniqueViolationIntoStatusNameTaken(): void
    {
        $status = new Status($this->repository()->nextId(), new StatusName('done'), new StatusTitle('Сделано'));

        $this->expectException(StatusNameTaken::class);
        $this->expectExceptionMessage('Status "done" already exists.');

        $this->repository()->save($status);
    }

    #[Group('REQ-TASK-create.created')]
    public function testFindsStatusByName(): void
    {
        $status = $this->repository()->findByName(new StatusName('in_progress'));

        self::assertNotNull($status);
        self::assertSame('019b76da-a800-7000-8000-000000000002', $status->id()->value);
        self::assertNull($this->repository()->findByName(new StatusName('archived')));
    }

    /**
     * A task moved to the status after the usage check: the foreign key refuses the delete and the client gets
     * the "used" 409 (ADR-0007 D2). remove() is called directly, standing in for a check that has passed.
     */
    #[Group('REQ-STATUS-delete.moved-in-meanwhile')]
    public function testDeleteRefusedByForeignKeyIsStatusInUse(): void
    {
        $this->connection()->insert('status', ['id' => '01a00000-0000-7000-8000-0000000000aa', 'name' => 'temporary', 'title' => 'Временный']);
        $this->connection()->insert('task', [
            'id' => '01a00000-0000-7000-8000-000000000001', 'title' => 'Отчет', 'description' => null,
            'status_id' => '01a00000-0000-7000-8000-0000000000aa',
            'created_at' => '2026-01-01 10:00:00', 'updated_at' => '2026-01-01 10:00:00',
        ]);
        $temporary = $this->repository()->findByName(new StatusName('temporary'));
        self::assertNotNull($temporary);

        try {
            $this->repository()->remove($temporary);
            self::fail('The delete of a status in use went through');
        } catch (StatusNotDeletable $exception) {
            self::assertSame('Status "temporary" is used by tasks.', $exception->getMessage());
        }
        self::assertSame(1, $this->connection()->fetchOne("SELECT COUNT(*) FROM status WHERE name = 'temporary'"));
        self::assertSame('01a00000-0000-7000-8000-0000000000aa', $this->connection()->fetchOne('SELECT status_id FROM task'));
    }

    private function repository(): StatusRepository
    {
        $repository = self::getContainer()->get(StatusRepository::class);
        self::assertInstanceOf(StatusRepository::class, $repository);

        return $repository;
    }

    private function connection(): Connection
    {
        $connection = self::getContainer()->get(Connection::class);
        self::assertInstanceOf(Connection::class, $connection);

        return $connection;
    }
}
