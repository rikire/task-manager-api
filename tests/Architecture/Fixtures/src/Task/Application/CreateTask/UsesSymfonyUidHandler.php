<?php

declare(strict_types=1);

namespace App\Task\Application\CreateTask;

use Symfony\Component\Uid\Uuid;

// Fixture: the application layer depending on a vendor library — must be a violation (core is PHP only).
final class UsesSymfonyUidHandler
{
    public function __construct(private Uuid $id)
    {
    }
}
