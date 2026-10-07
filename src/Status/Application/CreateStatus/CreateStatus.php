<?php

declare(strict_types=1);

namespace App\Status\Application\CreateStatus;

/** Command of the CreateStatus slice: values the request DTO has already validated (ADR-0005). */
final readonly class CreateStatus
{
    public function __construct(
        public string $name,
        public string $title,
    ) {
    }
}
