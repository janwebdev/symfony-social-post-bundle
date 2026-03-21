<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\Instagram;

use Janwebdev\SocialPostBundle\Message\Message;
use Janwebdev\SocialPostBundle\Provider\Exception\ProviderException;
use Janwebdev\SocialPostBundle\Provider\ProviderInterface;
use Janwebdev\SocialPostBundle\Provider\Result\PublishResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Instagram provider using Graph API.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 * @see https://developers.facebook.com/docs/instagram-api/guides/content-publishing
 */
final readonly class InstagramProvider implements ProviderInterface
{
    public const NAME = 'instagram';

    public function __construct(
        private InstagramClient $client,
        private LoggerInterface $logger = new NullLogger(),
        private int $pollIntervalSeconds = 2,
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
            $this->logger->debug('Publishing to Instagram');

            // Instagram requires a two-step process: create container, then publish

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

            // Step 2: Wait for media to be processed (poll status instead of blind sleep)
            $this->waitForContainerReady($containerId);

            // Step 3: Publish the container
            $response = $this->client->publishMediaContainer($containerId);

            if (isset($response['id'])) {
                $postId = $response['id'];
                $postUrl = "https://www.instagram.com/p/{$postId}";

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
            $this->logger->error('Failed to publish to Instagram', [
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
     * Poll container status until FINISHED, or throw on failure/timeout.
     *
     * @throws ProviderException when processing fails or times out
     */
    private function waitForContainerReady(string $containerId): void
    {
        $maxAttempts = 10;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $status = $this->client->getContainerStatus($containerId);
            $statusCode = $status['status_code'] ?? 'IN_PROGRESS';

            if ($statusCode === 'FINISHED') {
                return;
            }

            if ($statusCode === 'ERROR' || $statusCode === 'EXPIRED') {
                throw new ProviderException(
                    sprintf('Instagram container processing failed with status: %s', $statusCode)
                );
            }

            if ($this->pollIntervalSeconds > 0) {
                sleep($this->pollIntervalSeconds);
            }
        }

        throw new ProviderException(
            sprintf('Instagram container processing timed out after %d attempts', $maxAttempts)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareContainerData(Message $message): array
    {
        $data = [
            'caption' => $this->prepareCaption($message),
        ];

        // Instagram requires an image
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

    private function prepareCaption(Message $message): string
    {
        $caption = $message->getText();

        // Add link if provided (Instagram allows links in bio only, so we add as text)
        if ($message->getLink()) {
            $caption .= "\n\n🔗 " . $message->getLink();
        }

        // Instagram caption limit is 2200 characters
        if (mb_strlen($caption) > 2200) {
            $caption = mb_substr($caption, 0, 2197) . '...';
        }

        return $caption;
    }
}
