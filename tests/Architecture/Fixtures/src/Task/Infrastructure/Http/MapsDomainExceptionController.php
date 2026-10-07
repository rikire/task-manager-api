<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Http;

use App\Task\Domain\TaskNotFound;

// Fixture: Http may use its module domain for exceptions and values — allowed.
final class MapsDomainExceptionController
{
    public function handles(): string
    {
        return TaskNotFound::class;
    }
}
