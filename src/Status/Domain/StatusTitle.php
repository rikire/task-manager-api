<?php

declare(strict_types=1);

namespace App\Status\Domain;

/**
 * Label of a status for people (ADR-0007 D3; owner, 2026-10-07): no control character anywhere, then trimmed
 * of whitespace and Unicode spaces, then 1 to 255 code points.
 */
final readonly class StatusTitle
{
    public const int MAX_LENGTH = 255;
    private const string CONTROL_CHARACTER = '/\p{Cc}/u';
    private const string EDGE_SPACES = '/^[\s\p{Z}]+|[\s\p{Z}]+$/u';

    public string $value;

    public function __construct(string $title)
    {
        if (1 === preg_match(self::CONTROL_CHARACTER, $title)) {
            throw new \InvalidArgumentException('Status title must not contain control characters.');
        }
        $trimmed = self::trim($title);
        $length = preg_match_all('/./su', $trimmed);
        if (0 === $length || $length > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(\sprintf('Status title must be 1 to %d characters long after trimming.', self::MAX_LENGTH));
        }
        $this->value = $trimmed;
    }

    /** Removes whitespace and Unicode spaces (NBSP included) at both ends; the request DTO trims with it too. */
    public static function trim(string $title): string
    {
        return (string) preg_replace(self::EDGE_SPACES, '', $title);
    }
}
