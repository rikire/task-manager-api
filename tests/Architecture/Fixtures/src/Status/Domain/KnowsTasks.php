<?php

declare(strict_types=1);

namespace App\Status\Domain;

use App\Task\Domain\TaskNotFound;

// Fixture: Status depending on Task would recreate the module cycle — must be a violation.
final class KnowsTasks
{
    public function handles(): string
    {
        return TaskNotFound::class;
    }
}
