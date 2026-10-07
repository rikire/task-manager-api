<?php

declare(strict_types=1);

namespace App\Task\Domain;

/**
 * Title of a task (REQ-TASK-create; owner, 2026-10-07: the rule of a status title): no control character
 * anywhere, then trimmed of whitespace and Unicode spaces, then 1 to 255 code points. The rule is repeated
 * here, not borrowed from Status: the two titles are unrelated and may diverge (design D4).
 */
final readonly class TaskTitle
{
    public const int MAX_LENGTH = 255;
    private const string CONTROL_CHARACTER = '/\p{Cc}/u';
    private const string EDGE_SPACES = '/^[\s\p{Z}]+|[\s\p{Z}]+$/u';

    public string $value;

    public function __construct(string $title)
    {
        if (1 === preg_match(self::CONTROL_CHARACTER, $title)) {
            throw new \InvalidArgumentException('Task title must not contain control characters.');
        }
        $trimmed = self::trim($title);
        $length = preg_match_all('/./su', $trimmed);
        if (0 === $length || $length > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(\sprintf('Task title must be 1 to %d characters long after trimming.', self::MAX_LENGTH));
        }
        $this->value = $trimmed;
    }

    /** Removes whitespace and Unicode spaces (NBSP included) at both ends; the request DTO trims with it too. */
    public static function trim(string $title): string
    {
        return (string) preg_replace(self::EDGE_SPACES, '', $title);
    }
}
