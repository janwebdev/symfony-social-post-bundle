<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\LinkedIn;

use Janwebdev\SocialPostBundle\Http\ClientInterface;
use Janwebdev\SocialPostBundle\Provider\Exception\ProviderException;

/**
 * LinkedIn API v2 client.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
readonly class LinkedInClient
{
    private const API_BASE_URL = 'https://api.linkedin.com/v2';

    public function __construct(
        private ClientInterface $httpClient,
        private string $organizationId,
        private string $accessToken,
    ) {
    }

    public function isConfigured(): bool
    {
        return !empty($this->organizationId) && !empty($this->accessToken);
    }

    /**
     * Create a share/post on LinkedIn.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createShare(array $data): array
    {
        $url = self::API_BASE_URL . '/ugcPosts';

        // Build complete share payload
        $payload = [
            'author' => 'urn:li:organization:' . $this->organizationId,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => $data['text'],
                    'shareMediaCategory' => 'NONE',
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        // Add media if present
        if (isset($data['content'])) {
            if (isset($data['content']['article'])) {
                $payload['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'ARTICLE';
                $payload['specificContent']['com.linkedin.ugc.ShareContent']['media'] = [[
                    'status' => 'READY',
                    'originalUrl' => $data['content']['article']['source'],
                ]];
            }
        }

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
            'X-Restli-Protocol-Version' => '2.0.0',
        ];

        $response = $this->httpClient->post($url, $headers, $payload);

        if (!$response->isSuccessful()) {
            $error = $response->toArray();
            $errorMessage = $error['message'] ?? 'Unknown error';
            throw new ProviderException("LinkedIn API error: {$errorMessage}");
        }

        return $response->toArray();
    }
}
