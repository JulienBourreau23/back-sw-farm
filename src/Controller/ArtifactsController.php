<?php

namespace App\Controller;

use App\Service\FastApiService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/artifacts', name: 'artifacts_')]
#[IsGranted('ROLE_USER')]
#[OA\Tag(name: 'Artifacts')]
class ArtifactsController extends AbstractController
{
    public function __construct(
        private FastApiService $fastApiService,
    ) {}

    #[Route('/averages', name: 'averages', methods: ['GET'])]
    #[OA\Get(
        summary: 'Récupérer les moyennes des effets secondaires d\'artefacts',
        parameters: [
            new OA\Parameter(name: 'type',          in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: '1=élémentaire, 2=style'),
            new OA\Parameter(name: 'attribute',     in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: '1=Feu 2=Eau 3=Vent 4=Lum 5=Tén'),
            new OA\Parameter(name: 'unit_style',    in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: '1=ATQ 2=DEF 3=PV 4=Support'),
            new OA\Parameter(name: 'pri_effect_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: '100=HP 101=ATK 102=DEF'),
            new OA\Parameter(name: 'min_level',     in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'Niveau minimum (0-15)'),
        ]
    )]
    public function averages(Request $request): JsonResponse
    {
        $userId = $this->getUser()->getId();

        $importId = $this->fastApiService->getActiveImportId($userId);

        if (!$importId) {
            return $this->json(
                ['message' => 'Aucun import trouvé. Veuillez importer votre JSON SW.'],
                Response::HTTP_NOT_FOUND
            );
        }

        $params = array_filter([
            'type'          => $request->query->get('type'),
            'attribute'     => $request->query->get('attribute'),
            'unit_style'    => $request->query->get('unit_style'),
            'pri_effect_id' => $request->query->get('pri_effect_id'),
            'min_level'     => $request->query->get('min_level'),
        ], fn($v) => $v !== null && $v !== '');

        $result = $this->fastApiService->getArtifactAverages($userId, $importId, $params);

        return $this->json($result, isset($result['error']) ? Response::HTTP_BAD_GATEWAY : Response::HTTP_OK);
    }

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    #[OA\Get(summary: 'Statistiques globales des artefacts (comptages par type/attribut/style/stat principale)')]
    public function stats(): JsonResponse
    {
        $userId = $this->getUser()->getId();

        $importId = $this->fastApiService->getActiveImportId($userId);

        if (!$importId) {
            return $this->json(
                ['message' => 'Aucun import trouvé. Veuillez importer votre JSON SW.'],
                Response::HTTP_NOT_FOUND
            );
        }

        $result = $this->fastApiService->getArtifactStats($userId, $importId);

        return $this->json($result, isset($result['error']) ? Response::HTTP_BAD_GATEWAY : Response::HTTP_OK);
    }
}
