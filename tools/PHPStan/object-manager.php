<?php

declare(strict_types=1);

// Gives phpstan-doctrine the application's entity manager, so it reads the XML mappings (ADR-0004): entity
// fields Doctrine writes count as written, and DQL and repository calls are type-checked.
use App\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$kernel = new Kernel('dev', true);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
