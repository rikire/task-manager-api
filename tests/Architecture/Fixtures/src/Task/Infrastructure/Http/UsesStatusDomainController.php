<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Http;

use App\Status\Domain\StatusRepository;

// Fixture: Task Http may use only its own module domain — Status domain must be a violation.
final class UsesStatusDomainController
{
    public function __construct(private StatusRepository $statuses)
    {
    }
}
