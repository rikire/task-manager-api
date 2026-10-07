<?php

declare(strict_types=1);

// Environment comes from phpunit.xml.dist and the container, never from .env files (decision record 15).
require dirname(__DIR__).'/vendor/autoload.php';

// The test database is rebuilt by the real migrations once per run, so the schema and the initial statuses
// are exactly what production gets (design D7); each test then runs in a transaction that DAMA rolls back.
foreach ([
    'doctrine:database:drop --force --if-exists',
    'doctrine:database:create',
    'doctrine:migrations:migrate --no-interaction --allow-no-migration',
] as $command) {
    passthru(sprintf('php %s/bin/console %s --env=test --quiet', escapeshellarg(dirname(__DIR__)), $command), $exitCode);
    if (0 !== $exitCode) {
        fwrite(\STDERR, sprintf("Test database setup failed: bin/console %s (exit %d)\n", $command, $exitCode));
        exit(1);
    }
}
