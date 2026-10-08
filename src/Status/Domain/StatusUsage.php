<?php

declare(strict_types=1);

namespace App\Status\Domain;

/**
 * Port: is this status used by any task? (ADR-0006). The Task module's Persistence implements it, so the
 * Status module never depends on Task.
 */
interface StatusUsage
{
    public function isUsed(Status $status): bool;
}
