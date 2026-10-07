<?php

declare(strict_types=1);

namespace App\Status\Domain;

/**
 * Machine name of a status, used in requests (`?status=done`): ADR-0007 D3. The request DTO reports a broken
 * rule as a violation first; this check keeps a broken name out of the database if that ever fails.
 */
final readonly class StatusName
{
    private const string FORMAT = '/^[a-z][a-z0-9_]{0,49}\z/';

    public function __construct(public string $value)
    {
        if (1 !== preg_match(self::FORMAT, $value)) {
            throw new \InvalidArgumentException(\sprintf('Status name "%s" must match ^[a-z][a-z0-9_]*$ and be 1 to 50 characters long.', $value));
        }
    }
}
