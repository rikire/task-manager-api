<?php

declare(strict_types=1);

namespace App\Tests\Functional\Task;

use App\Tests\Functional\ApiTestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;

/**
 * POST /api/tasks (REQ-TASK-create; ADR-0005 amended, ADR-0007 D1, D3–D5; the proposal's corner-case matrix).
 * Every response is checked against the POST operation of the contract.
 */
final class CreateTaskTest extends ApiTestCase
{
    private const array JSON = ['CONTENT_TYPE' => 'application/json'];
    private const string DATE = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/';

    #[Group('REQ-TASK-create.created')]
    #[Group('ADR-0007-api-conventions')]
    public function testCreatesTask(): void
    {
        $response = $this->post('{"title": "Подготовить отчет", "description": "Отчет по продажам за май"}');

        self::assertSame(201, $response->getStatusCode(), (string) $response->getContent());
        $task = self::decode($response);
        self::assertSame(['id', 'title', 'description', 'status', 'created_at', 'updated_at'], array_keys($task));
        self::assertSame(['Подготовить отчет', 'Отчет по продажам за май', 'new'], [$task['title'], $task['description'], $task['status']]);
        self::assertIsString($task['created_at']);
        self::assertMatchesRegularExpression(self::DATE, $task['created_at']);
        self::assertSame($task['created_at'], $task['updated_at']);
        self::assertIsString($task['id']);
        self::assertSame('/api/tasks/'.$task['id'], $response->headers->get('Location'));
        self::assertSame($task, self::decode($this->sendValid('GET', '/api/tasks/'.$task['id'], '/api/tasks/{id}')));
    }

    /** @return iterable<string, array{string, string, ?string}> */
    public static function acceptedBodies(): iterable
    {
        yield 'no description' => ['{"title": "A"}', 'A', null];
        yield 'description null' => ['{"title": "A", "description": null}', 'A', null];
        yield 'description empty' => ['{"title": "A", "description": ""}', 'A', null];
        yield 'description only spaces' => ['{"title": "A", "description": "   "}', 'A', null];
        yield 'multiline description' => ['{"title": "A", "description": "Строка 1\nСтрока 2\r\n\tс отступом"}', 'A', "Строка 1\nСтрока 2\r\n\tс отступом"];
        yield 'description trimmed' => ['{"title": "A", "description": "  Текст  "}', 'A', 'Текст'];
        yield 'description of 5000 code points' => ['{"title": "A", "description": "'.str_repeat('я', 5000).'"}', 'A', str_repeat('я', 5000)];
        yield 'title trimmed, NBSP included' => ["{\"title\": \"\u{a0} Отчет \u{a0}\"}", 'Отчет', null];
        yield 'title of 255 code points' => ['{"title": "'.str_repeat('я', 255).'"}', str_repeat('я', 255), null];
        yield 'HTML-looking title kept as text' => ['{"title": "<script>alert(1)</script>"}', '<script>alert(1)</script>', null];
        yield 'format character U+202E kept' => ["{\"title\": \"Отч\u{202E}ет\"}", "Отч\u{202E}ет", null];
    }

    #[DataProvider('acceptedBodies')]
    #[Group('REQ-TASK-create.created')]
    public function testStoresTask(string $body, string $title, ?string $description): void
    {
        $response = $this->post($body);

        self::assertSame(201, $response->getStatusCode(), (string) $response->getContent());
        $task = self::decode($response);
        self::assertSame([$title, $description], [$task['title'], $task['description']]);
        self::assertIsString($task['id']);
        self::assertSame(
            ['title' => $title, 'description' => $description],
            $this->connection()->fetchAssociative('SELECT title, description FROM task WHERE id = ?', [$task['id']]),
        );
    }

    #[Group('REQ-TASK-create.created')]
    public function testAllowsEqualTitles(): void
    {
        $codes = [$this->post('{"title": "Отчет"}')->getStatusCode(), $this->post('{"title": "Отчет"}')->getStatusCode()];

        self::assertSame([201, 201], $codes);
        self::assertSame(2, $this->countTasks());
    }

    #[Group('REQ-TASK-create.invalid')]
    public function testRejectsInvalidValues(): void
    {
        $response = $this->post('{"title": "", "description": "Строка\u0000"}');

        self::assertSame(['title', 'description'], self::violatedFields(self::assertProblem($response, 422)));
        self::assertSame(0, $this->countTasks());
    }

