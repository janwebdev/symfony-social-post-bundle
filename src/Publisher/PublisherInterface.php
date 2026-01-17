<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Publisher;

use Janwebdev\SocialPostBundle\Message\Message;
use Janwebdev\SocialPostBundle\Publisher\Result\PublishResultCollection;

/**
 * Main publisher interface for publishing to multiple social networks.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
interface PublisherInterface
{
    /**
     * Publish a message to all configured social networks.
     */
    public function publish(Message $message): PublishResultCollection;

    /**
     * Publish a message asynchronously using Symfony Messenger.
     */
    public function publishAsync(Message $message): void;
}
