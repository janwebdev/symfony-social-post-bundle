<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Event;

use Janwebdev\SocialPostBundle\Message\Message;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched before publishing a message.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
final class BeforePublishEvent extends Event
{
    public function __construct(
        private readonly Message $message,
    ) {
    }

    public function getMessage(): Message
    {
        return $this->message;
    }
}
