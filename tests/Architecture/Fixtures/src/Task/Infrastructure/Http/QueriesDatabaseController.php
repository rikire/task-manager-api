<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Http;

use Doctrine\ORM\EntityManagerInterface;

// Fixture: a controller that reaches the database — must be a violation (QAS-MAINT-layering).
final class QueriesDatabaseController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }
}
