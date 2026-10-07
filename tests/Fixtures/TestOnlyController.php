<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

/**
 * Routes registered only in the test environment (ADR-0005 Confirmation: no test code in production routes):
 * one breaks a domain rule, one fails unexpectedly.
 */
final class TestOnlyController
{
    #[Route('/api/_test/domain-rule', name: 'test_domain_rule', methods: ['GET'])]
    public function domainRule(): never
    {
        throw new DomainRuleBroken('Status "done" already exists.');
    }

    #[Route('/api/_test/failure', name: 'test_failure', methods: ['GET'])]
    public function failure(): never
    {
        throw new \RuntimeException('Secret internal detail: connection to db:5432 refused.');
    }
}