    /** @return iterable<string, array{string, list<string>}> */
    public static function invalidValues(): iterable
    {
        yield 'empty object' => ['{}', ['title']];
        yield 'empty body' => ['', ['title']];
        yield 'title null' => ['{"title": null}', ['title']];
        yield 'title empty' => ['{"title": ""}', ['title']];
        yield 'title only no-break spaces' => ["{\"title\": \"\u{a0}\u{a0}\"}", ['title']];
        yield 'title with a newline' => ['{"title": "\nОтчет"}', ['title']];
        yield 'title of 256 code points' => ['{"title": "'.str_repeat('я', 256).'"}', ['title']];
        yield 'description with NUL' => ['{"title": "A", "description": "a\u0000b"}', ['description']];
        yield 'description with ESC' => ['{"title": "A", "description": "a\u001bb"}', ['description']];
        yield 'description with a final form feed' => ['{"title": "A", "description": "text\f"}', ['description']];
        yield 'description of 5001 code points' => ['{"title": "A", "description": "'.str_repeat('я', 5001).'"}', ['description']];
    }

    /** @param list<string> $fields */
    #[DataProvider('invalidValues')]
    #[Group('REQ-TASK-create.invalid')]
    #[Group('ADR-0007-api-conventions')]
    public function testReportsEachInvalidField(string $body, array $fields): void
    {
        self::assertSame($fields, self::violatedFields(self::assertProblem($this->post($body), 422)));
        self::assertSame(0, $this->countTasks());
    }

    /** @return iterable<string, array{string, string}> */
    public static function shapeErrors(): iterable
    {
        yield 'status sent on create' => ['{"title": "Отчет", "status": "done"}', 'status'];
        yield 'unknown field' => ['{"title": "Отчет", "priority": 1}', 'priority'];
        yield 'title is a number' => ['{"title": 42}', 'title'];
        yield 'title is an array' => ['{"title": ["A"], "description": ""}', 'title'];
        yield 'description is a number' => ['{"title": "", "description": 42}', 'description'];
    }

    #[DataProvider('shapeErrors')]
    #[Group('REQ-TASK-create.status-field')]
    #[Group('ADR-0007-api-conventions')]
    public function testReportsShapeErrorAlone(string $body, string $field): void
    {
        self::assertSame([$field], self::violatedFields(self::assertProblem($this->post($body), 422)));
        self::assertSame(0, $this->countTasks());
    }

    #[Group('ADR-0005-validation')]
    public function testRejectsJsonThatIsNotAnObject(): void
    {
        self::assertProblem($this->post('"x"'), 422);
        self::assertSame(0, $this->countTasks());
    }

    #[Group('ADR-0005-validation')]
    public function testRejectsMalformedJson(): void
    {
        self::assertProblem($this->post('{"title":'), 400);
    }

    #[Group('ADR-0005-validation')]
    public function testRejectsBodyThatIsNotJson(): void
    {
        self::assertProblem($this->post('{"title": "A"}', ['CONTENT_TYPE' => 'text/plain']), 415);
    }

    /** A missing `new` breaks a data invariant, not a client rule: 500 without internals (owner, 2026-10-07). */
    #[Group('REQ-TASK-create.created')]
    public function testAnswersServerErrorWhenStatusNewIsMissing(): void
    {
        $this->connection()->executeStatement("DELETE FROM status WHERE name = 'new'");

        $response = $this->send('POST', '/api/tasks', '{"title": "Отчет"}', self::JSON, debug: false);

        $problem = self::assertProblem($response, 500);
        self::assertSame('Internal Server Error', $problem['detail']);
        self::assertSame(0, $this->countTasks());
    }

    /** @param array<string, string> $headers */
    private function post(string $body, array $headers = self::JSON): Response
    {
        $response = $this->send('POST', '/api/tasks', $body, $headers, debug: false);
        self::assertMatchesContract($response, '/api/tasks', 'POST');

        return $response;
    }

    /**
     * @param array<string, mixed> $problem
     *
     * @return list<string>
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

    private function countTasks(): int
    {
        self::assertContains('task', $this->connection()->createSchemaManager()->listTableNames(), 'No table "task": the migration is missing');
        $count = $this->connection()->fetchOne('SELECT COUNT(*) FROM task');
        self::assertIsInt($count);

        return $count;
    }

    private function connection(): Connection
    {
        $connection = self::getContainer()->get(Connection::class);
        self::assertInstanceOf(Connection::class, $connection);

        return $connection;
    }
}
