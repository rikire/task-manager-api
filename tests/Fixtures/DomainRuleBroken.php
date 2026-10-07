<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

/**
 * Stands for a project domain exception: in the App\ namespace, below 500 once mapped in framework.exceptions
 * (mapped to 409 in the test environment only). Used to prove the error `detail` normalizer (ADR-0005).
 */
final class DomainRuleBroken extends \DomainException
{
}
