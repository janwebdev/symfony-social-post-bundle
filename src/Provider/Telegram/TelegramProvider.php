<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\Telegram;

use Janwebdev\SocialPostBundle\Message\Message;
use Janwebdev\SocialPostBundle\Provider\ProviderInterface;
use Janwebdev\SocialPostBundle\Provider\Result\PublishResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Telegram provider using Bot API.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 * @see https://core.telegram.org/bots/api
 */
final readonly class TelegramProvider implements ProviderInterface
{
    public const NAME = 'telegram';

    public function __construct(
        private TelegramClient $client,
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
            $this->logger->debug('Posting to Telegram');

            // Determine if we're sending with photo or just text
            if ($message->hasAttachments()) {
                $images = array_filter(
                    $message->getAttachments(),
                    fn($attachment) => $attachment->getType() === 'image'
                );

                if (!empty($images)) {
                    $firstImage = reset($images);
                    $response = $this->client->sendPhoto(
                        photo: $firstImage->getPath(),
                        caption: $this->prepareText($message),
                    );
                } else {
                    $response = $this->client->sendMessage($this->prepareText($message));
                }
            } else {
                $response = $this->client->sendMessage($this->prepareText($message));
            }

            if ($response['ok'] && isset($response['result']['message_id'])) {
                $messageId = (string) $response['result']['message_id'];
                $chatId = $response['result']['chat']['id'];
                
                // Build message URL if it's a channel
                $postUrl = null;
                if (isset($response['result']['chat']['username'])) {
                    $username = $response['result']['chat']['username'];
                    $postUrl = "https://t.me/{$username}/{$messageId}";
                }

                return PublishResult::success(
                    providerName: self::NAME,
                    postId: $messageId,
                    postUrl: $postUrl,
                    metadata: $response,
                );
            }

            return PublishResult::failure(
                providerName: self::NAME,
                errorMessage: $response['description'] ?? 'Unknown error',
                metadata: $response,
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to publish to Telegram', [
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

        // Add link if provided
        if ($message->getLink()) {
            $text .= "\n\n" . $message->getLink();
        }

        return $text;
    }
}
