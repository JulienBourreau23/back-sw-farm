<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FastApiService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%env(FASTAPI_URL)%')]
        private string $fastApiUrl,
        #[Autowire('%env(FASTAPI_SECRET)%')]
        private string $fastApiSecret,
    ) {}

    private function headers(): array
    {
        return ['X-API-Secret' => $this->fastApiSecret];
    }

    public function importJson(int $userId, UploadedFile $file): array
    {
        try {
            $tempPath = $file->getRealPath();
            if (!$tempPath || !file_exists($tempPath)) {
                return ['error' => true, 'message' => 'Fichier temporaire introuvable.'];
            }
            $formData = new FormDataPart([
                'file' => DataPart::fromPath($tempPath, $file->getClientOriginalName(), 'application/json'),
            ]);
            $response = $this->httpClient->request('POST', "{$this->fastApiUrl}/import/{$userId}", [
                'headers' => array_merge($this->headers(), $formData->getPreparedHeaders()->toArray()),
                'body'    => $formData->bodyToString(),
            ]);
            return $response->toArray();
        } catch (\Exception $e) {
            return ['error' => true, 'message' => 'Erreur import : ' . $e->getMessage()];
        }
    }

    public function getActiveImportId(int $userId): ?int
    {
        try {
            $response = $this->httpClient->request('GET', "{$this->fastApiUrl}/import/{$userId}/active", [
                'headers' => $this->headers(),
            ]);
            return $response->toArray()['import_id'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getAverages(int $userId, int $importId, array $params = []): array
    {
        try {
            $response = $this->httpClient->request('GET', "{$this->fastApiUrl}/averages/{$userId}/{$importId}", [
                'headers' => $this->headers(),
                'query'   => $params,
            ]);
            return $response->toArray();
        } catch (\Exception $e) {
            return ['error' => true, 'message' => 'Erreur FastAPI : ' . $e->getMessage()];
        }
    }

    public function getAveragesAncient(int $userId, int $importId, array $params = []): array
    {
        return $this->getAverages($userId, $importId, array_merge($params, ['is_ancient' => 'true']));
    }

    public function getAveragesNormal(int $userId, int $importId, array $params = []): array
    {
        return $this->getAverages($userId, $importId, array_merge($params, ['is_ancient' => 'false']));
    }

    public function getTopSets(int $userId, int $limit = 5): array
    {
        try {
            $response = $this->httpClient->request('GET', "{$this->fastApiUrl}/stats/{$userId}/top-sets", [
                'headers' => $this->headers(),
                'query'   => ['limit' => $limit],
            ]);
            return $response->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getTop3ByStat(int $userId, string $statCode, float $minPct = 10.0): array
    {
        try {
            $response = $this->httpClient->request('GET', "{$this->fastApiUrl}/stats/{$userId}/top3-by-stat", [
                'headers' => $this->headers(),
                'query'   => ['stat_code' => $statCode, 'min_pct' => $minPct],
            ]);
            return $response->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getTotalRunes(int $userId): array
    {
        try {
            $response = $this->httpClient->request('GET', "{$this->fastApiUrl}/stats/{$userId}/total-runes", [
                'headers' => $this->headers(),
            ]);
            return $response->toArray();
        } catch (\Exception $e) {
            return ['total_runes' => 0];
        }
    }

    public function getAvailablePriStats(int $userId, int $setId): array
    {
        try {
            $response = $this->httpClient->request('GET', "{$this->fastApiUrl}/stats/{$userId}/available-pri-stats", [
                'headers' => $this->headers(),
                'query'   => ['set_id' => $setId],
            ]);
            return $response->toArray();
        } catch (\Exception $e) {
            return ['2' => [], '4' => [], '6' => []];
        }
    }

    // ── Artefacts ────────────────────────────────────────────

    public function getArtifactAverages(int $userId, int $importId, array $params = []): array
    {
        try {
            $response = $this->httpClient->request('GET', "{$this->fastApiUrl}/artifacts/{$userId}/{$importId}/averages", [
                'headers' => $this->headers(),
                'query'   => $params,
            ]);
            return $response->toArray();
        } catch (\Exception $e) {
            return ['error' => true, 'message' => 'Erreur FastAPI : ' . $e->getMessage()];
        }
    }

    public function getArtifactStats(int $userId, int $importId): array
    {
        try {
            $response = $this->httpClient->request('GET', "{$this->fastApiUrl}/artifacts/{$userId}/{$importId}/stats", [
                'headers' => $this->headers(),
            ]);
            return $response->toArray();
        } catch (\Exception $e) {
            return ['error' => true, 'message' => 'Erreur FastAPI : ' . $e->getMessage()];
        }
    }

    // ── Monstres ─────────────────────────────────────────────

    public function getMonsters(int $userId): array
    {
        try {
            $response = $this->httpClient->request('GET', "{$this->fastApiUrl}/monsters/{$userId}", [
                'headers' => $this->headers(),
            ]);
            return $response->toArray();
        } catch (\Exception $e) {
            return ['error' => true, 'message' => 'Erreur FastAPI : ' . $e->getMessage()];
        }
    }

    public function getMonsterIcon(int $unitMasterId): ?string
    {
        try {
            $response = $this->httpClient->request('GET', "{$this->fastApiUrl}/monsters/icon/{$unitMasterId}", [
                'headers' => $this->headers(),
            ]);
            if ($response->getStatusCode() !== 200) {
                return null;
            }
            return $response->getContent();
        } catch (\Exception $e) {
            return null;
        }
    }
}
