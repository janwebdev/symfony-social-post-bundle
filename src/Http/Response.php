<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Http;

/**
 * HTTP Response wrapper.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
final readonly class Response
{
    public function __construct(
        private int $statusCode,
        private string $body,
        private array $headers = [],
    ) {
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return array<string, mixed>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $decoded = json_decode($this->body, true);
        return is_array($decoded) ? $decoded : [];
    }
}
