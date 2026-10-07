<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Status\Domain\StatusRepository;

// Fixture: shared infrastructure that uses a module — must be a violation (modules plug into Shared, not back).
final class KnowsStatuses
{
    public function __construct(private StatusRepository $statuses)
    {
    }
}
