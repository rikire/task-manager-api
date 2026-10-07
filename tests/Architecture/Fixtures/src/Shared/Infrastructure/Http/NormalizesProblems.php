<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

// Fixture: shared infrastructure may use vendor libraries — allowed (ADR-0006, layer Shared).
final class NormalizesProblems
{
    public function __construct(private NormalizerInterface $inner)
    {
    }
}
