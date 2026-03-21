<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Publisher;

use Janwebdev\SocialPostBundle\Event\AfterPublishEvent;
use Janwebdev\SocialPostBundle\Event\BeforePublishEvent;
use Janwebdev\SocialPostBundle\Event\PublishFailedEvent;
use Janwebdev\SocialPostBundle\Message\MessageBuilder;
use Janwebdev\SocialPostBundle\Provider\ProviderInterface;
use Janwebdev\SocialPostBundle\Provider\Result\PublishResult;
use Janwebdev\SocialPostBundle\Publisher\Publisher;
use Janwebdev\SocialPostBundle\Publisher\PublishMessageCommand;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @covers \Janwebdev\SocialPostBundle\Publisher\Publisher
 */
class PublisherTest extends TestCase
{
    private EventDispatcherInterface $eventDispatcherMock;
    private MessageBusInterface $messageBusMock;
    private Publisher $publisher;

    protected function setUp(): void
    {
        $this->eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $this->messageBusMock = $this->createMock(MessageBusInterface::class);
    }

    public function testPublishToMultipleProviders(): void
    {
        $provider1 = $this->createConfiguredProvider('twitter', true);
        $provider2 = $this->createConfiguredProvider('facebook', true);

        $this->publisher = new Publisher(
            [$provider1, $provider2],
            $this->eventDispatcherMock,
            $this->messageBusMock,
            new NullLogger()
        );

        $message = MessageBuilder::create()->setText('Test')->build();

        $dispatchedEvents = [];
        $this->eventDispatcherMock->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$dispatchedEvents): object {
                $dispatchedEvents[] = $event;
                return $event;
            });

        $results = $this->publisher->publish($message);

        $this->assertEquals(2, $results->count());
        $this->assertTrue($results->isAllSuccessful());
        $this->assertCount(2, $dispatchedEvents);
        $this->assertInstanceOf(BeforePublishEvent::class, $dispatchedEvents[0]);
        $this->assertInstanceOf(AfterPublishEvent::class, $dispatchedEvents[1]);
    }

    public function testPublishSkipsUnconfiguredProviders(): void
    {
        $configured = $this->createConfiguredProvider('twitter', true);
        $unconfigured = $this->createConfiguredProvider('facebook', false);

        $this->publisher = new Publisher(
            [$configured, $unconfigured],
            $this->eventDispatcherMock,
            $this->messageBusMock,
            new NullLogger()
        );

        $message = MessageBuilder::create()->setText('Test')->build();
        $results = $this->publisher->publish($message);

        // Only one provider should publish
        $this->assertEquals(1, $results->count());
        $this->assertNotNull($results->getResult('twitter'));
        $this->assertNull($results->getResult('facebook'));
    }

    public function testPublishSkipsProvidersThatCannotPublish(): void
    {
        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('getName')->willReturn('twitter');
        $provider->method('isConfigured')->willReturn(true);
        $provider->expects($this->once())
            ->method('canPublish')
            ->willReturn(false); // Cannot publish this message

        $this->publisher = new Publisher(
            [$provider],
            $this->eventDispatcherMock,
            $this->messageBusMock,
            new NullLogger()
        );

        $message = MessageBuilder::create()->setText('Test')->build();
        $results = $this->publisher->publish($message);

        $this->assertEquals(0, $results->count());
    }

    public function testPublishHandlesExceptions(): void
    {
        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('getName')->willReturn('twitter');
        $provider->method('isConfigured')->willReturn(true);
        $provider->method('canPublish')->willReturn(true);
        $provider->expects($this->once())
            ->method('publish')
            ->willThrowException(new \RuntimeException('Provider error'));

        $this->publisher = new Publisher(
            [$provider],
            $this->eventDispatcherMock,
            $this->messageBusMock,
            new NullLogger()
        );

        $dispatchedEvents = [];
        $this->eventDispatcherMock->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$dispatchedEvents): object {
                $dispatchedEvents[] = $event;
                return $event;
            });

        $message = MessageBuilder::create()->setText('Test')->build();
        $results = $this->publisher->publish($message);

        $this->assertEquals(1, $results->count());
        $result = $results->getResult('twitter');
        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('Provider error', $result->getErrorMessage());
        $this->assertCount(3, $dispatchedEvents);
        $this->assertInstanceOf(BeforePublishEvent::class, $dispatchedEvents[0]);
        $this->assertInstanceOf(PublishFailedEvent::class, $dispatchedEvents[1]);
        $this->assertInstanceOf(AfterPublishEvent::class, $dispatchedEvents[2]);
    }

    public function testPublishAsync(): void
    {
        $this->publisher = new Publisher(
            [],
            $this->eventDispatcherMock,
            $this->messageBusMock,
            new NullLogger()
        );

        $message = MessageBuilder::create()->setText('Test')->build();

        $this->messageBusMock->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(PublishMessageCommand::class))
            ->willReturn(new Envelope(new PublishMessageCommand($message)));

        $this->publisher->publishAsync($message);
    }

    private function createConfiguredProvider(string $name, bool $configured): ProviderInterface
    {
        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('getName')->willReturn($name);
        $provider->method('isConfigured')->willReturn($configured);
        $provider->method('canPublish')->willReturn(true);
        
        if ($configured) {
            $provider->method('publish')->willReturn(
                PublishResult::success($name, '123', 'https://example.com/123')
            );
        }

        return $provider;
    }
}
