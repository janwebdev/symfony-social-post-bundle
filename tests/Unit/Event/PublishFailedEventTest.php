<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Event;

use Janwebdev\SocialPostBundle\Event\PublishFailedEvent;
use Janwebdev\SocialPostBundle\Message\Message;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Janwebdev\SocialPostBundle\Event\PublishFailedEvent
 */
class PublishFailedEventTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $message = new Message(text: 'Test message');
        $exception = new \RuntimeException('Network error');
        
        $event = new PublishFailedEvent($message, 'twitter', $exception);

        $this->assertSame($message, $event->getMessage());
        $this->assertEquals('twitter', $event->getProviderName());
        $this->assertSame($exception, $event->getException());
    }

    public function testEventExtendsSymfonyEvent(): void
    {
        $message = new Message(text: 'Test');
        $exception = new \Exception('Test exception');
        
        $event = new PublishFailedEvent($message, 'facebook', $exception);

        $this->assertInstanceOf(\Symfony\Contracts\EventDispatcher\Event::class, $event);
    }

    public function testWithDifferentExceptionTypes(): void
    {
        $message = new Message(text: 'Test');
        
        $runtimeException = new \RuntimeException('Runtime error');
        $event1 = new PublishFailedEvent($message, 'linkedin', $runtimeException);
        $this->assertInstanceOf(\RuntimeException::class, $event1->getException());
        
        $logicException = new \LogicException('Logic error');
        $event2 = new PublishFailedEvent($message, 'instagram', $logicException);
        $this->assertInstanceOf(\LogicException::class, $event2->getException());
    }

    public function testWithDifferentProviders(): void
    {
        $message = new Message(text: 'Test');
        $exception = new \Exception('Error');

        $event1 = new PublishFailedEvent($message, 'twitter', $exception);
        $this->assertEquals('twitter', $event1->getProviderName());

        $event2 = new PublishFailedEvent($message, 'facebook', $exception);
        $this->assertEquals('facebook', $event2->getProviderName());

        $event3 = new PublishFailedEvent($message, 'telegram', $exception);
        $this->assertEquals('telegram', $event3->getProviderName());
    }
}
