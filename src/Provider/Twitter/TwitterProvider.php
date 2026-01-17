<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\Twitter;

use Janwebdev\SocialPostBundle\Message\Message;
use Janwebdev\SocialPostBundle\Provider\ProviderInterface;
use Janwebdev\SocialPostBundle\Provider\Result\PublishResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Twitter provider using API v2.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 * @see https://developer.twitter.com/en/docs/twitter-api/tweets/manage-tweets/api-reference/post-tweets
 */
final readonly class TwitterProvider implements ProviderInterface
{
    public const NAME = 'twitter';
    public const TEXT_MAX_LENGTH = 280;

    public function __construct(
        private TwitterClient $client,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function canPublish(Message $message): bool
    {
        return $message->isForNetwork(self::NAME);
    }

    public function publish(Message $message): PublishResult
    {
        try {
            $text = $this->prepareText($message);
            
            $tweetData = ['text' => $text];

            // Handle media attachments
            $mediaIds = [];
            if ($message->hasAttachments()) {
                $mediaIds = $this->client->uploadMedia($message->getAttachments());
                if (!empty($mediaIds)) {
                    $tweetData['media'] = ['media_ids' => $mediaIds];
                }
            }

            $this->logger->debug('Posting tweet', ['data' => $tweetData]);
            
            $response = $this->client->createTweet($tweetData);

            if (isset($response['data']['id'])) {
                $tweetId = $response['data']['id'];
                $tweetUrl = "https://twitter.com/i/web/status/{$tweetId}";

                return PublishResult::success(
                    providerName: self::NAME,
                    postId: $tweetId,
                    postUrl: $tweetUrl,
                    metadata: $response,
                );
            }

            return PublishResult::failure(
                providerName: self::NAME,
                errorMessage: 'Tweet created but no ID returned',
                metadata: $response,
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to publish to Twitter', [
                'exception' => $e->getMessage(),
            ]);

            return PublishResult::failure(
                providerName: self::NAME,
                errorMessage: $e->getMessage(),
                exception: $e,
            );
        }
    }

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    private function prepareText(Message $message): string
    {
        $text = $message->getText();
        $link = $message->getLink();

        // Add link if provided and not already in text
        if ($link && !str_contains($text, $link)) {
            $text .= ' ' . $link;
        }

        // Truncate if too long (Twitter counts characters differently, but this is a safe approximation)
        if (mb_strlen($text) > self::TEXT_MAX_LENGTH) {
            $text = mb_substr($text, 0, self::TEXT_MAX_LENGTH - 3) . '...';
        }

        return $text;
    }
}
