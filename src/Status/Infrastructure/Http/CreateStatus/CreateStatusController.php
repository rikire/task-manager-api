<?php

declare(strict_types=1);

namespace App\Status\Infrastructure\Http\CreateStatus;

use App\Status\Application\CreateStatus\CreateStatus;
use App\Status\Application\CreateStatus\CreateStatusHandler;
use App\Status\Application\StatusView;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/** REQ-STATUS-create: POST /api/statuses → 201, the status and its Location (ADR-0007 D4). */
#[OA\Tag(name: 'Statuses')]
final class CreateStatusController extends AbstractController
{
    public function __construct(private readonly CreateStatusHandler $createStatus)
    {
    }

    #[Route('/api/statuses', name: 'status_create', methods: ['POST'])]
    #[OA\Response(response: 201, description: 'The created status', content: new OA\JsonContent(ref: new Model(type: StatusView::class)))]
    #[OA\Response(response: 400, description: 'Malformed JSON', content: new OA\JsonContent(ref: '#/components/schemas/Problem'))]
    #[OA\Response(response: 409, description: 'A status with this name exists', content: new OA\JsonContent(ref: '#/components/schemas/Problem'))]
    #[OA\Response(response: 415, description: 'The body is not JSON', content: new OA\JsonContent(ref: '#/components/schemas/Problem'))]
    #[OA\Response(response: 422, description: 'The body breaks a rule; violations per field', content: new OA\JsonContent(ref: '#/components/schemas/Problem'))]
    public function __invoke(
        // An empty body is validated as {} and an unknown field is a violation, not a 500 (ADR-0005, amended).
        #[MapRequestPayload(serializationContext: [
            AbstractObjectNormalizer::ALLOW_EXTRA_ATTRIBUTES => false,
            DenormalizerInterface::COLLECT_EXTRA_ATTRIBUTES_ERRORS => true,
        ], mapWhenEmpty: true)]
        CreateStatusRequest $request,
    ): JsonResponse {
        $status = ($this->createStatus)(new CreateStatus($request->name, $request->title));

        return $this->json($status, Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('status_get', ['id' => $status->id]),
        ]);
    }
}
