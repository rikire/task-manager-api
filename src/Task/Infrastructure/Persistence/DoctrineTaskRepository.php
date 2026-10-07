<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Persistence;

use App\Task\Domain\Task;
use App\Task\Domain\TaskId;
use App\Task\Domain\TaskNotFound;
use App\Task\Domain\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/** Doctrine adapter of the TaskRepository port (ADR-0004, ADR-0006). */
final readonly class DoctrineTaskRepository implements TaskRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function nextId(): TaskId
    {
        return new TaskId(Uuid::v7()->toRfc4122());
    }

    public function save(Task $task): void
    {
        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }

    public function get(TaskId $id): Task
    {
        return $this->entityManager->find(Task::class, $id->value) ?? throw TaskNotFound::withId($id);
    }
}
