<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Http;

use App\Task\Infrastructure\Persistence\DoctrineStatusUsage;

// Fixture: a controller that uses a persistence adapter — must be a violation.
final class UsesPersistenceController
{
    public function __construct(private DoctrineStatusUsage $adapter)
    {
    }
}
