<?php

declare(strict_types=1);

namespace App\Tests\Task\Infrastructure\Persistence;

use App\Status\Domain\StatusName;
use App\Status\Domain\StatusRepository;
use App\Status\Domain\StatusUsage;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The Task module answers the Status module's question "is this status used by any task?" (ADR-0006). */
final class DoctrineStatusUsageTest extends KernelTestCase
{
    #[Group('REQ-STATUS-delete.in-use')]
    public function testTellsWhetherAnyTaskUsesTheStatus(): void
    {
        $connection = self::getContainer()->get(Connection::class);
        $usage = self::getContainer()->get(StatusUsage::class);
        $statuses = self::getContainer()->get(StatusRepository::class);
        self::assertInstanceOf(Connection::class, $connection);
        self::assertInstanceOf(StatusUsage::class, $usage);
        self::assertInstanceOf(StatusRepository::class, $statuses);
        $done = $statuses->findByName(new StatusName('done'));
        $new = $statuses->findByName(new StatusName('new'));
        self::assertNotNull($done);
        self::assertNotNull($new);
        $connection->insert('task', [
            'id' => '01a00000-0000-7000-8000-000000000001', 'title' => 'Отчет', 'description' => null,
            'status_id' => $done->id()->value,
            'created_at' => '2026-01-01 10:00:00', 'updated_at' => '2026-01-01 10:00:00',
        ]);

        self::assertTrue($usage->isUsed($done));
        self::assertFalse($usage->isUsed($new));
    }
}
