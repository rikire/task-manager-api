<?php

declare(strict_types=1);

namespace App\Tests\Status\Infrastructure\Persistence;

use App\Status\Domain\Status;
use App\Status\Domain\StatusName;
use App\Status\Domain\StatusNameTaken;
use App\Status\Domain\StatusRepository;
use App\Status\Domain\StatusTitle;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The adapter against the real test database (design D4, D5): ids are UUID v7, a saved status can be read
 * back, and a duplicate name that slips past the handler's check (two concurrent requests) still ends as
 * StatusNameTaken — the unique index decides, not a 500.
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
