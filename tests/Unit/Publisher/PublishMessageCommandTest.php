<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Publisher;

use Janwebdev\SocialPostBundle\Message\Message;
use Janwebdev\SocialPostBundle\Publisher\PublishMessageCommand;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Janwebdev\SocialPostBundle\Publisher\PublishMessageCommand
 */
class PublishMessageCommandTest extends TestCase
{
    public function testConstructorAndGetMessage(): void
    {
        $message = new Message(text: 'Test message');
        $command = new PublishMessageCommand($message);

        $this->assertSame($message, $command->getMessage());
    }

    public function testWithComplexMessage(): void
    {
        $message = new Message(
            text: 'Complex message',
            link: 'https://example.com',
            networks: ['twitter', 'facebook'],
            metadata: ['campaign' => 'test']
        );
        
        $command = new PublishMessageCommand($message);

        $retrievedMessage = $command->getMessage();
        $this->assertEquals('Complex message', $retrievedMessage->getText());
        $this->assertEquals('https://example.com', $retrievedMessage->getLink());
        $this->assertEquals(['twitter', 'facebook'], $retrievedMessage->getNetworks());
    }
}
