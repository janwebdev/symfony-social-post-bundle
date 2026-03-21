<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\Pinterest;

use Janwebdev\SocialPostBundle\Message\Message;
use Janwebdev\SocialPostBundle\Provider\ProviderInterface;
use Janwebdev\SocialPostBundle\Provider\Result\PublishResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Pinterest provider using API v5.
 *
 * Publishes link pins (text + URL) and image pins (URL-based images only).
 * Local image files must be served via a public URL — Pinterest requires
 * publicly accessible image URLs; local paths are not supported.
 *
 * @since 3.2.0
 * @license https://opensource.org/licenses/MIT
 * @see https://developers.pinterest.com/docs/api/v5/#tag/pins/operation/pins/create
 */
final readonly class PinterestProvider implements ProviderInterface
{
    public const NAME = 'pinterest';

    public function __construct(
        private PinterestClient $client,
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
            $pinData = $this->preparePinData($message);

            $this->logger->debug('Creating Pinterest pin', ['data' => $pinData]);

            $response = $this->client->createPin($pinData);

            if (isset($response['id']) && is_string($response['id'])) {
                $pinId = $response['id'];
                $pinUrl = "https://www.pinterest.com/pin/{$pinId}/";

                return PublishResult::success(
                    providerName: self::NAME,
                    postId: $pinId,
                    postUrl: $pinUrl,
                    metadata: $response,
                );
            }

            return PublishResult::failure(
                providerName: self::NAME,
                errorMessage: 'Pin created but no ID returned',
                metadata: $response,
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to publish to Pinterest', [
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
    private function preparePinData(Message $message): array
    {
        $data = [
            'title' => mb_substr($message->getText(), 0, 100),
            'description' => $message->getText(),
        ];

        if ($message->getLink() !== null) {
            $data['link'] = $message->getLink();
        }

        // Image pin: Pinterest requires a publicly accessible image URL
        if ($message->hasAttachments()) {
            $images = array_filter(
                $message->getAttachments(),
                static fn ($a) => $a->getType() === 'image',
            );

            if (!empty($images)) {
                $firstImage = reset($images);
                $data['media_source'] = [
                    'source_type' => 'image_url',
                    'url' => $firstImage->getPath(),
                ];
            }
        }

        return $data;
    }
}
