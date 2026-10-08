<?php

declare(strict_types=1);

namespace App\Status\Domain;

/**
 * A status that must stay: `new`, which every created task needs, or one that tasks use. Mapped to 409 in
 * framework.exceptions; the two messages tell the cases apart (ADR-0007 D2).
 */
final class StatusNotDeletable extends \DomainException
{
    public const string INITIAL = 'new';

    public static function initial(): self
    {
        return new self(\sprintf('Status "%s" cannot be deleted.', self::INITIAL));
    }

    public static function inUse(StatusName $name, ?\Throwable $previous = null): self
    {
        return new self(\sprintf('Status "%s" is used by tasks.', $name->value), previous: $previous);
    }
}
