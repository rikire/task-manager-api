<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Http\CreateTask;

use App\Task\Application\CreateTask\CreateTask;
use App\Task\Application\CreateTask\CreateTaskHandler;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/** REQ-TASK-create: POST /api/tasks → 201, the task with status `new` and its Location (ADR-0007 D1, D4). */
#[OA\Tag(name: 'Tasks')]
final class CreateTaskController extends AbstractController
{
    public function __construct(private readonly CreateTaskHandler $createTask)
    {
    }

    #[Route('/api/tasks', name: 'task_create', methods: ['POST'])]
    #[OA\Response(response: 201, description: 'The created task', content: new OA\JsonContent(ref: '#/components/schemas/Task'))]
    #[OA\Response(response: 400, description: 'Malformed JSON', content: new OA\JsonContent(ref: '#/components/schemas/Problem'))]
    #[OA\Response(response: 415, description: 'The body is not JSON', content: new OA\JsonContent(ref: '#/components/schemas/Problem'))]
    #[OA\Response(response: 422, description: 'The body breaks a rule; violations per field', content: new OA\JsonContent(ref: '#/components/schemas/Problem'))]
    public function __invoke(
        // An empty body is validated as {} and an unknown field — `status` included — is a violation (ADR-0005, ADR-0007).
        #[MapRequestPayload(serializationContext: [
            AbstractObjectNormalizer::ALLOW_EXTRA_ATTRIBUTES => false,
            DenormalizerInterface::COLLECT_EXTRA_ATTRIBUTES_ERRORS => true,
        ], mapWhenEmpty: true)]
        CreateTaskRequest $request,
    ): JsonResponse {
        $task = ($this->createTask)(new CreateTask($request->title, $request->description));

        return $this->json($task, Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('task_get', ['id' => $task->id]),
        ]);
    }
}
