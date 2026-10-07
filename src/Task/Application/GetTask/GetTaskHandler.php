<?php

declare(strict_types=1);

namespace App\Task\Application\GetTask;

use App\Task\Application\TaskView;
use App\Task\Domain\TaskId;
use App\Task\Domain\TaskNotFound;
use App\Task\Domain\TaskRepository;

/** REQ-TASK-read.get: one task by id. */
final readonly class GetTaskHandler
{
    public function __construct(private TaskRepository $tasks)
    {
    }

    /** @throws TaskNotFound */
    public function __invoke(GetTask $query): TaskView
    {
        return TaskView::of($this->tasks->get(new TaskId($query->id)));
    }
}
