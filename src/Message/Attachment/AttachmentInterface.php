<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Message\Attachment;

/**
 * Interface for message attachments (images, videos, etc.).
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
interface AttachmentInterface
{
    /**
     * Get attachment type (image, video, link, etc.).
     */
    public function getType(): string;

    /**
     * Get the attachment URL or file path.
     */
    public function getPath(): string;

    /**
     * Check if attachment is a local file.
     */
    public function isLocal(): bool;

    /**
     * Get attachment metadata.
     */
    public function getMetadata(): array;
}
