<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Event;

use Janwebdev\SocialPostBundle\Event\AfterPublishEvent;
use Janwebdev\SocialPostBundle\Message\Message;
use Janwebdev\SocialPostBundle\Publisher\Result\PublishResultCollection;
use Janwebdev\SocialPostBundle\Provider\Result\PublishResult;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Janwebdev\SocialPostBundle\Event\AfterPublishEvent
 */
class AfterPublishEventTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $message = new Message(text: 'Test message');
        $results = new PublishResultCollection([
            'twitter' => PublishResult::success('twitter', '123'),
        ]);
        
        $event = new AfterPublishEvent($message, $results);

        $this->assertSame($message, $event->getMessage());
        $this->assertSame($results, $event->getResults());
    }

    public function testEventExtendsSymfonyEvent(): void
    {
        $message = new Message(text: 'Test');
        $results = new PublishResultCollection([]);
        $event = new AfterPublishEvent($message, $results);

        $this->assertInstanceOf(\Symfony\Contracts\EventDispatcher\Event::class, $event);
    }
}
