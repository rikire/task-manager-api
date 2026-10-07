<?php

declare(strict_types=1);

namespace App\Status\Infrastructure\Http\CreateStatus;

use App\Status\Domain\StatusTitle;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Body of POST /api/statuses (REQ-STATUS-create; ADR-0007 D3; design D3). Broken rules answer 422 with a
 * violation per field; the domain value objects hold the same rules as the last line.
 */
final readonly class CreateStatusRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 50)]
        // htmlPattern is what the OpenAPI contract shows: an ECMA-262 regex, where `\z` would be a literal "z".
        #[Assert\Regex('/^[a-z][a-z0-9_]*\z/', message: 'Use lowercase Latin letters, digits and "_", starting with a letter.', htmlPattern: '^[a-z][a-z0-9_]*$')]
        public string $name,
        // Control characters are refused before trimming (owner, 2026-10-07): "\nReview" is not "Review".
        #[Assert\Regex('/^\P{Cc}*\z/u', message: 'Control characters are not allowed.')]
        #[Assert\NotBlank(normalizer: [StatusTitle::class, 'trim'])]
        #[Assert\Length(max: StatusTitle::MAX_LENGTH, normalizer: [StatusTitle::class, 'trim'])]
        public string $title,
    ) {
    }
}
