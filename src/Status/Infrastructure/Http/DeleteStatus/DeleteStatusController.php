<?php

declare(strict_types=1);

namespace App\Status\Infrastructure\Http\DeleteStatus;

use App\Status\Application\DeleteStatus\DeleteStatus;
use App\Status\Application\DeleteStatus\DeleteStatusHandler;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

/** REQ-STATUS-delete: DELETE /api/statuses/{id} → 204; `new` or a status in use → 409 (ADR-0007 D2). */
#[OA\Tag(name: 'Statuses')]
final class DeleteStatusController extends AbstractController
{
    public function __construct(private readonly DeleteStatusHandler $deleteStatus)
    {
    }

    #[Route('/api/statuses/{id}', name: 'status_delete', requirements: ['id' => Requirement::UUID], methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'The status is deleted')]
    #[OA\Response(response: 404, description: 'No status has this id', content: new OA\JsonContent(ref: '#/components/schemas/Problem'))]
    #[OA\Response(response: 409, description: 'The status is `new` or used by tasks', content: new OA\JsonContent(ref: '#/components/schemas/Problem'))]
    public function __invoke(string $id): Response
    {
        ($this->deleteStatus)(new DeleteStatus($id));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
