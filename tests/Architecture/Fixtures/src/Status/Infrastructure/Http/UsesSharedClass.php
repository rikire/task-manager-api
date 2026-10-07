<?php

declare(strict_types=1);

namespace App\Status\Infrastructure\Http;

use App\Shared\Infrastructure\Http\NormalizesProblems;

// Fixture: a module that depends on Shared — must be a violation (Shared is wired by configuration only).
final class UsesSharedClass
{
    public function __construct(private NormalizesProblems $normalizer)
    {
    }
}
