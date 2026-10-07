<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Http\CreateTask;

use App\Task\Domain\TaskDescription;
use App\Task\Domain\TaskTitle;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Body of POST /api/tasks (REQ-TASK-create; ADR-0007 D3, D4; design D4). Broken rules answer 422 with a
 * violation per field; the domain value objects hold the same rules as the last line.
 */
final readonly class CreateTaskRequest
{
    public function __construct(
        // Control characters are refused before trimming (owner, 2026-10-07): "\nReport" is not "Report".
        #[Assert\Regex('/^\P{Cc}*\z/u', message: 'Control characters are not allowed.', htmlPattern: '')]
        #[Assert\NotBlank(normalizer: [TaskTitle::class, 'trim'])]
        #[Assert\Length(max: TaskTitle::MAX_LENGTH, normalizer: [TaskTitle::class, 'trim'])]
        public string $title,
        // Multiline text: tab and line breaks are allowed, other control characters are not.
        #[Assert\Regex('/^(?:[\t\n\r]|\P{Cc})*\z/u', message: 'Control characters other than tab and line breaks are not allowed.', htmlPattern: '')]
        #[Assert\Length(max: TaskDescription::MAX_LENGTH, normalizer: [TaskDescription::class, 'trim'])]
        public ?string $description = null,
    ) {
    }
}
