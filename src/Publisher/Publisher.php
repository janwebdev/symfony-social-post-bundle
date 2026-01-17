<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Publisher;

use Janwebdev\SocialPostBundle\Event\AfterPublishEvent;
use Janwebdev\SocialPostBundle\Event\BeforePublishEvent;
use Janwebdev\SocialPostBundle\Event\PublishFailedEvent;
use Janwebdev\SocialPostBundle\Message\Message;
use Janwebdev\SocialPostBundle\Provider\ProviderInterface;
use Janwebdev\SocialPostBundle\Provider\Result\PublishResult;
use Janwebdev\SocialPostBundle\Publisher\Result\PublishResultCollection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Main publisher that coordinates publishing to multiple social networks.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
final readonly class Publisher implements PublisherInterface
{
    /**
     * @param iterable<ProviderInterface> $providers
     */
    public function __construct(
        private iterable $providers,
        private EventDispatcherInterface $eventDispatcher,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function publish(Message $message): PublishResultCollection
    {
        $this->eventDispatcher->dispatch(new BeforePublishEvent($message));

        $results = [];

        foreach ($this->providers as $provider) {
            if (!$provider->isConfigured()) {
                $this->logger->warning("Provider {$provider->getName()} is not configured, skipping");
                continue;
            }

            if (!$provider->canPublish($message)) {
                $this->logger->debug("Provider {$provider->getName()} cannot publish this message, skipping");
                continue;
            }

            try {
                $this->logger->info("Publishing to {$provider->getName()}");
                $result = $provider->publish($message);
                $results[$provider->getName()] = $result;

                if ($result->isSuccess()) {
                    $this->logger->info("Successfully published to {$provider->getName()}", [
                        'post_id' => $result->getPostId(),
                        'post_url' => $result->getPostUrl(),
                    ]);
                } else {
                    $this->logger->error("Failed to publish to {$provider->getName()}: {$result->getErrorMessage()}");
                }
            } catch (\Throwable $e) {
                $this->logger->error("Exception while publishing to {$provider->getName()}: {$e->getMessage()}", [
                    'exception' => $e,
                ]);

                $result = PublishResult::failure(
                    providerName: $provider->getName(),
                    errorMessage: $e->getMessage(),
                    exception: $e,
                );
                $results[$provider->getName()] = $result;

                $this->eventDispatcher->dispatch(new PublishFailedEvent($message, $provider->getName(), $e));
            }
        }

        $collection = new PublishResultCollection($results);
        $this->eventDispatcher->dispatch(new AfterPublishEvent($message, $collection));

        return $collection;
    }

    public function publishAsync(Message $message): void
    {
        $this->logger->info('Dispatching message for async publishing');
        $this->messageBus->dispatch(new PublishMessageCommand($message));
    }
}
