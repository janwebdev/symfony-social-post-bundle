<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\Instagram;

use Janwebdev\SocialPostBundle\Http\ClientInterface;
use Janwebdev\SocialPostBundle\Provider\Exception\ProviderException;

/**
 * Instagram Graph API client.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
readonly class InstagramClient
{
    private const API_BASE_URL = 'https://graph.facebook.com';

    public function __construct(
        private ClientInterface $httpClient,
        private string $instagramAccountId,
        private string $accessToken,
        private string $graphVersion = 'v20.0',
    ) {
    }

    public function isConfigured(): bool
    {
        return !empty($this->instagramAccountId) && !empty($this->accessToken);
    }

    /**
     * Create a media container (step 1 of Instagram publishing).
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createMediaContainer(array $data): array
    {
        $url = $this->buildUrl("/{$this->instagramAccountId}/media");

        // Add access token
        $data['access_token'] = $this->accessToken;

        $response = $this->httpClient->post($url, [], $data);

        if (!$response->isSuccessful()) {
            $error = $response->toArray();
            $errorMessage = $error['error']['message'] ?? 'Unknown error';
            throw new ProviderException("Instagram API error: {$errorMessage}");
        }

        return $response->toArray();
    }

    /**
     * Publish a media container (step 2 of Instagram publishing).
     *
     * @return array<string, mixed>
     */
    public function publishMediaContainer(string $containerId): array
    {
        $url = $this->buildUrl("/{$this->instagramAccountId}/media_publish");

        $data = [
            'creation_id' => $containerId,
            'access_token' => $this->accessToken,
        ];

        $response = $this->httpClient->post($url, [], $data);

        if (!$response->isSuccessful()) {
            $error = $response->toArray();
            $errorMessage = $error['error']['message'] ?? 'Unknown error';
            throw new ProviderException("Instagram publish error: {$errorMessage}");
        }

        return $response->toArray();
    }

    /**
     * Check container status (useful for async operations).
     *
     * @return array<string, mixed>
     */
    public function getContainerStatus(string $containerId): array
    {
        $url = $this->buildUrl("/{$containerId}");
        
        $params = [
            'fields' => 'status_code,status',
            'access_token' => $this->accessToken,
        ];

        $response = $this->httpClient->get($url, [], $params);

        if (!$response->isSuccessful()) {
            throw new ProviderException("Failed to get container status");
        }

        return $response->toArray();
    }

    private function buildUrl(string $endpoint): string
    {
        return self::API_BASE_URL . '/' . $this->graphVersion . $endpoint;
    }
}
