<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Http;

use Symfony\Bridge\Doctrine\Attribute\MapEntity;

// Fixture: #[MapEntity] loads an entity from the database — must be a violation for Http.
final class UsesMapEntityController
{
    public function attribute(): string
    {
        return MapEntity::class;
    }
}
