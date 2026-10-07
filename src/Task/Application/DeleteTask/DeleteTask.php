<?php

declare(strict_types=1);

namespace App\Task\Application\DeleteTask;

/** Command of the DeleteTask slice: the id as the route matched it (a lowercase UUID, ADR-0007 D5). */
final readonly class DeleteTask
{
    public function __construct(public string $id)
    {
    }
}
