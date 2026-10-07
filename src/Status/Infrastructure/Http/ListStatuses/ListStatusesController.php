<?php

declare(strict_types=1);

namespace App\Status\Infrastructure\Http\ListStatuses;

use App\Status\Application\ListStatuses\ListStatusesHandler;
use App\Status\Application\StatusView;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/** REQ-STATUS-read.list: GET /api/statuses → {"items": [...]} in creation order (ADR-0007 D4). */
#[OA\Tag(name: 'Statuses')]
final class ListStatusesController extends AbstractController
{
    public function __construct(private readonly ListStatusesHandler $listStatuses)
    {
    }

    #[Route('/api/statuses', name: 'status_list', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Every status in creation order',
        content: new OA\JsonContent(
            required: ['items'],
            properties: [new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: StatusView::class)))],
        ),
    )]
    public function __invoke(): JsonResponse
    {
        return $this->json(['items' => ($this->listStatuses)()]);
    }
}
