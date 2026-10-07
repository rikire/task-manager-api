<?php

declare(strict_types=1);

namespace App\Task\Application\ListTasks;

use App\Status\Domain\StatusName;
use App\Status\Domain\StatusRepository;
use App\Task\Application\TaskView;
use App\Task\Domain\TaskRepository;
use App\Task\Domain\UnknownStatus;

/** REQ-TASK-list: every task, or those with one status (`?status=`), in creation order. */
final readonly class ListTasksHandler
{
    public function __construct(
        private TaskRepository $tasks,
        private StatusRepository $statuses,
    ) {
    }

    /**
     * @return list<TaskView>
     *
     * @throws UnknownStatus
     */
    public function __invoke(ListTasks $query): array
    {
        $status = null;
        if (null !== $query->status) {
            $status = $this->statuses->findByName(new StatusName($query->status)) ?? throw UnknownStatus::withName($query->status);
        }

        return array_map(TaskView::of(...), $this->tasks->list($status));
    }
}
