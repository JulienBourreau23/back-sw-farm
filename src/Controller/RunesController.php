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

#[Route('/runes', name: 'runes_')]
#[IsGranted('ROLE_USER')]
#[OA\Tag(name: 'Runes')]
class RunesController extends AbstractController
{
    public function __construct(
        private FastApiService $fastApiService,
    ) {}

    #[Route('/import', name: 'import', methods: ['POST'])]
    #[OA\Post(summary: 'Importer le JSON export Summoners War')]
    public function import(Request $request): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(['message' => 'Fichier manquant.'], Response::HTTP_BAD_REQUEST);
        }

        $userId = $this->getUser()->getId();
        $result = $this->fastApiService->importJson($userId, $file);

        return $this->json($result, isset($result['error']) ? Response::HTTP_BAD_GATEWAY : Response::HTTP_OK);
    }

    #[Route('/averages', name: 'averages', methods: ['GET'])]
    #[OA\Get(
        summary: 'Récupérer les moyennes de substats',
        parameters: [
            new OA\Parameter(name: 'set_id',     in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'slot_no',    in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'pri_stat',   in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'min_upgrade',in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_ancient', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'), description: 'true=immémorial, false=normales, absent=toutes'),
            new OA\Parameter(name: 'refresh',    in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ]
    )]
    public function averages(Request $request): JsonResponse
    {
        $userId   = $this->getUser()->getId();
        $importId = $this->fastApiService->getActiveImportId($userId);

        if (!$importId) {
            return $this->json(
                ['message' => 'Aucun import trouvé. Veuillez importer votre JSON SW.'],
                Response::HTTP_NOT_FOUND
            );
        }

        $params = array_filter([
            'set_id'      => $request->query->get('set_id'),
            'slot_no'     => $request->query->get('slot_no'),
            'pri_stat'    => $request->query->get('pri_stat'),
            'min_upgrade' => $request->query->get('min_upgrade'),
            'is_ancient'  => $request->query->get('is_ancient'),
            'refresh'     => $request->query->get('refresh', 'false'),
        ], fn($v) => $v !== null && $v !== '');

        $result = $this->fastApiService->getAverages($userId, $importId, $params);

        return $this->json($result);
    }

    #[Route('/previous-averages', name: 'previous_averages', methods: ['GET'])]
    #[OA\Get(
        summary: 'Moyennes de substats de l\'import précédent (comparaison)',
        parameters: [
            new OA\Parameter(name: 'set_id',  in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'slot_no', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'pri_stat',in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ]
    )]
    public function previousAverages(Request $request): JsonResponse
    {
        $userId = $this->getUser()->getId();

        $params = array_filter([
            'set_id'  => $request->query->get('set_id'),
            'slot_no' => $request->query->get('slot_no'),
            'pri_stat'=> $request->query->get('pri_stat'),
        ], fn($v) => $v !== null && $v !== '');

        $result = $this->fastApiService->getPreviousAverages($userId, $params);

        return $this->json($result);
    }
}
