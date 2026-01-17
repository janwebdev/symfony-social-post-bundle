<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\Twitter;

use Janwebdev\SocialPostBundle\Http\ClientInterface;
use Janwebdev\SocialPostBundle\Message\Attachment\AttachmentInterface;
use Janwebdev\SocialPostBundle\Provider\Exception\ProviderException;

/**
 * Twitter API v2 client with OAuth 1.0a authentication.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
final readonly class TwitterClient
{
    private const API_BASE_URL = 'https://api.twitter.com/2';
    private const UPLOAD_BASE_URL = 'https://upload.twitter.com/1.1';

    public function __construct(
        private ClientInterface $httpClient,
        private string $apiKey,
        private string $apiSecret,
        private string $accessToken,
        private string $accessTokenSecret,
    ) {
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey)
            && !empty($this->apiSecret)
            && !empty($this->accessToken)
            && !empty($this->accessTokenSecret);
    }

    /**
     * Create a tweet using Twitter API v2.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createTweet(array $data): array
    {
        $url = self::API_BASE_URL . '/tweets';
        $headers = $this->getAuthHeaders('POST', $url, $data);
        $headers['Content-Type'] = 'application/json';

        $response = $this->httpClient->post($url, $headers, $data);

        if (!$response->isSuccessful()) {
            throw new ProviderException(
                "Twitter API error: {$response->getBody()}",
            );
        }

        return $response->toArray();
    }

    /**
     * Upload media attachments and return media IDs.
     *
     * @param array<AttachmentInterface> $attachments
     * @return array<string>
     */
    public function uploadMedia(array $attachments): array
    {
        $mediaIds = [];

        foreach ($attachments as $attachment) {
            if ($attachment->getType() !== 'image') {
                continue; // For now, only support images
            }

            try {
                $mediaId = $this->uploadSingleMedia($attachment);
                if ($mediaId) {
                    $mediaIds[] = $mediaId;
                }
            } catch (\Throwable $e) {
                // Log but continue with other attachments
                continue;
            }
        }

        return $mediaIds;
    }

    private function uploadSingleMedia(AttachmentInterface $attachment): ?string
    {
        $url = self::UPLOAD_BASE_URL . '/media/upload.json';
        
        // Read file content
        $filePath = $attachment->getPath();
        if ($attachment->isLocal()) {
            if (!file_exists($filePath)) {
                throw new ProviderException("File not found: {$filePath}");
            }
            $fileContent = file_get_contents($filePath);
        } else {
            // Download from URL
            $fileContent = file_get_contents($filePath);
        }

        if ($fileContent === false) {
            throw new ProviderException("Failed to read file: {$filePath}");
        }

        $boundary = uniqid('', true);
        $headers = $this->getAuthHeaders('POST', $url);
        $headers['Content-Type'] = "multipart/form-data; boundary={$boundary}";

        $body = $this->buildMultipartBody($boundary, $fileContent, basename($filePath));

        $response = $this->httpClient->post($url, $headers, $body);

        if (!$response->isSuccessful()) {
            throw new ProviderException("Failed to upload media: {$response->getBody()}");
        }

        $data = $response->toArray();
        return $data['media_id_string'] ?? null;
    }

    private function buildMultipartBody(string $boundary, string $fileContent, string $filename): string
    {
        $body = "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"media\"; filename=\"{$filename}\"\r\n";
        $body .= "Content-Type: application/octet-stream\r\n\r\n";
        $body .= $fileContent . "\r\n";
        $body .= "--{$boundary}--\r\n";

        return $body;
    }

    /**
     * Generate OAuth 1.0a headers for Twitter API.
     *
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function getAuthHeaders(string $method, string $url, array $data = []): array
    {
        $oauthParams = [
            'oauth_consumer_key' => $this->apiKey,
            'oauth_nonce' => bin2hex(random_bytes(16)),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => (string) time(),
            'oauth_token' => $this->accessToken,
            'oauth_version' => '1.0',
        ];

        // Create signature base string
        $params = array_merge($oauthParams, $method === 'GET' ? $data : []);
        ksort($params);
        
        $paramString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $baseString = strtoupper($method) . '&' . rawurlencode($url) . '&' . rawurlencode($paramString);

        // Generate signature
        $signingKey = rawurlencode($this->apiSecret) . '&' . rawurlencode($this->accessTokenSecret);
        $signature = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));
        
        $oauthParams['oauth_signature'] = $signature;

        // Build Authorization header
        $authHeader = 'OAuth ';
        $headerParts = [];
        foreach ($oauthParams as $key => $value) {
            $headerParts[] = rawurlencode($key) . '="' . rawurlencode($value) . '"';
        }
        $authHeader .= implode(', ', $headerParts);

        return ['Authorization' => $authHeader];
    }
}
