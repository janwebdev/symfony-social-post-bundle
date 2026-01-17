<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Event;

use Janwebdev\SocialPostBundle\Message\Message;
use Symfony\Contracts\EventDispatcher\Event;
use Throwable;

/**
 * Event dispatched when publishing fails.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
final class PublishFailedEvent extends Event
{
    public function __construct(
        private readonly Message $message,
        private readonly string $providerName,
        private readonly Throwable $exception,
    ) {
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }

    public function getException(): Throwable
    {
        return $this->exception;
    }
}
