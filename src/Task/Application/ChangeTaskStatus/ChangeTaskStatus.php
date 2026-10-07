<?php

declare(strict_types=1);

namespace App\Task\Application\ChangeTaskStatus;

/** Command of the ChangeTaskStatus slice: the task id from the route and the status name from the body. */
final readonly class ChangeTaskStatus
{
    public function __construct(
        public string $taskId,
        public string $status,
    ) {
    }
}
