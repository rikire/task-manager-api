<?php

declare(strict_types=1);

namespace App\Task\Domain;

use App\Status\Domain\Status;

/**
 * A task (assignment: id, title, description, status, created_at, updated_at). It references the Status
 * entity, so the list reads the status name in the same query (design D1, D5). Not final: Doctrine creates
 * lazy proxies.
 */
class Task
{
    private string $id;
    private string $title;
    private ?string $description;
    private Status $status;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(TaskId $id, TaskTitle $title, TaskDescription $description, Status $status, \DateTimeImmutable $now)
    {
        $this->id = $id->value;
        $this->title = $title->value;
        $this->description = $description->value;
        $this->status = $status;
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    /**
     * Moves the task to another status (REQ-TASK-status-change; any status may follow any other, owner).
     * The same status changes nothing, `updatedAt` included; the return value says whether to save.
     */
    public function changeStatus(Status $status, \DateTimeImmutable $now): bool
    {
        if ($this->status->id()->value === $status->id()->value) {
            return false;
        }
        $this->status = $status;
        $this->updatedAt = $now;

        return true;
    }

    public function id(): TaskId
    {
        return new TaskId($this->id);
    }

    public function title(): TaskTitle
    {
        return new TaskTitle($this->title);
    }

    public function description(): TaskDescription
    {
        return new TaskDescription($this->description);
    }

    public function status(): Status
    {
        return $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
