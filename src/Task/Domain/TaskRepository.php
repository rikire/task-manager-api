<?php

declare(strict_types=1);

namespace App\Task\Domain;

/** Port to the task storage (ADR-0006); the Doctrine adapter lives in Infrastructure/Persistence. */
interface TaskRepository
{
    /** A new UUID v7 identifier: known before the task is saved (ADR-0006). */
    public function nextId(): TaskId;

    public function save(Task $task): void;

    /** @throws TaskNotFound */
    public function get(TaskId $id): Task;
}
