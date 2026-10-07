<?php

declare(strict_types=1);

namespace App\Task\Domain;

/** No task has this id; mapped to 404 in framework.exceptions (ADR-0005, ADR-0007 D5). */
final class TaskNotFound extends \DomainException
{
    public static function withId(TaskId $id): self
    {
        return new self(\sprintf('Task "%s" not found.', $id->value));
    }
}
