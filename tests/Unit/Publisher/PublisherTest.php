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

        $this->eventDispatcherMock->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(BeforePublishEvent::class)],
                [$this->isInstanceOf(AfterPublishEvent::class)]
            );

        $results = $this->publisher->publish($message);

        $this->assertEquals(2, $results->count());
        $this->assertTrue($results->isAllSuccessful());
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

        $this->eventDispatcherMock->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(BeforePublishEvent::class)],
                [$this->isInstanceOf(PublishFailedEvent::class)],
                [$this->isInstanceOf(AfterPublishEvent::class)]
            );

        $message = MessageBuilder::create()->setText('Test')->build();
        $results = $this->publisher->publish($message);

        $this->assertEquals(1, $results->count());
        $result = $results->getResult('twitter');
        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('Provider error', $result->getErrorMessage());
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
            ->with($this->isInstanceOf(PublishMessageCommand::class));

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
