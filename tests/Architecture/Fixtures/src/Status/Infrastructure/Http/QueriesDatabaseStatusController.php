<?php

declare(strict_types=1);

namespace App\Status\Infrastructure\Http;

use Doctrine\DBAL\Connection;

// Fixture: Status Http reaching the database — must be a violation.
final class QueriesDatabaseStatusController
{
    public function __construct(private Connection $connection)
    {
    }
}
