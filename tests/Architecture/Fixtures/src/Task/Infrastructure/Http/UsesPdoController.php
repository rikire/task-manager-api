<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Http;

// Fixture: a raw PDO connection in a controller — must be a violation (database connection).
final class UsesPdoController
{
    public function __construct(private \PDO $connection)
    {
    }
}
