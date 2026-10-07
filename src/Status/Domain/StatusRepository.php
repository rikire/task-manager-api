<?php

declare(strict_types=1);

namespace App\Status\Domain;

/** Port to the status storage (ADR-0006); the Doctrine adapter lives in Infrastructure/Persistence. */
interface StatusRepository
{
    /**
     * Every status in creation order (UUID v7 ids grow with time, ADR-0007 D4).
     *
     * @return list<Status>
     */
    public function all(): array;

    /** @throws StatusNotFound */
    public function get(StatusId $id): Status;

    /** A new UUID v7 identifier: known before the status is saved (ADR-0006). */
    public function nextId(): StatusId;

    /** @throws StatusNameTaken when another status has the same name, also under concurrent requests */
    public function save(Status $status): void;
}
