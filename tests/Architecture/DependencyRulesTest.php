<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * The project's real Deptrac rules (deptrac.yaml) run against fixture classes, so each rule of
 * ADR-0006 is shown to catch what it must and to allow what it must (QAS-MAINT-layering).
 */
#[Group('ADR-0006-module-structure')]
final class DependencyRulesTest extends TestCase
{
    private const string PROJECT_DIR = __DIR__.'/../..';
    private const string FIXTURES = 'tests/Architecture/Fixtures/src';

    /** @var array<string, list<array{string, string}>> violations per analysed path, computed once */
    private static array $violations = [];

    /** HTTP entry points that reach persistence or the database (QAS-MAINT-layering.controller-queries-database). */
    /** @return iterable<string, array{string, string}> */
    public static function entryPointsReachingTheDatabase(): iterable
    {
        yield 'Task Http on Doctrine' => ['App\Task\Infrastructure\Http\QueriesDatabaseController', 'Doctrine\ORM\EntityManagerInterface'];
        yield 'Task Http on Persistence' => ['App\Task\Infrastructure\Http\UsesPersistenceController', 'App\Task\Infrastructure\Persistence\DoctrineStatusUsage'];
        yield 'Task Http on the Doctrine bridge' => ['App\Task\Infrastructure\Http\UsesMapEntityController', 'Symfony\Bridge\Doctrine\Attribute\MapEntity'];
        yield 'Task Http on PDO' => ['App\Task\Infrastructure\Http\UsesPdoController', 'PDO'];
        yield 'Status Http on Doctrine DBAL' => ['App\Status\Infrastructure\Http\QueriesDatabaseStatusController', 'Doctrine\DBAL\Connection'];
        yield 'controller outside the modules on Doctrine' => ['App\Controller\LegacyController', 'Doctrine\ORM\EntityManagerInterface'];
    }

    /** @return iterable<string, array{string, string}> */
    public static function otherForbiddenDependencies(): iterable
    {
        yield 'Domain on a vendor library' => ['App\Task\Domain\UsesSymfonyUid', 'Symfony\Component\Uid\Uuid'];
        yield 'Application on a vendor library' => ['App\Task\Application\CreateTask\UsesSymfonyUidHandler', 'Symfony\Component\Uid\Uuid'];
        yield 'Status on Task' => ['App\Status\Domain\KnowsTasks', 'App\Task\Domain\TaskNotFound'];
        yield 'Task Http on Status domain' => ['App\Task\Infrastructure\Http\UsesStatusDomainController', 'App\Status\Domain\StatusRepository'];
    }

    /** @return iterable<string, array{string, string}> */
    public static function allowedDependencies(): iterable
    {
        yield 'Http on its domain exception' => ['App\Task\Infrastructure\Http\MapsDomainExceptionController', 'App\Task\Domain\TaskNotFound'];
        yield 'Task persistence implements a Status port' => ['App\Task\Infrastructure\Persistence\DoctrineStatusUsage', 'App\Status\Domain\StatusUsage'];
        yield 'Task application on a Status port' => ['App\Task\Application\CreateTask\CreateTaskHandler', 'App\Status\Domain\StatusRepository'];
        yield 'Task persistence on Doctrine' => ['App\Task\Infrastructure\Persistence\DoctrineTaskRepository', 'Doctrine\ORM\EntityManagerInterface'];
        yield 'Task persistence on a vendor library' => ['App\Task\Infrastructure\Persistence\DoctrineTaskRepository', 'Symfony\Component\Uid\Uuid'];
        yield 'Status persistence on Doctrine DBAL' => ['App\Status\Infrastructure\Persistence\DoctrineStatusRepository', 'Doctrine\DBAL\Connection'];
    }

    #[DataProvider('entryPointsReachingTheDatabase')]
    #[Group('QAS-MAINT-layering.controller-queries-database')]
    public function testReportsEntryPointReachingTheDatabase(string $depender, string $dependent): void
    {
        self::assertContains([$depender, $dependent], self::violationsIn(self::FIXTURES));
    }

    #[Group('QAS-MAINT-layering.cycle')]
    public function testReportsDomainDependingOnApplication(): void
    {
        self::assertContains(
            ['App\Task\Domain\CallsApplication', 'App\Task\Application\CreateTask\CreateTaskHandler'],
            self::violationsIn(self::FIXTURES),
        );
    }

    #[DataProvider('otherForbiddenDependencies')]
    public function testReportsForbiddenDependency(string $depender, string $dependent): void
    {
        self::assertContains([$depender, $dependent], self::violationsIn(self::FIXTURES));
    }

    #[DataProvider('allowedDependencies')]
    public function testAllowsPermittedDependency(string $depender, string $dependent): void
    {
        self::assertNotContains([$depender, $dependent], self::violationsIn(self::FIXTURES));
    }

    public function testReportsNothingBeyondTheForbiddenDependencies(): void
    {
        self::assertCount(11, self::violationsIn(self::FIXTURES));
    }

    #[Group('QAS-MAINT-layering.clean')]
    public function testApplicationCodeHasNoViolations(): void
    {
        self::assertSame([], self::violationsIn('src'));
    }

    /** @return list<array{string, string}> unique [depender, dependent] pairs reported as errors */
    private static function violationsIn(string $path): array
    {
        if (!isset(self::$violations[$path])) {
            self::$violations[$path] = self::analyse($path);
        }

        return self::$violations[$path];
    }

    /** @return list<array{string, string}> */
    private static function analyse(string $path): array
    {
        $configFile = self::PROJECT_DIR.'/deptrac.yaml';
        self::assertFileExists($configFile, 'deptrac.yaml with the rules of ADR-0006 is missing');

        $config = Yaml::parseFile($configFile);
        self::assertIsArray($config);
        self::assertArrayHasKey('deptrac', $config);
        self::assertIsArray($config['deptrac']);
        $projectDir = realpath(self::PROJECT_DIR);
        self::assertIsString($projectDir);
        // Deptrac resolves relative paths against the config file, which is written to the temp directory.
        $config['deptrac']['paths'] = [$projectDir.'/'.$path];

        $tempConfig = sys_get_temp_dir().'/deptrac-'.bin2hex(random_bytes(6)).'.yaml';
        $report = $tempConfig.'.json';
        file_put_contents($tempConfig, Yaml::dump($config, 8));

        try {
            exec(\sprintf(
                'cd %s && php vendor/bin/deptrac analyse --config-file=%s --formatter=json --output=%s --no-progress 2>&1',
                escapeshellarg($projectDir),
                escapeshellarg($tempConfig),
                escapeshellarg($report),
            ), $output);
            $json = json_decode((string) @file_get_contents($report), true);
        } finally {
            @unlink($tempConfig);
            @unlink($report);
        }
        self::assertIsArray($json, "Deptrac produced no JSON report:\n".implode("\n", $output));
        self::assertArrayHasKey('files', $json);
        self::assertIsArray($json['files']);

        $pairs = [];
        foreach ($json['files'] as $file) {
            self::assertIsArray($file);
            self::assertIsArray($file['messages']);
            foreach ($file['messages'] as $message) {
                self::assertIsArray($message);
                self::assertIsString($message['type']);
                self::assertIsString($message['message']);
                if ('error' === $message['type']
                    && 1 === preg_match('/^(\S+) must not depend on (\S+) \(/', $message['message'], $match)) {
                    $pairs[$match[1].' '.$match[2]] = [$match[1], $match[2]];
                }
            }
        }

        return array_values($pairs);
    }
}
