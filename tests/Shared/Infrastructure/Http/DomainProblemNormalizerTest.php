<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Http;

use App\Shared\Infrastructure\Http\DomainProblemNormalizer;
use App\Tests\Fixtures\DomainRuleBroken;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Normalizer\ProblemNormalizer;

/**
 * With debug off the framework writes only the HTTP status text into `detail`; the decorator puts a project
 * exception's message there instead, and leaves everything else alone (ADR-0005, amended 2026-10-07).
 * Inputs have the shape the framework produces: framework.exceptions wraps a mapped exception in an
 * HttpException whose previous exception is the original one.
 */
#[Group('ADR-0005-validation')]
final class DomainProblemNormalizerTest extends TestCase
{
    public function testPutsMappedProjectExceptionMessageIntoDetail(): void
    {
        $thrown = HttpException::fromStatusCode(409, 'Status "done" already exists.', new DomainRuleBroken('Status "done" already exists.'));

        $problem = $this->normalize($thrown);

        self::assertSame(409, $problem['status']);
        self::assertSame('Status "done" already exists.', $problem['detail']);
    }

    public function testKeepsStatusTextForUnmappedProjectException(): void
    {
        $problem = $this->normalize(new DomainRuleBroken('Status "done" already exists.'));

        self::assertSame(500, $problem['status']);
        self::assertSame('Internal Server Error', $problem['detail']);
    }

    public function testKeepsStatusTextForFrameworkException(): void
    {
        $problem = $this->normalize(new NotFoundHttpException('No route found for "GET /api/secret-path".'));

        self::assertSame(404, $problem['status']);
        self::assertSame('Not Found', $problem['detail']);
    }

    public function testKeepsStatusTextWhenMappedToServerError(): void
    {
        $thrown = HttpException::fromStatusCode(503, 'Database host db:5432 is down.', new DomainRuleBroken('Database host db:5432 is down.'));

        $problem = $this->normalize($thrown);

        self::assertSame(503, $problem['status']);
        self::assertSame('Service Unavailable', $problem['detail']);
    }

    public function testSupportsWhatTheFrameworkNormalizerSupports(): void
    {
        $normalizer = new DomainProblemNormalizer(new ProblemNormalizer());

        self::assertTrue($normalizer->supportsNormalization(FlattenException::createFromThrowable(new \RuntimeException())));
        self::assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }

    /** @return array<array-key, mixed> */
    private function normalize(\Throwable $thrown): array
    {
        $normalizer = new DomainProblemNormalizer(new ProblemNormalizer(debug: false));

        return $normalizer->normalize(FlattenException::createFromThrowable($thrown), 'json', ['exception' => $thrown]);
    }
}
