<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Doctrine\DBAL\Connection;

// Fixture: shared infrastructure that reaches the database — must be a violation (Shared may use Vendor only).
final class QueriesDatabase
{
    public function __construct(private Connection $connection)
    {
    }
}
