<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Persistence;

use App\Status\Domain\Status;
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

    public function list(?Status $status): array
    {
        // One query with the status joined in: the list does not grow by a query per task (design D5).
        $query = $this->entityManager->createQueryBuilder()
            ->select('task', 'status')
            ->from(Task::class, 'task')
            ->join('task.status', 'status')
            ->orderBy('task.id', 'ASC');
        if (null !== $status) {
            $query->where('task.status = :status')->setParameter('status', $status);
        }

        /** @var list<Task> $tasks */
        $tasks = $query->getQuery()->getResult();

        return $tasks;
    }
}
