<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\HackerNews;

use Janwebdev\SocialPostBundle\Provider\Exception\ProviderException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * HackerNews HTTP bot client.
 * Automates login → submit → logout via the HN web interface.
 *
 * @since 3.2.11
 * @license https://opensource.org/licenses/MIT
 */
readonly class HackerNewsClient
{
    private const BASE_URL = 'https://news.ycombinator.com';
    private const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $username,
        private string $password,
        private int $requestDelay = 5,
    ) {
    }

    public function isConfigured(): bool
    {
        return !empty($this->username) && !empty($this->password);
    }

    public function submitPost(string $title, string $url): string
    {
        $cookies = $this->login();
        sleep($this->requestDelay);

        $postUrl = $this->submit($title, $url, $cookies);
        sleep($this->requestDelay);

        $this->logout($cookies);

        return $postUrl;
    }

    /**
     * @return array<string, string>
     */
    private function login(): array
    {
        $loginPageResponse = $this->httpClient->request('GET', self::BASE_URL . '/login', [
            'headers' => $this->browserHeaders(),
        ]);

        $crawler = new Crawler($loginPageResponse->getContent(false));

        $formData = [
            'acct' => $this->username,
            'pw' => $this->password,
        ];

        $crawler->filter('form[action="login"] input[type="hidden"]')->each(
            function (Crawler $node) use (&$formData): void {
                $name = $node->attr('name');
                $value = $node->attr('value') ?? '';
                if ($name !== null) {
                    $formData[$name] = $value;
                }
            }
        );

        $loginResponse = $this->httpClient->request('POST', self::BASE_URL . '/login', [
            'headers' => $this->browserHeaders(),
            'body' => $formData,
            'max_redirects' => 0,
        ]);

        $cookies = $this->parseCookies($loginResponse->getHeaders(false));

        if (empty($cookies)) {
            throw new ProviderException('HackerNews login failed: no session cookie received');
        }

        return $cookies;
    }

    /**
     * @param array<string, string> $cookies
     */
    private function submit(string $title, string $url, array $cookies): string
    {
        $submitPageResponse = $this->httpClient->request('GET', self::BASE_URL . '/submit', [
            'headers' => $this->browserHeaders($cookies),
        ]);

        $crawler = new Crawler($submitPageResponse->getContent(false));

        $fnidNode = $crawler->filter('input[name="fnid"]');
        $fnopNode = $crawler->filter('input[name="fnop"]');

        if ($fnidNode->count() === 0) {
            throw new ProviderException('HackerNews: could not extract form token from submit page');
        }

        $fnid = $fnidNode->attr('value') ?? '';
        $fnop = $fnopNode->count() > 0 ? ($fnopNode->attr('value') ?? 'submit-page') : 'submit-page';

        if ($fnid === '') {
            throw new ProviderException('HackerNews: could not extract form token from submit page');
        }

        $submitResponse = $this->httpClient->request('POST', self::BASE_URL . '/r', [
            'headers' => $this->browserHeaders($cookies),
            'body' => [
                'fnid'  => $fnid,
                'fnop'  => $fnop,
                'title' => $title,
                'url'   => $url,
                'text'  => '',
            ],
            'max_redirects' => 0,
        ]);

        $statusCode = $submitResponse->getStatusCode();

        if ($statusCode < 300 || $statusCode >= 400) {
            throw new ProviderException(sprintf('HackerNews submit failed: HTTP %d', $statusCode));
        }

        $headers = $submitResponse->getHeaders(false);
        $location = ($headers['location'] ?? [''])[0];

        if (str_contains($location, '/submit') || str_contains($location, '/login')) {
            throw new ProviderException('HackerNews submit failed: redirected back to form (duplicate or banned)');
        }

        if ($location === '') {
            throw new ProviderException('HackerNews submit failed: no redirect location received');
        }

        return str_starts_with($location, 'http') ? $location : self::BASE_URL . '/' . ltrim($location, '/');
    }

    /**
     * @param array<string, string> $cookies
     */
    private function logout(array $cookies): void
    {
        try {
            $this->httpClient->request('GET', self::BASE_URL . '/logout', [
                'headers' => $this->browserHeaders($cookies),
                'max_redirects' => 0,
            ]);
        } catch (\Throwable) {
            // logout is best-effort, session will expire naturally
        }
    }

    /**
     * @param array<string, string[]> $headers
     * @return array<string, string>
     */
    private function parseCookies(array $headers): array
    {
        $cookies = [];
        foreach ($headers['set-cookie'] ?? [] as $cookieString) {
            $nameValue = explode(';', $cookieString)[0];
            if (str_contains($nameValue, '=')) {
                [$name, $value] = explode('=', $nameValue, 2);
                $cookies[trim($name)] = trim($value);
            }
        }
        return $cookies;
    }

    /**
     * @param array<string, string> $cookies
     * @return array<string, string>
     */
    private function browserHeaders(array $cookies = []): array
    {
        $headers = [
            'User-Agent'      => self::USER_AGENT,
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
        ];

        if (!empty($cookies)) {
            $parts = [];
            foreach ($cookies as $name => $value) {
                $parts[] = "{$name}={$value}";
            }
            $headers['Cookie'] = implode('; ', $parts);
        }

        return $headers;
    }
}
