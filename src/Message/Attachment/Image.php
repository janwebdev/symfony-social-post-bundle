<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Message\Attachment;

/**
 * Represents an image attachment.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
final readonly class Image implements AttachmentInterface
{
    public function __construct(
        private string $path,
        private ?string $altText = null,
        private array $metadata = [],
    ) {
    }

    public function getType(): string
    {
        return 'image';
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isLocal(): bool
    {
        return !filter_var($this->path, FILTER_VALIDATE_URL);
    }

    public function getAltText(): ?string
    {
        return $this->altText;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
