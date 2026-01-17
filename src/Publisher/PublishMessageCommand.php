<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Publisher;

use Janwebdev\SocialPostBundle\Message\Message;

/**
 * Messenger command for async message publishing.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
final readonly class PublishMessageCommand
{
    public function __construct(
        private Message $message,
    ) {
    }

    public function getMessage(): Message
    {
        return $this->message;
    }
}
