<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\Telegram;

use Janwebdev\SocialPostBundle\Http\ClientInterface;
use Janwebdev\SocialPostBundle\Provider\Exception\ProviderException;

/**
 * Telegram Bot API client.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
final readonly class TelegramClient
{
    private const API_BASE_URL = 'https://api.telegram.org';

    public function __construct(
        private ClientInterface $httpClient,
        private string $botToken,
        private string $channelId,
    ) {
    }

    public function isConfigured(): bool
    {
        return !empty($this->botToken) && !empty($this->channelId);
    }

    /**
     * Send a text message.
     *
     * @return array<string, mixed>
     */
    public function sendMessage(string $text): array
    {
        $url = $this->buildUrl('sendMessage');

        $data = [
            'chat_id' => $this->channelId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        $response = $this->httpClient->post($url, [], $data);

        if (!$response->isSuccessful()) {
            throw new ProviderException("Telegram API error: {$response->getBody()}");
        }

        return $response->toArray();
    }

    /**
     * Send a photo with caption.
     *
     * @return array<string, mixed>
     */
    public function sendPhoto(string $photo, ?string $caption = null): array
    {
        $url = $this->buildUrl('sendPhoto');

        $data = [
            'chat_id' => $this->channelId,
            'photo' => $photo,
        ];

        if ($caption) {
            $data['caption'] = $caption;
            $data['parse_mode'] = 'HTML';
        }

        $response = $this->httpClient->post($url, [], $data);

        if (!$response->isSuccessful()) {
            throw new ProviderException("Telegram API error: {$response->getBody()}");
        }

        return $response->toArray();
    }

    private function buildUrl(string $method): string
    {
        return self::API_BASE_URL . '/bot' . $this->botToken . '/' . $method;
    }
}
