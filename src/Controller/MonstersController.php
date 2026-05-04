<?php

namespace App\Controller;

use App\Service\FastApiService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/monsters', name: 'monsters_')]
#[IsGranted('ROLE_USER')]
#[OA\Tag(name: 'Monsters')]
class MonstersController extends AbstractController
{
    public function __construct(
        private FastApiService $fastApiService,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(summary: 'Récupérer les monstres possédés de l\'import actif')]
    public function list(): JsonResponse
    {
        $userId = $this->getUser()->getId();

        $result = $this->fastApiService->getMonsters($userId);

        if (isset($result['error'])) {
            return $this->json(
                ['message' => $result['message']],
                Response::HTTP_BAD_GATEWAY
            );
        }

        if (isset($result['detail']) && str_contains($result['detail'], 'import actif')) {
            return $this->json(
                ['message' => 'Aucun import trouvé. Veuillez importer votre JSON SW.'],
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->json($result);
    }
}
