<?php

declare(strict_types=1);

namespace App\Task\Application\DeleteTask;

use App\Task\Domain\TaskId;
use App\Task\Domain\TaskNotFound;
use App\Task\Domain\TaskRepository;

/** REQ-TASK-delete: removes a task; an unknown or already deleted one is TaskNotFound (404). */
final readonly class DeleteTaskHandler
{
    public function __construct(private TaskRepository $tasks)
    {
    }

    /** @throws TaskNotFound */
    public function __invoke(DeleteTask $command): void
    {
        $this->tasks->remove($this->tasks->get(new TaskId($command->id)));
    }
}
