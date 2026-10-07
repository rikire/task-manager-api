<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;

/**
 * Error responses that need no product endpoint (ADR-0005; design D7, D8). Every request is sent with debug
 * off and without an `Accept` header, as the production image would see it.
 */
#[Group('ADR-0005-validation')]
final class ErrorResponsesTest extends ApiTestCase
{
    public function testAnswersUnknownApiPathWithJsonNotFound(): void
    {
        $response = $this->send('GET', '/api/no-such-resource', debug: false);

        $problem = self::assertProblem($response, 404);
        self::assertSame('Not Found', $problem['detail']);
    }

    public function testAnswersUnexpectedErrorWithoutInternals(): void
    {
        $response = $this->send('GET', '/api/_test/failure', debug: false);

        $problem = self::assertProblem($response, 500);
        self::assertSame('Internal Server Error', $problem['detail']);
        self::assertArrayNotHasKey('trace', $problem);
        self::assertArrayNotHasKey('class', $problem);
        self::assertStringNotContainsString('db:5432', (string) $response->getContent());
    }

    public function testShowsBrokenDomainRuleInDetail(): void
    {
        $response = $this->send('GET', '/api/_test/domain-rule', debug: false);

        $problem = self::assertProblem($response, 409);
        self::assertSame('Status "done" already exists.', $problem['detail']);
        self::assertArrayNotHasKey('trace', $problem);
    }
}
