<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Persistence;

use App\Status\Domain\StatusUsage;

// Fixture: Task persistence implements the Status port that breaks the module cycle — allowed.
final class DoctrineStatusUsage implements StatusUsage
{
}
