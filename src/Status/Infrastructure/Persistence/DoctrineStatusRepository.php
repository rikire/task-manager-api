<?php

declare(strict_types=1);

namespace App\Status\Infrastructure\Persistence;

use App\Status\Domain\Status;
use App\Status\Domain\StatusId;
use App\Status\Domain\StatusName;
use App\Status\Domain\StatusNameTaken;
use App\Status\Domain\StatusNotDeletable;
use App\Status\Domain\StatusNotFound;
use App\Status\Domain\StatusRepository;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/** Doctrine adapter of the StatusRepository port (ADR-0004, ADR-0006). */
final readonly class DoctrineStatusRepository implements StatusRepository
{
    private const string RESTRICT_VIOLATION = '23001';

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

    public function remove(Status $status): void
    {
        $this->entityManager->remove($status);
        try {
            $this->entityManager->flush();
        } catch (DriverException $exception) {
            // A task was moved to this status after the usage check: the foreign key refuses, and the client
            // gets the same answer as without the race (ADR-0007 D2). With ON DELETE RESTRICT PostgreSQL reports
            // SQLSTATE 23001 (restrict_violation), which Doctrine does not map to ForeignKeyConstraintViolation.
            if (self::RESTRICT_VIOLATION !== $exception->getSQLState()) {
                throw $exception;
            }
            throw StatusNotDeletable::inUse($status->name(), $exception);
        }
    }

    public function findByName(StatusName $name): ?Status
    {
        return $this->entityManager->getRepository(Status::class)->findOneBy(['name' => $name->value]);
    }

    public function nextId(): StatusId
    {
        return new StatusId(Uuid::v7()->toRfc4122());
    }

    public function save(Status $status): void
    {
        $this->entityManager->persist($status);
        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            // The unique index on the name is the one check for a taken name, so two concurrent requests
            // are refused the same way (ADR-0007 D3, design D4); the id is generated, never a duplicate.
            throw StatusNameTaken::withName($status->name(), $exception);
        }
    }
}
