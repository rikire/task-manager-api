<?php

declare(strict_types=1);

namespace App\Task\Application;

use App\Task\Domain\Task;

/**
 * Read model of a task, shared by the create, get and list slices (ADR-0006, amended): exactly the fields of
 * the contract (RUL-SEC-response-models); `status` is the status name, dates ISO 8601 UTC with `Z` (ADR-0007 D4).
 */
final readonly class TaskView implements \JsonSerializable
{
    private const string DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

    public function __construct(
        public string $id,
        public string $title,
        public ?string $description,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function of(Task $task): self
    {
        return new self(
            $task->id()->value,
            $task->title()->value,
            $task->description()->value,
            $task->status()->name()->value,
            $task->createdAt()->format(self::DATE_FORMAT),
            $task->updatedAt()->format(self::DATE_FORMAT),
        );
    }

    /** @return array{id: string, title: string, description: ?string, status: string, created_at: string, updated_at: string} */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
