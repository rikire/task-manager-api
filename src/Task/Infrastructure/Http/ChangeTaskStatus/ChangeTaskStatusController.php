<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Http\ChangeTaskStatus;

use App\Task\Application\ChangeTaskStatus\ChangeTaskStatus;
use App\Task\Application\ChangeTaskStatus\ChangeTaskStatusHandler;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/** REQ-TASK-status-change: PATCH /api/tasks/{id}/status {"status": "<name>"} → 200 with the task. */
#[OA\Tag(name: 'Tasks')]
final class ChangeTaskStatusController extends AbstractController
{
    public function __construct(private readonly ChangeTaskStatusHandler $changeTaskStatus)
    {
    }

    #[Route('/api/tasks/{id}/status', name: 'task_change_status', requirements: ['id' => Requirement::UUID], methods: ['PATCH'])]
    #[OA\Response(response: 200, description: 'The task with its status', content: new OA\JsonContent(ref: '#/components/schemas/Task'))]
    #[OA\Response(response: 400, description: 'Malformed JSON', content: new OA\JsonContent(ref: '#/components/schemas/Problem'))]
    #[OA\Response(response: 404, description: 'No task has this id', content: new OA\JsonContent(ref: '#/components/schemas/Problem'))]
    #[OA\Response(response: 415, description: 'The body is not JSON', content: new OA\JsonContent(ref: '#/components/schemas/Problem'))]
    #[OA\Response(response: 422, description: 'The body breaks a rule, or names no status', content: new OA\JsonContent(ref: '#/components/schemas/Problem'))]
    public function __invoke(
        string $id,
        // Same body rules as the create requests: empty body as {}, unknown fields refused (ADR-0005).
        #[MapRequestPayload(serializationContext: [
            AbstractObjectNormalizer::ALLOW_EXTRA_ATTRIBUTES => false,
            DenormalizerInterface::COLLECT_EXTRA_ATTRIBUTES_ERRORS => true,
        ], mapWhenEmpty: true)]
        ChangeTaskStatusRequest $request,
    ): JsonResponse {
        return $this->json(($this->changeTaskStatus)(new ChangeTaskStatus($id, $request->status)));
    }
}
