<?php

declare(strict_types=1);

namespace App\Task\Domain;

/**
 * A request names a status that does not exist; mapped to 422 in framework.exceptions. Not the 404 of
 * StatusNotFound: the resource addressed exists, the request body or query is wrong (ADR-0007 D5).
 */
final class UnknownStatus extends \DomainException
{
    public static function withName(string $name): self
    {
        return new self(\sprintf('Unknown status "%s".', $name));
    }
}
