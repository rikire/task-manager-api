<?php

declare(strict_types=1);

namespace App\Tests\Functional\Status;

use App\Tests\Functional\ApiTestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * POST /api/statuses (REQ-STATUS-create; ADR-0005 amended, ADR-0007 D3, D5; the proposal's corner-case
 * matrix). Error responses are sent with debug off and no `Accept` header, as production sees them.
 */
final class CreateStatusTest extends ApiTestCase
{
    private const array JSON = ['CONTENT_TYPE' => 'application/json'];

    #[Group('REQ-STATUS-create.created')]
    #[Group('ADR-0007-api-conventions')]
    public function testCreatesStatus(): void
    {
        $response = $this->sendValid('POST', '/api/statuses', '/api/statuses', '{"name": "code_review", "title": "  Ревью кода "}', self::JSON);

        self::assertSame(201, $response->getStatusCode());
        $status = self::decode($response);
        self::assertSame(['id', 'name', 'title'], array_keys($status));
        self::assertSame(['code_review', 'Ревью кода'], [$status['name'], $status['title']]);
        self::assertIsString($status['id']);
        self::assertSame('/api/statuses/'.$status['id'], $response->headers->get('Location'));
        self::assertSame($status, self::decode($this->sendValid('GET', '/api/statuses/'.$status['id'], '/api/statuses/{id}')));
    }

    /** @return iterable<string, array{string, string}> */
    public static function acceptedEdgeValues(): iterable
    {
        yield 'one-letter name' => ['{"name": "a", "title": "A"}', 'a'];
        yield '50-character name' => ['{"name": "'.str_repeat('a', 50).'", "title": "Long"}', str_repeat('a', 50)];
        yield '255 code points of title inside no-break spaces' => ["{\"name\": \"long_title\", \"title\": \"\u{a0}".str_repeat('я', 255)."\u{a0}\"}", 'long_title'];
        yield 'title of an existing status' => ['{"name": "finished", "title": "Готово"}', 'finished'];
        yield 'duplicate key: the last value wins' => ['{"name": "first", "name": "second", "title": "Second"}', 'second'];
    }

    #[DataProvider('acceptedEdgeValues')]
    #[Group('REQ-STATUS-create.created')]
    public function testAcceptsEdgeValue(string $body, string $storedName): void
    {
        $response = $this->send('POST', '/api/statuses', $body, self::JSON);

        self::assertSame(201, $response->getStatusCode(), (string) $response->getContent());
        self::assertSame($storedName, self::decode($response)['name']);
        self::assertSame(1, $this->countStatuses($storedName));
    }

    #[Group('REQ-STATUS-create.invalid-values')]
    public function testRejectsInvalidValues(): void
    {
        $response = $this->send('POST', '/api/statuses', '{"name": "Code Review", "title": "\nРевью"}', self::JSON, debug: false);

        self::assertSame(['name', 'title'], self::violatedFields(self::assertProblem($response, 422)));
        self::assertSame(3, $this->countStatuses());
    }

    /** @return iterable<string, array{string, list<string>}> */
    public static function invalidValues(): iterable
    {
        yield 'empty object' => ['{}', ['name', 'title']];
        yield 'empty body' => ['', ['name', 'title']];
        yield 'name null' => ['{"name": null, "title": "T"}', ['name']];
        yield 'name empty' => ['{"name": "", "title": "T"}', ['name']];
        yield 'name of 51 characters' => ['{"name": "'.str_repeat('a', 51).'", "title": "T"}', ['name']];
        yield 'name with a final newline' => ['{"name": "done\n", "title": "T"}', ['name']];
        yield 'name with a leading space' => ['{"name": " done", "title": "T"}', ['name']];
        yield 'title only spaces' => ['{"name": "spaces", "title": "   "}', ['title']];
        yield 'title only no-break spaces' => ["{\"name\": \"nbsp\", \"title\": \"\u{a0}\u{a0}\"}", ['title']];
        yield 'title with a tab inside' => ['{"name": "tab", "title": "Ре\tвью"}', ['title']];
        yield 'title with NUL' => ['{"name": "nul", "title": "Ревью\u0000"}', ['title']];
        yield 'title of 256 code points' => ['{"name": "too_long", "title": "'.str_repeat('я', 256).'"}', ['title']];
    }

    /** @param list<string> $fields */
    #[DataProvider('invalidValues')]
    #[Group('REQ-STATUS-create.invalid-values')]
    #[Group('ADR-0005-validation')]
    public function testReportsEachInvalidField(string $body, array $fields): void
    {
        $response = $this->send('POST', '/api/statuses', $body, self::JSON, debug: false);

        self::assertSame($fields, self::violatedFields(self::assertProblem($response, 422)));
        self::assertSame(3, $this->countStatuses());
    }

    /** @return iterable<string, array{string, string}> */
    public static function shapeErrors(): iterable
    {
        yield 'unknown field' => ['{"name": "code_review", "title": "Ревью кода", "color": "red"}', 'color'];
        yield 'name is a number' => ['{"name": 42, "title": "T"}', 'name'];
        yield 'name is a boolean' => ['{"name": true, "title": ""}', 'name'];
        yield 'title is an array' => ['{"name": "", "title": ["T"]}', 'title'];
    }

