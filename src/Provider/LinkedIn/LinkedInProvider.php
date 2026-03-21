<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\LinkedIn;

use Janwebdev\SocialPostBundle\Message\Message;
use Janwebdev\SocialPostBundle\Provider\ProviderInterface;
use Janwebdev\SocialPostBundle\Provider\Result\PublishResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * LinkedIn provider using Community Management API (/rest/posts).
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 * @see https://learn.microsoft.com/en-us/linkedin/marketing/community-management/shares/posts-api
 */
final readonly class LinkedInProvider implements ProviderInterface
{
    public const NAME = 'linkedin';

    public function __construct(
        private LinkedInClient $client,
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
            $shareData = $this->prepareShareData($message);

            $this->logger->debug('Posting to LinkedIn', ['data' => $shareData]);

            $response = $this->client->createPost($shareData);

            if (isset($response['id']) && is_string($response['id'])) {
                $shareId = $response['id'];
                // Extract URN from response
                $shareUrn = $response['id'];
                $postUrl = "https://www.linkedin.com/feed/update/{$shareUrn}";

                return PublishResult::success(
                    providerName: self::NAME,
                    postId: $shareId,
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
            $this->logger->error('Failed to publish to LinkedIn', [
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
    private function prepareShareData(Message $message): array
    {
        $data = [
            'text' => ['text' => $message->getText()],
        ];

        // Add content/article if link is provided
        if ($message->getLink()) {
            $data['content'] = [
                'article' => [
                    'source' => $message->getLink(),
                    'title' => mb_substr($message->getText(), 0, 100),
                ],
            ];
        }

        // Handle image attachments
        if ($message->hasAttachments()) {
            $images = array_filter(
                $message->getAttachments(),
                fn($attachment) => $attachment->getType() === 'image'
            );

            if (!empty($images)) {
                $firstImage = reset($images);
                if (!isset($data['content'])) {
                    $data['content'] = [];
                }
                $data['content']['media'] = [
                    'title' => mb_substr($message->getText(), 0, 100),
                    'id' => $firstImage->getPath(), // Would need to upload first
                ];
            }
        }

        return $data;
    }
}
