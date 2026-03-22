<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\Facebook;

use Janwebdev\SocialPostBundle\Message\Message;
use Janwebdev\SocialPostBundle\Provider\ProviderInterface;
use Janwebdev\SocialPostBundle\Provider\Result\PublishResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Facebook provider using Graph API v20.0+.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 * @see https://developers.facebook.com/docs/graph-api/reference/page/feed
 */
final readonly class FacebookProvider implements ProviderInterface
{
    public const NAME = 'facebook';

    public function __construct(
        private FacebookClient $client,
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
            $postData = $this->preparePostData($message);

            $this->logger->debug('Posting to Facebook', ['data' => $postData]);

            $response = $this->client->createPost($postData);

            if (isset($response['id'])) {
                $postId = $response['id'];
                // Post ID format: {page-id}_{post-id}
                $postUrl = "https://www.facebook.com/{$postId}";

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
            $this->logger->error('Failed to publish to Facebook', [
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
    private function preparePostData(Message $message): array
    {
        $data = ['message' => $message->getText()];

        // Add link if provided
        if ($message->getLink()) {
            $data['link'] = $message->getLink();
        }

        // Handle image attachments
        if ($message->hasAttachments()) {
            $images = array_filter(
                $message->getAttachments(),
                fn($attachment) => $attachment->getType() === 'image'
            );

            if (!empty($images)) {
                $firstImage = reset($images);
                // For single image, use photo endpoint
                if (count($images) === 1) {
                    $data['url'] = $firstImage->getPath();
                    if ($firstImage->getAltText()) {
                        $data['caption'] = $data['message'];
                        $data['message'] = $firstImage->getAltText();
                    }
                }
                // For multiple images, would need different approach
            }
        }

        return $data;
    }
}
