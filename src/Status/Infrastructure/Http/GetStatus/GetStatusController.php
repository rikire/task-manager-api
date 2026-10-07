<?php

declare(strict_types=1);

namespace App\Status\Infrastructure\Http\GetStatus;

use App\Status\Application\GetStatus\GetStatus;
use App\Status\Application\GetStatus\GetStatusHandler;
use App\Status\Application\StatusView;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

/**
 * REQ-STATUS-read.get: GET /api/statuses/{id}. A path id that is not a lowercase UUID matches no route
 * and answers 404 (ADR-0007 D5); an unknown id answers 404 through StatusNotFound.
 */
#[OA\Tag(name: 'Statuses')]
final class GetStatusController extends AbstractController
{
    public function __construct(private readonly GetStatusHandler $getStatus)
    {
    }

    #[Route('/api/statuses/{id}', name: 'status_get', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[OA\Response(response: 200, description: 'The status', content: new OA\JsonContent(ref: new Model(type: StatusView::class)))]
    #[OA\Response(response: 404, description: 'No status has this id', content: new OA\JsonContent(ref: '#/components/schemas/Problem'))]
    public function __invoke(string $id): JsonResponse
    {
        return $this->json(($this->getStatus)(new GetStatus($id)));
    }
}
