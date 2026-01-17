<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\Threads;

use Janwebdev\SocialPostBundle\Message\Message;
use Janwebdev\SocialPostBundle\Provider\ProviderInterface;
use Janwebdev\SocialPostBundle\Provider\Result\PublishResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Threads provider using Threads API.
 *
 * @since 3.1.0
 * @license https://opensource.org/licenses/MIT
 * @see https://developers.facebook.com/docs/threads/get-started
 */
final readonly class ThreadsProvider implements ProviderInterface
{
    public const NAME = 'threads';

    public function __construct(
        private ThreadsClient $client,
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
            $this->logger->debug('Publishing to Threads');

            // Threads requires a two-step process: create container, then publish
            
            // Step 1: Create media container
            $containerData = $this->prepareContainerData($message);
            $container = $this->client->createMediaContainer($containerData);

            if (!isset($container['id'])) {
                return PublishResult::failure(
                    providerName: self::NAME,
                    errorMessage: 'Failed to create media container',
                    metadata: $container,
                );
            }

            $containerId = $container['id'];
            $this->logger->debug('Media container created', ['container_id' => $containerId]);

            // Step 2: Wait a bit for media to be processed (Threads requirement)
            sleep(1);

            // Step 3: Publish the container
            $response = $this->client->publishMediaContainer($containerId);

            if (isset($response['id'])) {
                $postId = $response['id'];
                $postUrl = "https://www.threads.net/@username/post/{$postId}"; // Username will be in actual post

                return PublishResult::success(
                    providerName: self::NAME,
                    postId: $postId,
                    postUrl: $postUrl,
                    metadata: $response,
                );
            }

            return PublishResult::failure(
                providerName: self::NAME,
                errorMessage: 'Post created but no ID returned',
                metadata: $response,
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to publish to Threads', [
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
    private function prepareContainerData(Message $message): array
    {
        $data = [
            'text' => $this->prepareText($message),
        ];

        // Add image if available
        if ($message->hasAttachments()) {
            $images = array_filter(
                $message->getAttachments(),
                fn($attachment) => $attachment->getType() === 'image'
            );

            if (!empty($images)) {
                $firstImage = reset($images);
                $data['image_url'] = $firstImage->getPath();
            }
        }

        return $data;
    }

    private function prepareText(Message $message): string
    {
        $text = $message->getText();

        // Add link if provided (Threads allows links in text)
        if ($message->getLink()) {
            $text .= "\n\n🔗 " . $message->getLink();
        }

        // Threads text limit is 500 characters
        if (mb_strlen($text) > 500) {
            $text = mb_substr($text, 0, 497) . '...';
        }

        return $text;
    }
}
