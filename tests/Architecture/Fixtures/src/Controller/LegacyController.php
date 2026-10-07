<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;

// Fixture: a controller outside the modules (Symfony default folder) — must still be checked.
final class LegacyController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }
}
