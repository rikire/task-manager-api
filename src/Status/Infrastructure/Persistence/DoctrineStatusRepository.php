<?php

declare(strict_types=1);

namespace App\Status\Infrastructure\Persistence;

use App\Status\Domain\Status;
use App\Status\Domain\StatusId;
use App\Status\Domain\StatusNotFound;
use App\Status\Domain\StatusRepository;
use Doctrine\ORM\EntityManagerInterface;

/** Doctrine adapter of the StatusRepository port (ADR-0004, ADR-0006). */
final readonly class DoctrineStatusRepository implements StatusRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function all(): array
    {
        /** @var list<Status> $statuses */
        $statuses = $this->entityManager->getRepository(Status::class)->findBy([], ['id' => 'ASC']);

        return $statuses;
    }

    public function get(StatusId $id): Status
    {
        return $this->entityManager->find(Status::class, $id->value) ?? throw StatusNotFound::withId($id);
    }
}
