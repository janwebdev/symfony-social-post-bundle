<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Provider\HackerNews;

use Janwebdev\SocialPostBundle\Provider\Exception\ProviderException;
use Janwebdev\SocialPostBundle\Provider\HackerNews\HackerNewsClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @covers \Janwebdev\SocialPostBundle\Provider\HackerNews\HackerNewsClient
 */
class HackerNewsClientTest extends TestCase
{
    private const LOGIN_PAGE_HTML = '<html><body><form action="login" method="post">'
        . '<input type="hidden" name="goto" value="news">'
        . '<input type="hidden" name="creating" value="">'
        . '<input type="text" name="acct">'
        . '<input type="password" name="pw">'
        . '</form></body></html>';

    private const SUBMIT_PAGE_HTML = '<html><body><form action="r" method="post">'
        . '<input type="hidden" name="fnid" value="TOKEN123">'
        . '<input type="hidden" name="fnop" value="submit-page">'
        . '<input type="text" name="title">'
        . '<input type="url" name="url">'
        . '</form></body></html>';

    public function testIsConfiguredReturnsTrueWithCredentials(): void
    {
        $client = new HackerNewsClient(new MockHttpClient(), 'user', 'pass', 0);
        $this->assertTrue($client->isConfigured());
    }

    public function testIsConfiguredReturnsFalseWithEmptyUsername(): void
    {
        $client = new HackerNewsClient(new MockHttpClient(), '', 'pass', 0);
        $this->assertFalse($client->isConfigured());
    }

    public function testIsConfiguredReturnsFalseWithEmptyPassword(): void
    {
        $client = new HackerNewsClient(new MockHttpClient(), 'user', '', 0);
        $this->assertFalse($client->isConfigured());
    }

    public function testSubmitPostSuccessReturnsPostUrl(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse(self::LOGIN_PAGE_HTML),
            new MockResponse('', [
                'http_code' => 302,
                'response_headers' => [
                    'Set-Cookie: user=testuser%26pass%3Dabc; path=/',
                    'Location: /news',
                ],
            ]),
            new MockResponse(self::SUBMIT_PAGE_HTML),
            new MockResponse('', [
                'http_code' => 302,
                'response_headers' => ['Location: /newest'],
            ]),
            new MockResponse('', ['http_code' => 302]),
        ]);

        $client = new HackerNewsClient($httpClient, 'testuser', 'testpass', 0);
        $postUrl = $client->submitPost('Test Title', 'https://example.com');

        $this->assertSame('https://news.ycombinator.com/newest', $postUrl);
    }

    public function testSubmitPostThrowsOnLoginNoCookie(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse(self::LOGIN_PAGE_HTML),
            new MockResponse('', ['http_code' => 302, 'response_headers' => ['Location: /login']]),
        ]);

        $client = new HackerNewsClient($httpClient, 'user', 'wrongpass', 0);

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessageMatches('/login failed/i');

        $client->submitPost('Title', 'https://example.com');
    }

    public function testSubmitPostThrowsWhenFnidMissing(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse(self::LOGIN_PAGE_HTML),
            new MockResponse('', [
                'http_code' => 302,
                'response_headers' => ['Set-Cookie: user=abc; path=/'],
            ]),
            new MockResponse('<html><body>no form here</body></html>'),
        ]);

        $client = new HackerNewsClient($httpClient, 'user', 'pass', 0);

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessageMatches('/form token/i');

        $client->submitPost('Title', 'https://example.com');
    }

    public function testSubmitPostThrowsOnRedirectBackToSubmitForm(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse(self::LOGIN_PAGE_HTML),
            new MockResponse('', [
                'http_code' => 302,
                'response_headers' => ['Set-Cookie: user=abc; path=/'],
            ]),
            new MockResponse(self::SUBMIT_PAGE_HTML),
            new MockResponse('', [
                'http_code' => 302,
                'response_headers' => ['Location: /submit'],
            ]),
        ]);

        $client = new HackerNewsClient($httpClient, 'user', 'pass', 0);

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessageMatches('/duplicate or banned/i');

        $client->submitPost('Title', 'https://example.com');
    }

    public function testSubmitPostThrowsWhenNoLocationHeader(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse(self::LOGIN_PAGE_HTML),
            new MockResponse('', [
                'http_code' => 302,
                'response_headers' => ['Set-Cookie: user=abc; path=/'],
            ]),
            new MockResponse(self::SUBMIT_PAGE_HTML),
            new MockResponse('', ['http_code' => 200]),
        ]);

        $client = new HackerNewsClient($httpClient, 'user', 'pass', 0);

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessageMatches('/HTTP 200/i');

        $client->submitPost('Title', 'https://example.com');
    }
}
