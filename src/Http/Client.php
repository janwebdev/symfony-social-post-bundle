<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Http;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * HTTP Client implementation using Symfony HttpClient.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
final readonly class Client implements ClientInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    public function get(string $url, array $headers = [], array $query = []): Response
    {
        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => $headers,
                'query' => $query,
            ]);

            return new Response(
                statusCode: $response->getStatusCode(),
                body: $response->getContent(false),
                headers: $response->getHeaders(false),
            );
        } catch (TransportExceptionInterface $e) {
            throw new Exception\HttpException(
                "HTTP GET request failed: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    public function post(string $url, array $headers = [], array|string $body = []): Response
    {
        try {
            $options = ['headers' => $headers];

            if (is_string($body)) {
                $options['body'] = $body;
            } elseif (!empty($body)) {
                $options['json'] = $body;
            }

            $response = $this->httpClient->request('POST', $url, $options);

            return new Response(
                statusCode: $response->getStatusCode(),
                body: $response->getContent(false),
                headers: $response->getHeaders(false),
            );
        } catch (TransportExceptionInterface $e) {
            throw new Exception\HttpException(
                "HTTP POST request failed: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    public function postMultipart(string $url, array $headers = [], array $fields = []): Response
    {
        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => $headers,
                'body' => $fields,
            ]);

            return new Response(
                statusCode: $response->getStatusCode(),
                body: $response->getContent(false),
                headers: $response->getHeaders(false),
            );
        } catch (TransportExceptionInterface $e) {
            throw new Exception\HttpException(
                "HTTP multipart POST request failed: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    public function put(string $url, array $headers, string $body): Response
    {
        try {
            $response = $this->httpClient->request('PUT', $url, [
                'headers' => $headers,
                'body' => $body,
            ]);

            return new Response(
                statusCode: $response->getStatusCode(),
                body: $response->getContent(false),
                headers: $response->getHeaders(false),
            );
        } catch (TransportExceptionInterface $e) {
            throw new Exception\HttpException(
                "HTTP PUT request failed: {$e->getMessage()}",
                previous: $e,
            );
        }
    }
}
