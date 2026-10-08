<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Persistence;

use App\Status\Domain\Status;
use App\Status\Domain\StatusUsage;
use App\Task\Domain\Task;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Answers the Status module's port from the task table (ADR-0006: the port breaks the Status → Task cycle).
 * One query that stops at the first task found.
 */
final readonly class DoctrineStatusUsage implements StatusUsage
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function isUsed(Status $status): bool
    {
        $found = $this->entityManager->createQueryBuilder()
            ->select('1')
            ->from(Task::class, 'task')
            ->where('task.status = :status')
            ->setParameter('status', $status)
            ->setMaxResults(1)
            ->getQuery()
            ->getScalarResult();

        return [] !== $found;
    }
}
