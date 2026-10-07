<?php

declare(strict_types=1);

namespace App\Task\Domain;

use App\Status\Domain\Status;

/** Port to the task storage (ADR-0006); the Doctrine adapter lives in Infrastructure/Persistence. */
interface TaskRepository
{
    /** A new UUID v7 identifier: known before the task is saved (ADR-0006). */
    public function nextId(): TaskId;

    public function save(Task $task): void;

    public function remove(Task $task): void;

    /** @throws UnknownStatus when the new status was deleted before the change is saved (ADR-0007 D2) */
    public function saveStatusChange(Task $task): void;

    /** @throws TaskNotFound */
    public function get(TaskId $id): Task;

    /**
     * Tasks in creation order (UUID v7 ids grow with time, ADR-0007 D4), only those with the given status
     * when one is given.
     *
     * @return list<Task>
     */
    public function list(?Status $status): array;
}
