<?php

declare(strict_types=1);

namespace App\Status\Domain;

/**
 * Identifier of a status: a lowercase UUID string (ADR-0006: UUID v7 from the repository's nextId(); the
 * domain keeps it as its own value so it depends on nothing but PHP).
 */
final readonly class StatusId
{
    private const string FORMAT = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/';

    public function __construct(public string $value)
    {
        if (1 !== preg_match(self::FORMAT, $value)) {
            throw new \InvalidArgumentException(\sprintf('Status id "%s" is not a lowercase UUID.', $value));
        }
    }
}
