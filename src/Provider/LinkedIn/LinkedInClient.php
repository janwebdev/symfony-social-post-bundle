<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\LinkedIn;

use Janwebdev\SocialPostBundle\Http\ClientInterface;
use Janwebdev\SocialPostBundle\Message\Attachment\AttachmentInterface;
use Janwebdev\SocialPostBundle\Provider\Exception\ProviderException;

/**
 * LinkedIn Community Management API client.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 * @see https://learn.microsoft.com/en-us/linkedin/marketing/community-management/shares/posts-api
 */
readonly class LinkedInClient
{
    private const API_BASE_URL = 'https://api.linkedin.com/rest';

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
     * Create a post using LinkedIn Community Management API.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createPost(array $data): array
    {
        $url = self::API_BASE_URL . '/posts';

        $textData = isset($data['text']) && is_array($data['text']) ? $data['text'] : [];
        $commentary = isset($textData['text']) && is_string($textData['text']) ? $textData['text'] : '';

        $payload = [
            'author' => 'urn:li:organization:' . $this->organizationId,
            'commentary' => $commentary,
            'visibility' => 'PUBLIC',
            'distribution' => [
                'feedDistribution' => 'MAIN_FEED',
                'targetEntities' => [],
                'thirdPartyDistributionChannels' => [],
            ],
            'lifecycleState' => 'PUBLISHED',
            'isReshareDisabledByAuthor' => false,
        ];

        $content = isset($data['content']) && is_array($data['content']) ? $data['content'] : [];

        // Image-only post
        if (isset($content['media']) && is_array($content['media'])) {
            $media = $content['media'];
            $payload['content'] = [
                'media' => [
                    'id'      => isset($media['id']) && is_string($media['id']) ? $media['id'] : '',
                    'altText' => isset($media['altText']) && is_string($media['altText']) ? $media['altText'] : '',
                ],
            ];
        // Article post (with optional thumbnail image)
        } elseif (isset($content['article']) && is_array($content['article'])) {
            $article = $content['article'];
            $articlePayload = [
                'source'      => isset($article['source']) && is_string($article['source']) ? $article['source'] : '',
                'title'       => isset($article['title']) && is_string($article['title']) ? $article['title'] : '',
                'description' => '',
            ];
            if (isset($article['thumbnail']) && is_string($article['thumbnail'])) {
                $articlePayload['thumbnail'] = $article['thumbnail'];
            }
            $payload['content'] = ['article' => $articlePayload];
        }

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
            'X-Restli-Protocol-Version' => '2.0.0',
            'LinkedIn-Version' => '202503',
        ];

        $response = $this->httpClient->post($url, $headers, $payload);

        if (!$response->isSuccessful()) {
            $error = $response->toArray();
            $errorMessage = isset($error['message']) && is_string($error['message']) ? $error['message'] : 'Unknown error';
            throw new ProviderException("LinkedIn API error: {$errorMessage}");
        }

        // New API returns post URN in x-restli-id header; body is empty
        $responseHeaders = $response->getHeaders();
        $restliHeader = isset($responseHeaders['x-restli-id']) && is_array($responseHeaders['x-restli-id'])
            ? $responseHeaders['x-restli-id']
            : [];
        $linkedinHeader = isset($responseHeaders['x-linkedin-id']) && is_array($responseHeaders['x-linkedin-id'])
            ? $responseHeaders['x-linkedin-id']
            : [];

        $postId = (isset($restliHeader[0]) && is_string($restliHeader[0]))
            ? $restliHeader[0]
            : ((isset($linkedinHeader[0]) && is_string($linkedinHeader[0])) ? $linkedinHeader[0] : null);

        return ['id' => $postId];
    }

    /**
     * Initialize an image upload and return upload URL and image URN.
     *
     * @return array{uploadUrl: string, imageUrn: string}
     */
    private function initializeImageUpload(): array
    {
        $url = self::API_BASE_URL . '/images?action=initializeUpload';

        $payload = [
            'initializeUploadRequest' => [
                'owner' => 'urn:li:organization:' . $this->organizationId,
            ],
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
            'X-Restli-Protocol-Version' => '2.0.0',
            'LinkedIn-Version' => '202503',
        ];

        $response = $this->httpClient->post($url, $headers, $payload);

        if (!$response->isSuccessful()) {
            $error = $response->toArray();
            $errorMessage = isset($error['message']) && is_string($error['message']) ? $error['message'] : 'Unknown error';
            throw new ProviderException("LinkedIn image initialize failed: {$errorMessage}");
        }

        $data = $response->toArray();
        $value = isset($data['value']) && is_array($data['value']) ? $data['value'] : [];
        $uploadUrl = isset($value['uploadUrl']) && is_string($value['uploadUrl'])
            ? $value['uploadUrl']
            : null;
        $imageUrn = isset($value['image']) && is_string($value['image'])
            ? $value['image']
            : null;

        if ($uploadUrl === null || $imageUrn === null) {
            throw new ProviderException('LinkedIn image initialize returned unexpected response');
        }

        return ['uploadUrl' => $uploadUrl, 'imageUrn' => $imageUrn];
    }

    private function uploadImageBinary(string $uploadUrl, string $binary): void
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/octet-stream',
        ];

        $response = $this->httpClient->put($uploadUrl, $headers, $binary);

        if (!$response->isSuccessful()) {
            throw new ProviderException(
                sprintf(
                    'LinkedIn image binary upload failed: HTTP %d — %s',
                    $response->getStatusCode(),
                    $response->getBody(),
                )
            );
        }
    }

    public function uploadImage(AttachmentInterface $attachment): string
    {
        $path = $attachment->getPath();

        if ($attachment->isLocal()) {
            if (!file_exists($path)) {
                throw new ProviderException("File not found: {$path}");
            }
            $binary = file_get_contents($path);
            if ($binary === false) {
                throw new ProviderException("Failed to read file: {$path}");
            }
        } else {
            $response = $this->httpClient->get($path);
            if (!$response->isSuccessful()) {
                throw new ProviderException("Failed to download image from: {$path}");
            }
            $binary = $response->getBody();
        }

        $result = $this->initializeImageUpload();
        $this->uploadImageBinary($result['uploadUrl'], $binary);

        return $result['imageUrn'];
    }
}
