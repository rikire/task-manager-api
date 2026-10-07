<?php

declare(strict_types=1);

namespace App\Task\Domain;

/** Identifier of a task: a lowercase UUID string (ADR-0006: UUID v7 from the repository's nextId()). */
final readonly class TaskId
{
    private const string FORMAT = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/';

    public function __construct(public string $value)
    {
        if (1 !== preg_match(self::FORMAT, $value)) {
            throw new \InvalidArgumentException(\sprintf('Task id "%s" is not a lowercase UUID.', $value));
        }
    }
}
