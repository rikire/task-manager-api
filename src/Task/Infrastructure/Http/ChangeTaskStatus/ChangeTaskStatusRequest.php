<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Http\ChangeTaskStatus;

use Symfony\Component\Validator\Constraints as Assert;

/** Body of PATCH /api/tasks/{id}/status (REQ-TASK-status-change): a status name by ADR-0007 D3's rule. */
final readonly class ChangeTaskStatusRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 50)]
        #[Assert\Regex('/^[a-z][a-z0-9_]*\z/', message: 'Use lowercase Latin letters, digits and "_", starting with a letter.', htmlPattern: '^[a-z][a-z0-9_]*$')]
        public string $status,
    ) {
    }
}
