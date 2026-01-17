<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Publisher;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler for async message publishing via Symfony Messenger.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
#[AsMessageHandler]
final readonly class PublishMessageHandler
{
    public function __construct(
        private PublisherInterface $publisher,
    ) {
    }

    public function __invoke(PublishMessageCommand $command): void
    {
        $this->publisher->publish($command->getMessage());
    }
}
