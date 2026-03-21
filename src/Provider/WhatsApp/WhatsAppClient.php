<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\WhatsApp;

use Janwebdev\SocialPostBundle\Http\ClientInterface;
use Janwebdev\SocialPostBundle\Provider\Exception\ProviderException;

/**
 * WhatsApp Channel API client (BETA).
 *
 * WARNING: This API is in beta and may change without notice.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
readonly class WhatsAppClient
{
    private const API_BASE_URL = 'https://graph.facebook.com';

    public function __construct(
        private ClientInterface $httpClient,
        private string $phoneNumberId,
        private string $accessToken,
        private string $apiVersion = 'v22.0',
    ) {
    }

    public function isConfigured(): bool
    {
        return !empty($this->phoneNumberId) && !empty($this->accessToken);
    }

    /**
     * Send a message to WhatsApp Channel.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function sendMessage(array $payload): array
    {
        $url = $this->buildUrl("/{$this->phoneNumberId}/messages");

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];

        $response = $this->httpClient->post($url, $headers, $payload);

        if (!$response->isSuccessful()) {
            $error = $response->toArray();
            $errorMessage = $error['error']['message'] ?? 'Unknown error';
            $errorCode = $error['error']['code'] ?? $response->getStatusCode();
            
            throw new ProviderException(
                "WhatsApp API error ({$errorCode}): {$errorMessage}"
            );
        }

        return $response->toArray();
    }

    private function buildUrl(string $endpoint): string
    {
        return self::API_BASE_URL . '/' . $this->apiVersion . $endpoint;
    }
}
