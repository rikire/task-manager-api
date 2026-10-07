<?php

declare(strict_types=1);

namespace App\Task\Application\ChangeTaskStatus;

use App\Status\Domain\StatusName;
use App\Status\Domain\StatusRepository;
use App\Task\Application\TaskView;
use App\Task\Domain\TaskId;
use App\Task\Domain\TaskNotFound;
use App\Task\Domain\TaskRepository;
use App\Task\Domain\UnknownStatus;

/**
 * REQ-TASK-status-change: the task is looked up before the status, so a missing task answers 404 even when
 * the status is unknown too (owner, 2026-10-07; design D3).
 */
final readonly class ChangeTaskStatusHandler
{
    public function __construct(
        private TaskRepository $tasks,
        private StatusRepository $statuses,
    ) {
    }

    /**
     * @throws TaskNotFound
     * @throws UnknownStatus
     */
    public function __invoke(ChangeTaskStatus $command): TaskView
    {
        $task = $this->tasks->get(new TaskId($command->taskId));
        $status = $this->statuses->findByName(new StatusName($command->status)) ?? throw UnknownStatus::withName($command->status);

        if ($task->changeStatus($status, new \DateTimeImmutable('@'.time()))) {
            $this->tasks->saveStatusChange($task);
        }

        return TaskView::of($task);
    }
}
