<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Http\GetTask;

use App\Task\Application\GetTask\GetTask;
use App\Task\Application\GetTask\GetTaskHandler;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

/** REQ-TASK-read: GET /api/tasks/{id}; a non-UUID id matches no route → 404 (ADR-0007 D5). */
#[OA\Tag(name: 'Tasks')]
final class GetTaskController extends AbstractController
{
    public function __construct(private readonly GetTaskHandler $getTask)
    {
    }

    #[Route('/api/tasks/{id}', name: 'task_get', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[OA\Response(response: 200, description: 'The task', content: new OA\JsonContent(ref: '#/components/schemas/Task'))]
    #[OA\Response(response: 404, description: 'No task has this id', content: new OA\JsonContent(ref: '#/components/schemas/Problem'))]
    public function __invoke(string $id): JsonResponse
    {
        return $this->json(($this->getTask)(new GetTask($id)));
    }
}
