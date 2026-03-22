<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Message;

use Janwebdev\SocialPostBundle\Message\Attachment\AttachmentInterface;

/**
 * Represents a message to be published to social networks.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
final readonly class Message
{
    /**
     * @param array<string> $networks List of networks to publish to (twitter, facebook, linkedin, telegram)
     * @param array<AttachmentInterface> $attachments
     */
    public function __construct(
        private string $text,
        private ?string $link = null,
        private ?string $imageUrl = null,
        private array $networks = [],
        private array $attachments = [],
        private array $metadata = [],
    ) {
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * @return array<string>
     */
    public function getNetworks(): array
    {
        return $this->networks;
    }

    /**
     * Check if message should be published to specific network.
     */
    public function isForNetwork(string $network): bool
    {
        // If networks list is empty, publish to all
        if (empty($this->networks)) {
            return true;
        }

        return in_array($network, $this->networks, true);
    }

    /**
     * @return array<AttachmentInterface>
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }
}
