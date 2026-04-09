<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Http;

/**
 * HTTP Client interface for making requests to social network APIs.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
interface ClientInterface
{
    /**
     * Make a GET request.
     *
     * @param array<string, mixed> $headers
     * @param array<string, mixed> $query
     */
    public function get(string $url, array $headers = [], array $query = []): Response;

    /**
     * Make a POST request.
     *
     * @param array<string, mixed> $headers
     * @param array<string, mixed>|string $body
     */
    public function post(string $url, array $headers = [], array|string $body = []): Response;

    /**
     * Upload a file via POST.
     *
     * @param array<string, mixed> $headers
     * @param array<string, mixed> $fields
     */
    public function postMultipart(string $url, array $headers = [], array $fields = []): Response;

    /**
     * Make a PUT request with a raw binary body.
     *
     * @param array<string, string> $headers
     */
    public function put(string $url, array $headers, string $body): Response;
}
