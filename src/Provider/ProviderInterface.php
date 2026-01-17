<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Provider;

use Janwebdev\SocialPostBundle\Message\Message;
use Janwebdev\SocialPostBundle\Provider\Result\PublishResult;

/**
 * Interface for social network providers.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
interface ProviderInterface
{
    /**
     * Get the provider name (twitter, facebook, linkedin, telegram).
     */
    public function getName(): string;

    /**
     * Check if this provider can publish the given message.
     */
    public function canPublish(Message $message): bool;

    /**
     * Publish a message to the social network.
     */
    public function publish(Message $message): PublishResult;

    /**
     * Check if the provider is properly configured and ready.
     */
    public function isConfigured(): bool;
}
