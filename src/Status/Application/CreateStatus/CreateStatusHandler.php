<?php

declare(strict_types=1);

namespace App\Status\Application\CreateStatus;

use App\Status\Application\StatusView;
use App\Status\Domain\Status;
use App\Status\Domain\StatusName;
use App\Status\Domain\StatusNameTaken;
use App\Status\Domain\StatusRepository;
use App\Status\Domain\StatusTitle;

/**
 * REQ-STATUS-create.created: adds a status. A taken name is refused by the storage's unique index, which
 * also covers two concurrent requests, so there is no separate check here (design D4).
 */
final readonly class CreateStatusHandler
{
    public function __construct(private StatusRepository $statuses)
    {
    }

    /** @throws StatusNameTaken */
    public function __invoke(CreateStatus $command): StatusView
    {
        $status = new Status($this->statuses->nextId(), new StatusName($command->name), new StatusTitle($command->title));
        $this->statuses->save($status);

        return StatusView::of($status);
    }
}
