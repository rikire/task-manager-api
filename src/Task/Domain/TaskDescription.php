<?php

declare(strict_types=1);

namespace App\Task\Domain;

/**
 * Optional multiline description of a task (REQ-TASK-create; owner, 2026-10-07): no control character other
 * than TAB, LF and CR anywhere, then trimmed, then at most 5000 code points; nothing left means no description.
 */
final readonly class TaskDescription
{
    public const int MAX_LENGTH = 5000;
    private const string ALLOWED_CHARACTERS = '/^(?:[\t\n\r]|\P{Cc})*\z/u';
    private const string EDGE_SPACES = '/^[\s\p{Z}]+|[\s\p{Z}]+$/u';

    public ?string $value;

    public function __construct(?string $description)
    {
        if (null !== $description && 1 !== preg_match(self::ALLOWED_CHARACTERS, $description)) {
            throw new \InvalidArgumentException('Task description must not contain control characters other than tab and line breaks.');
        }
        $trimmed = null === $description ? '' : self::trim($description);
        if (preg_match_all('/./su', $trimmed) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(\sprintf('Task description must be at most %d characters long after trimming.', self::MAX_LENGTH));
        }
        $this->value = '' === $trimmed ? null : $trimmed;
    }

    /** Removes whitespace and Unicode spaces at both ends; the request DTO trims with it too. */
    public static function trim(string $description): string
    {
        return (string) preg_replace(self::EDGE_SPACES, '', $description);
    }
}
