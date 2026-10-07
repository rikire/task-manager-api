<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Persistence;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

// Fixture: persistence adapters may use Doctrine and vendor libraries (symfony/uid) — allowed.
final class DoctrineTaskRepository
{
    public function __construct(private EntityManagerInterface $entityManager, private Uuid $id)
    {
    }
}
