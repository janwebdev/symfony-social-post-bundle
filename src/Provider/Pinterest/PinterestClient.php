<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\Pinterest;

use Janwebdev\SocialPostBundle\Http\ClientInterface;
use Janwebdev\SocialPostBundle\Provider\Exception\ProviderException;

/**
 * Pinterest API v5 client.
 *
 * @since 3.2.0
 * @license https://opensource.org/licenses/MIT
 * @see https://developers.pinterest.com/docs/api/v5/
 */
readonly class PinterestClient
{
    private const API_BASE_URL = 'https://api.pinterest.com/v5';

    public function __construct(
        private ClientInterface $httpClient,
        private string $boardId,
        private string $accessToken,
    ) {
    }

    public function isConfigured(): bool
    {
        return !empty($this->boardId) && !empty($this->accessToken);
    }

    /**
     * Create a Pin on the configured board.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createPin(array $data): array
    {
        $url = self::API_BASE_URL . '/pins';

        $payload = array_merge(['board_id' => $this->boardId], $data);

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];

        $response = $this->httpClient->post($url, $headers, $payload);

        if (!$response->isSuccessful()) {
            $error = $response->toArray();
            $errorMessage = is_string($error['message'] ?? null) ? $error['message'] : (is_string($error['code'] ?? null) ? $error['code'] : 'Unknown error');
            throw new ProviderException("Pinterest API error: {$errorMessage}");
        }

        return $response->toArray();
    }
}
