<?php

declare(strict_types=1);

namespace App\Status\Application\GetStatus;

/** Query of the GetStatus slice: the id as the route matched it (a lowercase UUID, ADR-0007 D5). */
final readonly class GetStatus
{
    public function __construct(public string $id)
    {
    }
}
