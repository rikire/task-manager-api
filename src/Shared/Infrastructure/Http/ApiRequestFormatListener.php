<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Every response under /api is JSON, errors included (ADR-0005, amended 2026-10-07). The `_format: json`
 * route default applies only once a route matches, so an unknown path (404) or a wrong method (405) would
 * render an HTML error page for a client that sends no `Accept` header. Runs before the router (priority 32).
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 64)]
final readonly class ApiRequestFormatListener
{
    private const string API_PREFIX = '/api/';

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (str_starts_with($request->getPathInfo(), self::API_PREFIX) && !self::isApiDocumentation($request->getPathInfo())) {
            $request->setRequestFormat('json');
        }
    }

    /** Swagger UI at /api/doc is an HTML page (ADR-0003). */
    private static function isApiDocumentation(string $path): bool
    {
        return '/api/doc' === $path || str_starts_with($path, '/api/doc.');
    }
}
