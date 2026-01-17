<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Event;

use Janwebdev\SocialPostBundle\Message\Message;
use Janwebdev\SocialPostBundle\Publisher\Result\PublishResultCollection;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched after publishing a message.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
final class AfterPublishEvent extends Event
{
    public function __construct(
        private readonly Message $message,
        private readonly PublishResultCollection $results,
    ) {
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function getResults(): PublishResultCollection
    {
        return $this->results;
    }
}
