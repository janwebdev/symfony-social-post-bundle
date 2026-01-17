<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Event;

use Janwebdev\SocialPostBundle\Event\BeforePublishEvent;
use Janwebdev\SocialPostBundle\Message\Message;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Janwebdev\SocialPostBundle\Event\BeforePublishEvent
 */
class BeforePublishEventTest extends TestCase
{
    public function testConstructorAndGetMessage(): void
    {
        $message = new Message(text: 'Test message');
        $event = new BeforePublishEvent($message);

        $this->assertSame($message, $event->getMessage());
    }

    public function testEventExtendsSymfonyEvent(): void
    {
        $message = new Message(text: 'Test');
        $event = new BeforePublishEvent($message);

        $this->assertInstanceOf(\Symfony\Contracts\EventDispatcher\Event::class, $event);
    }
}
