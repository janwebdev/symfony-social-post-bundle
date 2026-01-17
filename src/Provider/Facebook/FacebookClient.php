<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\Facebook;

use Janwebdev\SocialPostBundle\Http\ClientInterface;
use Janwebdev\SocialPostBundle\Provider\Exception\ProviderException;

/**
 * Facebook Graph API client.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
final readonly class FacebookClient
{
    private const API_BASE_URL = 'https://graph.facebook.com';

    public function __construct(
        private ClientInterface $httpClient,
        private string $pageId,
        private string $accessToken,
        private string $graphVersion = 'v20.0',
    ) {
    }

    public function isConfigured(): bool
    {
        return !empty($this->pageId) && !empty($this->accessToken);
    }

    /**
     * Create a post on Facebook page.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createPost(array $data): array
    {
        // Determine endpoint based on content type
        $endpoint = $this->getEndpoint($data);
        $url = $this->buildUrl($endpoint);

        // Add access token to data
        $data['access_token'] = $this->accessToken;

        $response = $this->httpClient->post($url, [], $data);

        if (!$response->isSuccessful()) {
            $error = $response->toArray();
            $errorMessage = $error['error']['message'] ?? 'Unknown error';
            throw new ProviderException("Facebook API error: {$errorMessage}");
        }

        return $response->toArray();
    }

    /**
     * Upload a photo to Facebook.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function uploadPhoto(array $data): array
    {
        $url = $this->buildUrl("/{$this->pageId}/photos");
        $data['access_token'] = $this->accessToken;

        $response = $this->httpClient->post($url, [], $data);

        if (!$response->isSuccessful()) {
            $error = $response->toArray();
            $errorMessage = $error['error']['message'] ?? 'Unknown error';
            throw new ProviderException("Facebook photo upload error: {$errorMessage}");
        }

        return $response->toArray();
    }

    private function getEndpoint(array $data): string
    {
        // If there's a photo URL, use photos endpoint
        if (isset($data['url'])) {
            return "/{$this->pageId}/photos";
        }

        // Default to feed endpoint
        return "/{$this->pageId}/feed";
    }

    private function buildUrl(string $endpoint): string
    {
        return self::API_BASE_URL . '/' . $this->graphVersion . $endpoint;
    }
}
