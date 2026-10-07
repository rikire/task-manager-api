<?php

declare(strict_types=1);

namespace App\Status\Domain;

/** A status with this name exists; mapped to 409 in framework.exceptions (ADR-0007 D3). */
final class StatusNameTaken extends \DomainException
{
    public static function withName(StatusName $name, ?\Throwable $previous = null): self
    {
        return new self(\sprintf('Status "%s" already exists.', $name->value), previous: $previous);
    }
}
