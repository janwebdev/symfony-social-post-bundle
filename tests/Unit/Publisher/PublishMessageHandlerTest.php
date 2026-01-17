<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Publisher;

use Janwebdev\SocialPostBundle\Message\Message;
use Janwebdev\SocialPostBundle\Publisher\PublishMessageCommand;
use Janwebdev\SocialPostBundle\Publisher\PublishMessageHandler;
use Janwebdev\SocialPostBundle\Publisher\PublisherInterface;
use Janwebdev\SocialPostBundle\Publisher\Result\PublishResultCollection;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Janwebdev\SocialPostBundle\Publisher\PublishMessageHandler
 */
class PublishMessageHandlerTest extends TestCase
{
    public function testInvokeCallsPublisher(): void
    {
        $message = new Message(text: 'Test message');
        $command = new PublishMessageCommand($message);
        
        $publisherMock = $this->createMock(PublisherInterface::class);
        $publisherMock->expects($this->once())
            ->method('publish')
            ->with($this->identicalTo($message))
            ->willReturn(new PublishResultCollection([]));

        $handler = new PublishMessageHandler($publisherMock);
        $handler($command);
    }

    public function testInvokeWithComplexMessage(): void
    {
        $message = new Message(
            text: 'Complex message',
            link: 'https://example.com',
            networks: ['twitter', 'facebook']
        );
        $command = new PublishMessageCommand($message);
        
        $publisherMock = $this->createMock(PublisherInterface::class);
        $publisherMock->expects($this->once())
            ->method('publish')
            ->with($this->callback(function ($msg) {
                return $msg->getText() === 'Complex message'
                    && $msg->getLink() === 'https://example.com'
                    && $msg->getNetworks() === ['twitter', 'facebook'];
            }))
            ->willReturn(new PublishResultCollection([]));

        $handler = new PublishMessageHandler($publisherMock);
        $handler($command);
    }

    public function testHandlerHasMessageHandlerAttribute(): void
    {
        $reflection = new \ReflectionClass(PublishMessageHandler::class);
        $attributes = $reflection->getAttributes(\Symfony\Component\Messenger\Attribute\AsMessageHandler::class);

        $this->assertCount(1, $attributes);
    }
}
