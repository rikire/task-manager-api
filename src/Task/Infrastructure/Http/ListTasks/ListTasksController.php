<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Http\ListTasks;

use App\Task\Application\ListTasks\ListTasks;
use App\Task\Application\ListTasks\ListTasksHandler;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

/** REQ-TASK-list: GET /api/tasks[?status=<name>] → {"items": [...]} in creation order (ADR-0007 D4, D5). */
#[OA\Tag(name: 'Tasks')]
final class ListTasksController extends AbstractController
{
    public function __construct(private readonly ListTasksHandler $listTasks)
    {
    }

    #[Route('/api/tasks', name: 'task_list', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Tasks in creation order, optionally of one status',
        content: new OA\JsonContent(
            required: ['items'],
            properties: [new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/Task'))],
        ),
    )]
    #[OA\Response(response: 422, description: 'The status filter is malformed or names no status', content: new OA\JsonContent(ref: '#/components/schemas/Problem'))]
    public function __invoke(
        // Without a query string the argument stays null; a query failing validation answers 422 (ADR-0005).
        #[MapQueryString(validationFailedStatusCode: 422)]
        ?ListTasksQuery $query = null,
    ): JsonResponse {
        return $this->json(['items' => ($this->listTasks)(new ListTasks($query?->status))]);
    }
}
