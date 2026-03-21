<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\Threads;

use Janwebdev\SocialPostBundle\Http\ClientInterface;
use Janwebdev\SocialPostBundle\Provider\Exception\ProviderException;

/**
 * Threads API client.
 *
 * @since 3.1.0
 * @license https://opensource.org/licenses/MIT
 * @see https://developers.facebook.com/docs/threads
 */
readonly class ThreadsClient
{
    private const API_BASE_URL = 'https://graph.threads.net';

    public function __construct(
        private ClientInterface $httpClient,
        private string $userId,
        private string $accessToken,
        private string $apiVersion = 'v1.0',
    ) {
    }

    public function isConfigured(): bool
    {
        return !empty($this->userId) && !empty($this->accessToken);
    }

    /**
     * Create a media container (step 1 of Threads publishing).
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createMediaContainer(array $data): array
    {
        $url = $this->buildUrl("/{$this->userId}/threads");

        // Add access token and media type
        $data['access_token'] = $this->accessToken;
        $data['media_type'] = $this->determineMediaType($data);

        $response = $this->httpClient->post($url, [], $data);

        if (!$response->isSuccessful()) {
            $error = $response->toArray();
            $errorMessage = $error['error']['message'] ?? 'Unknown error';
            throw new ProviderException("Threads API error: {$errorMessage}");
        }

        return $response->toArray();
    }

    /**
     * Publish a media container (step 2 of Threads publishing).
     *
     * @return array<string, mixed>
     */
    public function publishMediaContainer(string $containerId): array
    {
        $url = $this->buildUrl("/{$this->userId}/threads_publish");

        $data = [
            'creation_id' => $containerId,
            'access_token' => $this->accessToken,
        ];

        $response = $this->httpClient->post($url, [], $data);

        if (!$response->isSuccessful()) {
            $error = $response->toArray();
            $errorMessage = $error['error']['message'] ?? 'Unknown error';
            throw new ProviderException("Threads publish error: {$errorMessage}");
        }

        return $response->toArray();
    }

    /**
     * Check container status.
     *
     * @return array<string, mixed>
     */
    public function getContainerStatus(string $containerId): array
    {
        $url = $this->buildUrl("/{$containerId}");
        
        $params = [
            'fields' => 'status,error_message',
            'access_token' => $this->accessToken,
        ];

        $response = $this->httpClient->get($url, [], $params);

        if (!$response->isSuccessful()) {
            throw new ProviderException("Failed to get container status");
        }

        return $response->toArray();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function determineMediaType(array $data): string
    {
        if (isset($data['image_url'])) {
            return 'IMAGE';
        }

        return 'TEXT';
    }

    private function buildUrl(string $endpoint): string
    {
        return self::API_BASE_URL . '/' . $this->apiVersion . $endpoint;
    }
}
