<?php

declare(strict_types=1);

namespace App\Task\Application\CreateTask;

use App\Status\Domain\StatusName;
use App\Status\Domain\StatusRepository;
use App\Task\Application\TaskView;
use App\Task\Domain\Task;
use App\Task\Domain\TaskDescription;
use App\Task\Domain\TaskRepository;
use App\Task\Domain\TaskTitle;

/** REQ-TASK-create.created: adds a task with status `new` (ADR-0007 D1). */
final readonly class CreateTaskHandler
{
    private const string INITIAL_STATUS = 'new';

    public function __construct(
        private TaskRepository $tasks,
        private StatusRepository $statuses,
    ) {
    }

    public function __invoke(CreateTask $command): TaskView
    {
        // The migration seeds `new` and it cannot be deleted, so a missing one is corrupted data: a server
        // error, not the client's (owner, 2026-10-07; design D2).
        $status = $this->statuses->findByName(new StatusName(self::INITIAL_STATUS))
            ?? throw new \LogicException(\sprintf('Status "%s" is missing from the catalog.', self::INITIAL_STATUS));

        // Seconds in UTC: the precision the API shows (owner, 2026-10-07; design D3).
        $now = new \DateTimeImmutable('@'.time());
        $task = new Task($this->tasks->nextId(), new TaskTitle($command->title), new TaskDescription($command->description), $status, $now);
        $this->tasks->save($task);

        return TaskView::of($task);
    }
}
