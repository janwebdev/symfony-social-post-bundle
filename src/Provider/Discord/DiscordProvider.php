<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\Discord;

use Janwebdev\SocialPostBundle\Message\Message;
use Janwebdev\SocialPostBundle\Provider\ProviderInterface;
use Janwebdev\SocialPostBundle\Provider\Result\PublishResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Discord provider using Webhooks.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 * @see https://discord.com/developers/docs/resources/webhook
 */
final readonly class DiscordProvider implements ProviderInterface
{
    public const NAME = 'discord';

    public function __construct(
        private DiscordClient $client,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public static function getName(): string
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
            $this->logger->debug('Publishing to Discord');

            $payload = $this->preparePayload($message);
            $response = $this->client->executeWebhook($payload);

            // Discord webhook returns the message object
            if (isset($response['id'])) {
                $messageId = $response['id'];
                $channelId = $response['channel_id'] ?? 'unknown';
                
                // Discord message URL format
                $postUrl = isset($response['guild_id']) 
                    ? "https://discord.com/channels/{$response['guild_id']}/{$channelId}/{$messageId}"
                    : null;

                return PublishResult::success(
                    providerName: self::NAME,
                    postId: $messageId,
                    postUrl: $postUrl,
                    metadata: $response,
                );
            }

            // If no response body, webhook was successful but we don't get message back
            return PublishResult::success(
                providerName: self::NAME,
                postId: 'webhook_executed',
                postUrl: null,
                metadata: $response,
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to publish to Discord', [
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

    /**
     * @return array<string, mixed>
     */
    private function preparePayload(Message $message): array
    {
        $payload = [];

        // Check if we should use embeds (rich formatting) or simple content
        if ($message->getLink() || $message->hasAttachments()) {
            // Use embed for rich content
            $embed = [
                'description' => $message->getText(),
                'color' => 5814783, // Nice blue color
            ];

            if ($message->getLink()) {
                $embed['url'] = $message->getLink();
            }

            if ($message->hasAttachments()) {
                $images = array_filter(
                    $message->getAttachments(),
                    fn($attachment) => $attachment->getType() === 'image'
                );

                if (!empty($images)) {
                    $firstImage = reset($images);
                    $embed['image'] = ['url' => $firstImage->getPath()];
                }
            }

            $payload['embeds'] = [$embed];
        } else {
            // Simple text message
            $payload['content'] = $message->getText();
        }

        return $payload;
    }
}
