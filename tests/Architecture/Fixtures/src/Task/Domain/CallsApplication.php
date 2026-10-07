<?php

declare(strict_types=1);

namespace App\Task\Domain;

use App\Task\Application\CreateTask\CreateTaskHandler;

// Fixture: the domain depending on the application layer would close a cycle — must be a violation.
final class CallsApplication
{
    public function __construct(private CreateTaskHandler $handler)
    {
    }
}
