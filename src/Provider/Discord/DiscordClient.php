<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\Discord;

use Janwebdev\SocialPostBundle\Http\ClientInterface;
use Janwebdev\SocialPostBundle\Provider\Exception\ProviderException;

/**
 * Discord Webhook client.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
readonly class DiscordClient
{
    public function __construct(
        private ClientInterface $httpClient,
        private string $webhookUrl,
    ) {
    }

    public function isConfigured(): bool
    {
        return !empty($this->webhookUrl) && str_starts_with($this->webhookUrl, 'https://discord.com/api/webhooks/');
    }

    /**
     * Execute a Discord webhook.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function executeWebhook(array $payload): array
    {
        // Add wait parameter to get the message back
        $url = $this->webhookUrl . '?wait=true';

        $headers = [
            'Content-Type' => 'application/json',
        ];

        $response = $this->httpClient->post($url, $headers, $payload);

        if (!$response->isSuccessful()) {
            $statusCode = $response->getStatusCode();
            $body = $response->getBody();
            
            // Try to parse error
            $error = json_decode($body, true);
            $errorMessage = $error['message'] ?? "HTTP {$statusCode}: {$body}";
            
            throw new ProviderException("Discord webhook error: {$errorMessage}");
        }

        return $response->toArray();
    }
}
