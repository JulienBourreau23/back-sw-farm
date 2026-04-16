<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
            $response = $this->httpClient->request('POST', "{$this->fastApiUrl}/import/{$userId}", [
                'headers' => $this->headers(),
                'body'    => [
                    'file' => fopen($file->getPathname(), 'r'),
                ],
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            return ['error' => true, 'message' => 'Erreur lors de l\'import : ' . $e->getMessage()];
        }
    }

    public function getActiveImportId(int $userId): ?int
    {
        try {
            $response = $this->httpClient->request('GET', "{$this->fastApiUrl}/import/{$userId}/active", [
                'headers' => $this->headers(),
            ]);

            $data = $response->toArray();
            return $data['import_id'] ?? null;
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
}
