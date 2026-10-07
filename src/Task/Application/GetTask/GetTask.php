<?php

declare(strict_types=1);

namespace App\Task\Application\GetTask;

/** Query of the GetTask slice: the id as the route matched it (a lowercase UUID, ADR-0007 D5). */
final readonly class GetTask
{
    public function __construct(public string $id)
    {
    }
}
