<?php

declare(strict_types=1);

namespace App\Status\Application\DeleteStatus;

use App\Status\Domain\StatusId;
use App\Status\Domain\StatusNotDeletable;
use App\Status\Domain\StatusNotFound;
use App\Status\Domain\StatusRepository;
use App\Status\Domain\StatusUsage;

/**
 * REQ-STATUS-delete: `new` is refused first, so its message wins even when tasks use it; then a status in use
 * (ADR-0007 D2). The usage check and the delete are not atomic: the foreign key refuses a status a task was
 * moved to in between, and remove() turns that into the same refusal.
 */
final readonly class DeleteStatusHandler
{
    public function __construct(
        private StatusRepository $statuses,
        private StatusUsage $usage,
    ) {
    }

    /**
     * @throws StatusNotFound
     * @throws StatusNotDeletable
     */
    public function __invoke(DeleteStatus $command): void
    {
        $status = $this->statuses->get(new StatusId($command->id));
        if (StatusNotDeletable::INITIAL === $status->name()->value) {
            throw StatusNotDeletable::initial();
        }
        if ($this->usage->isUsed($status)) {
            throw StatusNotDeletable::inUse($status->name());
        }

        $this->statuses->remove($status);
    }
}
