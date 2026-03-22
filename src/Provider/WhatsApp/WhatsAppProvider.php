<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider\WhatsApp;

use Janwebdev\SocialPostBundle\Message\Message;
use Janwebdev\SocialPostBundle\Provider\ProviderInterface;
use Janwebdev\SocialPostBundle\Provider\Result\PublishResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * WhatsApp provider — sends messages via WhatsApp Business Cloud API.
 *
 * @deprecated Will be removed in v4.0. WhatsApp Business Cloud API does NOT support
 *             posting to public Channels. It can only send template messages to users
 *             who have explicitly opted in. Use Telegram for broadcast channel posting.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 * @see https://developers.facebook.com/docs/whatsapp/cloud-api
 */
final readonly class WhatsAppProvider implements ProviderInterface
{
    public const NAME = 'whatsapp';

    public function __construct(
        private WhatsAppClient $client,
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
        trigger_error(
            'WhatsAppProvider is deprecated and will be removed in v4.0. WhatsApp Business Cloud API does not support posting to public Channels.',
            \E_USER_DEPRECATED,
        );

        try {
            $this->logger->warning('Publishing to WhatsApp Channel (BETA API - may be unstable)');

            $payload = $this->preparePayload($message);
            $response = $this->client->sendMessage($payload);

            if (isset($response['messages'][0]['id'])) {
                $messageId = $response['messages'][0]['id'];

                return PublishResult::success(
                    providerName: self::NAME,
                    postId: $messageId,
                    postUrl: null, // WhatsApp channels don't have public URLs
                    metadata: $response,
                );
            }

            return PublishResult::failure(
                providerName: self::NAME,
                errorMessage: 'Message sent but no ID returned',
                metadata: $response,
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to publish to WhatsApp Channel', [
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
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'channel', // For WhatsApp Channels
            'type' => 'text',
        ];

        // Prepare text message
        $text = $message->getText();
        
        if ($message->getLink()) {
            $text .= "\n\n" . $message->getLink();
        }

        $payload['text'] = ['body' => $text];

        // Handle images if present
        if ($message->hasAttachments()) {
            $images = array_filter(
                $message->getAttachments(),
                fn($attachment) => $attachment->getType() === 'image'
            );

            if (!empty($images)) {
                $firstImage = reset($images);
                $payload['type'] = 'image';
                $payload['image'] = [
                    'link' => $firstImage->getPath(),
                    'caption' => $message->getText(),
                ];
                unset($payload['text']);
            }
        }

        return $payload;
    }
}
