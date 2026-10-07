<?php

declare(strict_types=1);

namespace App\Status\Application\GetStatus;

use App\Status\Application\StatusView;
use App\Status\Domain\StatusId;
use App\Status\Domain\StatusNotFound;
use App\Status\Domain\StatusRepository;

/** REQ-STATUS-read.get: one status by id. */
final readonly class GetStatusHandler
{
    public function __construct(private StatusRepository $statuses)
    {
    }

    /** @throws StatusNotFound */
    public function __invoke(GetStatus $query): StatusView
    {
        return StatusView::of($this->statuses->get(new StatusId($query->id)));
    }
}
