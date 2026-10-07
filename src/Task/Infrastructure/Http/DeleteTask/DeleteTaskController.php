<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Http\DeleteTask;

use App\Task\Application\DeleteTask\DeleteTask;
use App\Task\Application\DeleteTask\DeleteTaskHandler;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

/** REQ-TASK-delete: DELETE /api/tasks/{id} → 204 without a body; unknown or non-UUID id → 404 (ADR-0007 D4, D5). */
#[OA\Tag(name: 'Tasks')]
final class DeleteTaskController extends AbstractController
{
    public function __construct(private readonly DeleteTaskHandler $deleteTask)
    {
    }

    #[Route('/api/tasks/{id}', name: 'task_delete', requirements: ['id' => Requirement::UUID], methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'The task is deleted')]
    #[OA\Response(response: 404, description: 'No task has this id', content: new OA\JsonContent(ref: '#/components/schemas/Problem'))]
    public function __invoke(string $id): Response
    {
        ($this->deleteTask)(new DeleteTask($id));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
