<?php

declare(strict_types=1);

namespace App\Task\Application\ListTasks;

/** Query of the ListTasks slice: the status name to filter by, if any, already checked by the query DTO. */
final readonly class ListTasks
{
    public function __construct(public ?string $status)
    {
    }
}
