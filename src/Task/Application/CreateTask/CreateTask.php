<?php

declare(strict_types=1);

namespace App\Task\Application\CreateTask;

/** Command of the CreateTask slice: values the request DTO has already validated (ADR-0005). */
final readonly class CreateTask
{
    public function __construct(
        public string $title,
        public ?string $description,
    ) {
    }
}
