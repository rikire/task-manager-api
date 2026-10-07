<?php

declare(strict_types=1);

namespace App\Status\Application\ListStatuses;

use App\Status\Application\StatusView;
use App\Status\Domain\StatusRepository;

/** REQ-STATUS-read.list: every status in creation order. */
final readonly class ListStatusesHandler
{
    public function __construct(private StatusRepository $statuses)
    {
    }

    /** @return list<StatusView> */
    public function __invoke(): array
    {
        return array_map(StatusView::of(...), $this->statuses->all());
    }
}
