<?php

declare(strict_types=1);

namespace App\Status\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;

// Fixture: Status persistence may use the database — allowed.
final class DoctrineStatusRepository
{
    public function __construct(private Connection $connection)
    {
    }
}
