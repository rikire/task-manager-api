<?php

declare(strict_types=1);

namespace App\Task\Application\CreateTask;

use App\Status\Domain\StatusRepository;

// Fixture: Task application may use the Status domain (ports and values) — allowed.
final class CreateTaskHandler
{
    public function __construct(private StatusRepository $statuses)
    {
    }
}
