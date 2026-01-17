<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Message;

use Janwebdev\SocialPostBundle\Message\Attachment\AttachmentInterface;
use Janwebdev\SocialPostBundle\Message\Attachment\Image;

/**
 * Builder for creating Message objects with fluent interface.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
final class MessageBuilder
{
    private string $text = '';
    private ?string $link = null;
    /** @var array<string> */
    private array $networks = [];
    /** @var array<AttachmentInterface> */
    private array $attachments = [];
    private array $metadata = [];

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function setText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function setLink(?string $link): self
    {
        $this->link = $link;
        return $this;
    }

    /**
     * @param array<string> $networks
     */
    public function forNetworks(array $networks): self
    {
        $this->networks = $networks;
        return $this;
    }

    public function forAllNetworks(): self
    {
        $this->networks = [];
        return $this;
    }

    public function addAttachment(AttachmentInterface $attachment): self
    {
        $this->attachments[] = $attachment;
        return $this;
    }

    public function addImage(string $path, ?string $altText = null): self
    {
        $this->attachments[] = new Image($path, $altText);
        return $this;
    }

    /**
     * @param array<AttachmentInterface> $attachments
     */
    public function setAttachments(array $attachments): self
    {
        $this->attachments = $attachments;
        return $this;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function addMetadata(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    public function build(): Message
    {
        return new Message(
            text: $this->text,
            link: $this->link,
            networks: $this->networks,
            attachments: $this->attachments,
            metadata: $this->metadata,
        );
    }
}
