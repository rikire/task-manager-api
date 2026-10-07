<?php

declare(strict_types=1);

namespace App\Status\Domain;

/** No status has this id; mapped to 404 in framework.exceptions (ADR-0005, ADR-0007 D5). */
final class StatusNotFound extends \DomainException
{
    public static function withId(StatusId $id): self
    {
        return new self(\sprintf('Status "%s" not found.', $id->value));
    }
}
