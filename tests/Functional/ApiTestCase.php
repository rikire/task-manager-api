<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use League\OpenAPIValidation\Schema\SchemaValidator;
use Osteel\OpenApi\Testing\Exceptions\ValidationException;
use Osteel\OpenApi\Testing\ValidatorBuilder;
use Osteel\OpenApi\Testing\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profile;

/**
 * Base of every functional API test: sends a request and checks the response against the committed
 * contract `docs/api/openapi.yaml` (ADR-0003). Error responses are checked against the shared `Problem`
 * schema, which also covers paths the contract has no operation for (design D7).
 */
abstract class ApiTestCase extends WebTestCase
{
    private const string CONTRACT = __DIR__.'/../../docs/api/openapi.yaml';

    private static ?OpenApi $contract = null;
    private static ?ValidatorInterface $validator = null;

    /**
     * Sends a request without an `Accept` header unless one is given, as a plain client would.
     *
     * @param array<string, string> $headers server parameters, for example `CONTENT_TYPE`
     * @param bool                  $debug   false shows what the production image sends
     */
    protected function send(string $method, string $uri, ?string $body = null, array $headers = [], bool $debug = true): Response
    {
        self::ensureKernelShutdown();
        $client = static::createClient(['debug' => $debug]);
        $client->request($method, $uri, server: $headers, content: $body);

        return $client->getResponse();
    }

    /**
     * Sends a valid request and checks both the request and the response against the contract operation
     * `$method $contractPath` (ADR-0003). `$contractPath` is the path as the contract writes it, for example
     * `/api/statuses/{id}`.
     *
     * @param array<string, string> $headers server parameters, for example `CONTENT_TYPE`
     */
    protected function sendValid(string $method, string $uri, string $contractPath, ?string $body = null, array $headers = []): Response
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request($method, $uri, server: $headers, content: $body);

        self::assertNull(self::contractViolation($client->getRequest(), $contractPath, $method), 'The request breaks the contract');
        self::assertMatchesContract($client->getResponse(), $contractPath, $method);

        return $client->getResponse();
    }

    /** Number of SQL queries one request makes (rule: list endpoints must not grow with the number of rows). */
    protected function queryCountOf(string $method, string $uri): int
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->enableProfiler();
        $client->request($method, $uri);

        $profile = $client->getProfile();
        self::assertInstanceOf(Profile::class, $profile, 'The profiler is not enabled in the test environment');
        $collector = $profile->getCollector('db');
        self::assertInstanceOf(DoctrineDataCollector::class, $collector);

        return $collector->getQueryCount();
    }

    protected static function assertMatchesContract(Response $response, string $contractPath, string $method): void
    {
        self::assertNull(self::contractViolation($response, $contractPath, $method), 'The response breaks the contract');
    }

    /**
     * The validator's message when a request or response breaks the contract operation, null when it
     * matches. The validator reports by throwing; a test failure must name the broken rule instead.
     */
    protected static function contractViolation(object $message, string $contractPath, string $method): ?string
    {
        try {
            self::validator()->validate($message, $contractPath, $method);
        } catch (ValidationException $exception) {
            return $exception->getMessage();
        }

        return null;
    }

    /** @return array<array-key, mixed> */
    protected static function decode(Response $response): array
    {
        self::assertStringStartsWith('application/json', (string) $response->headers->get('Content-Type'));
        $decoded = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }

    /**
     * Asserts an RFC 9457 problem-details body (ADR-0005) with the given status, served as JSON and valid
     * against the contract's `Problem` schema.
     *
     * @return array<string, mixed> the decoded body
     */
    protected static function assertProblem(Response $response, int $status): array
    {
        self::assertSame($status, $response->getStatusCode());
        self::assertStringStartsWith('application/json', (string) $response->headers->get('Content-Type'));

        // The league validator expects JSON objects as associative arrays, not stdClass.
        $decoded = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        $components = self::contract()->components;
        self::assertNotNull($components, 'The contract has no components');
        self::assertArrayHasKey('Problem', $components->schemas, 'The contract has no components.schemas.Problem');
        $schema = $components->schemas['Problem'];
        self::assertInstanceOf(Schema::class, $schema);
        new SchemaValidator(SchemaValidator::VALIDATE_AS_RESPONSE)->validate($decoded, $schema);

        self::assertArrayHasKey('status', $decoded);
        self::assertSame($status, $decoded['status']);

        $problem = [];
        foreach ($decoded as $key => $value) {
            $problem[(string) $key] = $value;
        }

        return $problem;
    }

    private static function validator(): ValidatorInterface
    {
        return self::$validator ??= ValidatorBuilder::fromYamlFile(self::contractFile())->getValidator();
    }

    private static function contract(): OpenApi
    {
        return self::$contract ??= Reader::readFromYamlFile(self::contractFile());
    }

    private static function contractFile(): string
    {
        self::assertFileExists(self::CONTRACT, 'docs/api/openapi.yaml is missing: run `make openapi`');

        return (string) realpath(self::CONTRACT);
    }
}
