<?php

declare(strict_types=1);

namespace App\Status\Application;

use App\Status\Domain\Status;

/**
 * Read model of a status: exactly the fields the contract promises (RUL-SEC-response-models), shared by the
 * list and get slices of this module. jsonSerialize() lists the response fields explicitly, so what leaves
 * the API is visible here and not decided by reflection.
 */
final readonly class StatusView implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $name,
        public string $title,
    ) {
    }

    public static function of(Status $status): self
    {
        return new self($status->id()->value, $status->name()->value, $status->title()->value);
    }

    /** @return array{id: string, name: string, title: string} */
    public function jsonSerialize(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'title' => $this->title];
    }
}
