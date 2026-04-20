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

#[Route('/stats', name: 'stats_')]
#[IsGranted('ROLE_USER')]
#[OA\Tag(name: 'Stats')]
class StatsController extends AbstractController
{
    public function __construct(
        private FastApiService $fastApiService,
    ) {}

    #[Route('/top-sets', name: 'top_sets', methods: ['GET'])]
    public function topSets(Request $request): JsonResponse
    {
        $userId = $this->getUser()->getId();
        $limit  = $request->query->getInt('limit', 5);
        return $this->json($this->fastApiService->getTopSets($userId, $limit));
    }

    #[Route('/top3-by-stat', name: 'top3_by_stat', methods: ['GET'])]
    public function top3ByStat(Request $request): JsonResponse
    {
        $userId   = $this->getUser()->getId();
        $statCode = $request->query->get('stat_code');
        $minPct   = $request->query->get('min_pct', 10);

        if (!$statCode) {
            return $this->json(['message' => 'stat_code est requis.'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->fastApiService->getTop3ByStat($userId, $statCode, (float) $minPct));
    }

    #[Route('/total-runes', name: 'total_runes', methods: ['GET'])]
    public function totalRunes(): JsonResponse
    {
        $userId = $this->getUser()->getId();
        return $this->json($this->fastApiService->getTotalRunes($userId));
    }

    #[Route('/available-pri-stats', name: 'available_pri_stats', methods: ['GET'])]
    public function availablePriStats(Request $request): JsonResponse
    {
        $userId = $this->getUser()->getId();
        $setId  = $request->query->getInt('set_id');

        if (!$setId) {
            return $this->json(['message' => 'set_id est requis.'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->fastApiService->getAvailablePriStats($userId, $setId));
    }
}
