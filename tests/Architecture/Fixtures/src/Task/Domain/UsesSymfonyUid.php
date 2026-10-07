<?php

declare(strict_types=1);

namespace App\Task\Domain;

use Symfony\Component\Uid\Uuid;

// Fixture: the domain depending on a vendor library — must be a violation (ADR-0006: PHP only).
final class UsesSymfonyUid
{
    public function __construct(private Uuid $id)
    {
    }
}
