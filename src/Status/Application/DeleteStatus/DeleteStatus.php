<?php

declare(strict_types=1);

namespace App\Status\Application\DeleteStatus;

/** Command of the DeleteStatus slice: the id as the route matched it (a lowercase UUID, ADR-0007 D5). */
final readonly class DeleteStatus
{
    public function __construct(public string $id)
    {
    }
}
