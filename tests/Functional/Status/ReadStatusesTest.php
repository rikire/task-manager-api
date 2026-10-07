<?php

declare(strict_types=1);

namespace App\Tests\Functional\Status;

use App\Tests\Functional\ApiTestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

/**
 * Initial statuses, the list and one status (REQ-STATUS-initial, REQ-STATUS-read; ADR-0007 D4, D5).
 * Statuses beyond the seed are inserted with SQL: the create endpoint arrives in group 3 (design D8).
 */
final class ReadStatusesTest extends ApiTestCase
{
    private const string UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    #[Group('REQ-STATUS-initial.fresh-database')]
    public function testListsInitialStatusesInOrder(): void
    {
        $response = $this->sendValid('GET', '/api/statuses', '/api/statuses');

        self::assertSame(200, $response->getStatusCode());
        $items = self::items($response);
        self::assertSame(
            [['new', 'Новая'], ['in_progress', 'В работе'], ['done', 'Готово']],
            array_map(static fn (array $status): array => [$status['name'], $status['title']], $items),
        );
        foreach ($items as $status) {
            self::assertSame(['id', 'name', 'title'], array_keys($status));
            self::assertMatchesRegularExpression(self::UUID, $status['id']);
        }
    }

    #[Group('REQ-STATUS-read.list')]
    #[Group('ADR-0007-api-conventions')]
    public function testListsStatusesInCreationOrder(): void
    {
        $this->insertStatus('code_review', 'Ревью кода');

        $response = $this->sendValid('GET', '/api/statuses', '/api/statuses');

        self::assertSame(['new', 'in_progress', 'done', 'code_review'], self::names($response));
    }

    #[Group('REQ-STATUS-read.list')]
    public function testListQueryCountDoesNotGrowWithStatuses(): void
    {
        $withSeedOnly = $this->queryCountOf('GET', '/api/statuses');
        foreach (['review', 'testing', 'blocked', 'on_hold', 'archived'] as $name) {
            $this->insertStatus($name, ucfirst($name));
        }

        self::assertSame($withSeedOnly, $this->queryCountOf('GET', '/api/statuses'));
    }

    #[Group('ADR-0007-api-conventions')]
    public function testIgnoresUnknownQueryParameter(): void
    {
        $response = $this->send('GET', '/api/statuses?foo=1&utm_source=mail');

        self::assertSame(200, $response->getStatusCode());
        self::assertMatchesContract($response, '/api/statuses', 'GET');
        self::assertSame(['new', 'in_progress', 'done'], self::names($response));
    }

    #[Group('REQ-STATUS-read.get')]
    public function testReadsOneStatus(): void
    {
        $id = $this->idOf('in_progress');

        $response = $this->sendValid('GET', '/api/statuses/'.$id, '/api/statuses/{id}');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['id' => $id, 'name' => 'in_progress', 'title' => 'В работе'], self::decode($response));
    }

    #[Group('REQ-STATUS-read.not-found')]
    public function testAnswersNotFoundForUnknownId(): void
    {
        $response = $this->send('GET', '/api/statuses/'.Uuid::v7()->toRfc4122(), debug: false);

        self::assertProblem($response, 404);
        self::assertMatchesContract($response, '/api/statuses/{id}', 'GET');
    }

    #[Group('REQ-STATUS-read.not-found')]
    #[Group('ADR-0007-api-conventions')]
    public function testAnswersNotFoundForMalformedId(): void
    {
        self::assertProblem($this->send('GET', '/api/statuses/abc', debug: false), 404);
    }

    #[Group('ADR-0007-api-conventions')]
    public function testAnswersNotFoundForUppercaseId(): void
    {
        $id = strtoupper($this->idOf('done'));

        self::assertProblem($this->send('GET', '/api/statuses/'.$id, debug: false), 404);
    }

    #[Group('ADR-0005-validation')]
    public function testKeepsApiDocumentationHtml(): void
    {
        $response = $this->send('GET', '/api/doc');

        self::assertSame(200, $response->getStatusCode());
        self::assertStringStartsWith('text/html', (string) $response->headers->get('Content-Type'));
    }

    #[Group('ADR-0003-api-contract')]
    public function testContractRejectsResponseThatBreaksItsSchema(): void
    {
        $broken = new JsonResponse(['items' => [['id' => 1, 'name' => 'new']]]);

        $violation = self::contractViolation($broken, '/api/statuses', 'GET');

        self::assertNotNull($violation, 'A response that breaks the contract passed validation');
        self::assertStringContainsString('Body does not match schema', $violation);
    }

    #[Group('ADR-0007-api-conventions')]
    public function testStatusNameHasUniqueIndex(): void
    {
        $definitions = $this->connection()->fetchFirstColumn(
            "SELECT indexdef FROM pg_indexes WHERE schemaname = 'public' AND tablename = 'status'",
        );

        self::assertContainsOnlyString($definitions);

        self::assertNotSame(
            [],
            preg_grep('/^CREATE UNIQUE INDEX \S+ ON public\.status USING btree \(name\)$/', $definitions),
            'No unique index on status.name: '.implode('; ', $definitions)
        );
    }

    private function insertStatus(string $name, string $title): void
    {
        self::assertContains('status', $this->connection()->createSchemaManager()->listTableNames(), 'No table "status": the migration is missing');
        $this->connection()->insert('status', ['id' => Uuid::v7()->toRfc4122(), 'name' => $name, 'title' => $title]);
    }

    private function idOf(string $name): string
    {
        foreach (self::items($this->send('GET', '/api/statuses')) as $status) {
            if ($status['name'] === $name) {
                return $status['id'];
            }
        }
        self::fail(\sprintf('Status "%s" is not listed', $name));
    }

    private function connection(): Connection
    {
        $connection = self::getContainer()->get(Connection::class);
        self::assertInstanceOf(Connection::class, $connection);

        return $connection;
    }

    /** @return list<array{id: string, name: string, title: string}> */
    private static function items(Response $response): array
    {
        $body = self::decode($response);
        self::assertArrayHasKey('items', $body);
        self::assertIsList($body['items']);
        $items = [];
        foreach ($body['items'] as $status) {
            self::assertIsArray($status);
            self::assertArrayHasKey('id', $status);
            self::assertArrayHasKey('name', $status);
            self::assertArrayHasKey('title', $status);
            self::assertIsString($status['id']);
            self::assertIsString($status['name']);
            self::assertIsString($status['title']);
            self::assertSame(['id', 'name', 'title'], array_keys($status), 'A status has exactly id, name, title');
            $items[] = ['id' => $status['id'], 'name' => $status['name'], 'title' => $status['title']];
        }

        return $items;
    }

    /** @return list<string> */
    private static function names(Response $response): array
    {
        return array_map(static fn (array $status): string => $status['name'], self::items($response));
    }
}
