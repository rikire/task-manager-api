<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Http\ListTasks;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Query of GET /api/tasks (REQ-TASK-list; ADR-0007 D3, D5; design D6). `status` follows the full name rule, so
 * a malformed or too long value is a violation, not a lookup; unknown parameters are ignored.
 */
final readonly class ListTasksQuery
{
    public function __construct(
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 50)]
        #[Assert\Regex('/^[a-z][a-z0-9_]*\z/', message: 'Use lowercase Latin letters, digits and "_", starting with a letter.', htmlPattern: '^[a-z][a-z0-9_]*$')]
        public ?string $status = null,
    ) {
    }
}
