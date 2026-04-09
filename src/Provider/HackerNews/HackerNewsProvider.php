<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\HackerNews;

use Janwebdev\SocialPostBundle\Message\Message;
use Janwebdev\SocialPostBundle\Provider\ProviderInterface;
use Janwebdev\SocialPostBundle\Provider\Result\PublishResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * HackerNews provider — submits link posts via the HN web interface.
 *
 * @since 3.2.11
 * @license https://opensource.org/licenses/MIT
 */
final readonly class HackerNewsProvider implements ProviderInterface
{
    public const NAME = 'hackernews';

    public function __construct(
        private HackerNewsClient $client,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public static function getName(): string
    {
        return self::NAME;
    }

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    public function canPublish(Message $message): bool
    {
        return $message->isForNetwork(self::NAME) && $message->getLink() !== null;
    }

    public function publish(Message $message): PublishResult
    {
        $url = $message->getLink();

        if ($url === null) {
            return PublishResult::failure(
                providerName: self::NAME,
                errorMessage: 'HackerNews requires a URL (only link posts are supported)',
            );
        }

        try {
            $this->logger->debug('Submitting to HackerNews', [
                'title' => $message->getText(),
                'url'   => $url,
            ]);

            $postUrl = $this->client->submitPost($message->getText(), $url);

            $this->logger->info('Successfully submitted to HackerNews', ['post_url' => $postUrl]);

            return PublishResult::success(
                providerName: self::NAME,
                postUrl: $postUrl,
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to publish to HackerNews', [
                'exception' => $e->getMessage(),
            ]);

            return PublishResult::failure(
                providerName: self::NAME,
                errorMessage: $e->getMessage(),
                exception: $e,
            );
        }
    }
}