    /** Shape errors come alone: value rules wait for the next request (ADR-0005, amended). */
    #[DataProvider('shapeErrors')]
    #[Group('REQ-STATUS-create.unknown-field')]
    #[Group('ADR-0007-api-conventions')]
    public function testReportsShapeErrorAlone(string $body, string $field): void
    {
        $response = $this->send('POST', '/api/statuses', $body, self::JSON, debug: false);

        self::assertSame([$field], self::violatedFields(self::assertProblem($response, 422)));
        self::assertSame(3, $this->countStatuses());
    }

    #[Group('REQ-STATUS-create.duplicate-name')]
    public function testRefusesDuplicateName(): void
    {
        $response = $this->send('POST', '/api/statuses', '{"name": "done", "title": "Сделано"}', self::JSON, debug: false);

        $problem = self::assertProblem($response, 409);
        self::assertSame('Status "done" already exists.', $problem['detail']);
        self::assertMatchesContract($response, '/api/statuses', 'POST');
        self::assertSame(3, $this->countStatuses());
    }

    /** @return iterable<string, array{string, array<string, string>}> */
    public static function unsupportedContent(): iterable
    {
        yield 'JSON sent as text/plain' => ['{"name": "a", "title": "A"}', ['CONTENT_TYPE' => 'text/plain']];
    }

    /**
     * An empty body is validated as {} before its Content-Type is looked at (owner, 2026-10-07: accept the
     * framework's order), so no body and no Content-Type answers 422, not 415.
     */
    #[Group('ADR-0005-validation')]
    public function testValidatesEmptyBodyWithoutContentTypeAsEmptyObject(): void
    {
        $response = $this->send('POST', '/api/statuses', '', debug: false);

        self::assertSame(['name', 'title'], self::violatedFields(self::assertProblem($response, 422)));
    }

    /** @param array<string, string> $headers */
    #[DataProvider('unsupportedContent')]
    #[Group('ADR-0005-validation')]
    public function testRejectsUnsupportedContentType(string $body, array $headers): void
    {
        self::assertProblem($this->send('POST', '/api/statuses', $body, $headers, debug: false), 415);
    }

    /** @return iterable<string, array{string}> */
    public static function malformedBodies(): iterable
    {
        yield 'cut JSON' => ['{"name":'];
        yield 'lone surrogate escape' => ['{"name": "\ud800", "title": "T"}'];
    }

    #[DataProvider('malformedBodies')]
    #[Group('ADR-0005-validation')]
    public function testRejectsMalformedJson(string $body): void
    {
        self::assertProblem($this->send('POST', '/api/statuses', $body, self::JSON, debug: false), 400);
    }

    /**
     * Invalid UTF-8 is built here, not in a data provider: PHPUnit prints provider arguments of a failing test
     * as raw bytes, and the end-of-turn hook reading that output must get valid UTF-8.
     */
    #[Group('ADR-0005-validation')]
    public function testRejectsInvalidUtf8(): void
    {
        $invalidUtf8 = \chr(0xC3).\chr(0x28);

        self::assertProblem($this->send('POST', '/api/statuses', '{"name": "'.$invalidUtf8.'", "title": "T"}', self::JSON, debug: false), 400);
    }

    /** @return iterable<string, array{string}> */
    public static function nonObjectBodies(): iterable
    {
        yield 'array' => ['[]'];
        yield 'string' => ['"x"'];
        yield 'number' => ['42'];
        yield 'null' => ['null'];
    }

    /** The owner accepts the framework's code, 400 or 422; a 500 would be a defect (ADR-0005, amended). */
    #[DataProvider('nonObjectBodies')]
    #[Group('ADR-0005-validation')]
    public function testRejectsJsonThatIsNotAnObject(string $body): void
    {
        $response = $this->send('POST', '/api/statuses', $body, self::JSON, debug: false);

        self::assertContains($response->getStatusCode(), [400, 422], (string) $response->getContent());
        self::assertProblem($response, $response->getStatusCode());
        self::assertSame(3, $this->countStatuses());
    }

    /** @return iterable<string, array{string, string}> */
    public static function wrongMethods(): iterable
    {
        yield 'PUT on the list' => ['PUT', '/api/statuses'];
        yield 'PATCH on the list' => ['PATCH', '/api/statuses'];
        yield 'DELETE on the list' => ['DELETE', '/api/statuses'];
        yield 'POST on one status' => ['POST', '/api/statuses/019b76da-a800-7000-8000-000000000001'];
    }

    #[DataProvider('wrongMethods')]
    #[Group('ADR-0005-validation')]
    public function testAnswersMethodNotAllowedInJson(string $method, string $uri): void
    {
        self::assertProblem($this->send($method, $uri, debug: false), 405);
    }

    /**
     * @param array<string, mixed> $problem
     *
     * @return list<string> property paths of the violations, in the order reported
     */
    private static function violatedFields(array $problem): array
    {
        self::assertArrayHasKey('violations', $problem);
        self::assertIsList($problem['violations']);
        $fields = [];
        foreach ($problem['violations'] as $violation) {
            self::assertIsArray($violation);
            self::assertArrayHasKey('propertyPath', $violation);
            self::assertIsString($violation['propertyPath']);
            $fields[] = $violation['propertyPath'];
        }

        return array_values(array_unique($fields));
    }

    private function countStatuses(?string $name = null): int
    {
        $connection = self::getContainer()->get(Connection::class);
        self::assertInstanceOf(Connection::class, $connection);
        $count = null === $name
            ? $connection->fetchOne('SELECT COUNT(*) FROM status')
            : $connection->fetchOne('SELECT COUNT(*) FROM status WHERE name = ?', [$name]);
        self::assertIsInt($count);

        return $count;
    }
}
