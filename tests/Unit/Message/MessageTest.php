<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Message;

use Janwebdev\SocialPostBundle\Message\Message;
use Janwebdev\SocialPostBundle\Message\Attachment\Image;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Janwebdev\SocialPostBundle\Message\Message
 */
class MessageTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $message = new Message(
            text: 'Test message',
            link: 'https://example.com',
            networks: ['twitter', 'facebook'],
            attachments: [],
            metadata: ['key' => 'value']
        );

        $this->assertEquals('Test message', $message->getText());
        $this->assertEquals('https://example.com', $message->getLink());
        $this->assertEquals(['twitter', 'facebook'], $message->getNetworks());
        $this->assertEquals(['key' => 'value'], $message->getMetadata());
    }

    public function testIsForNetworkWithSpecificNetworks(): void
    {
        $message = new Message(
            text: 'Test',
            networks: ['twitter', 'facebook']
        );

        $this->assertTrue($message->isForNetwork('twitter'));
        $this->assertTrue($message->isForNetwork('facebook'));
        $this->assertFalse($message->isForNetwork('linkedin'));
    }

    public function testIsForNetworkWithEmptyNetworksReturnsTrue(): void
    {
        $message = new Message(
            text: 'Test',
            networks: []
        );

        // Empty networks means publish to all
        $this->assertTrue($message->isForNetwork('twitter'));
        $this->assertTrue($message->isForNetwork('facebook'));
        $this->assertTrue($message->isForNetwork('linkedin'));
    }

    public function testHasAttachments(): void
    {
        $image = new Image('/path/to/image.jpg');
        
        $messageWithAttachments = new Message(
            text: 'Test',
            attachments: [$image]
        );

        $messageWithoutAttachments = new Message(
            text: 'Test',
            attachments: []
        );

        $this->assertTrue($messageWithAttachments->hasAttachments());
        $this->assertFalse($messageWithoutAttachments->hasAttachments());
    }

    public function testGetAttachments(): void
    {
        $image = new Image('/path/to/image.jpg');
        
        $message = new Message(
            text: 'Test',
            attachments: [$image]
        );

        $attachments = $message->getAttachments();
        $this->assertCount(1, $attachments);
        $this->assertSame($image, $attachments[0]);
    }

    public function testGetMetadataValue(): void
    {
        $message = new Message(
            text: 'Test',
            metadata: [
                'campaign_id' => '123',
                'source' => 'website',
            ]
        );

        $this->assertEquals('123', $message->getMetadataValue('campaign_id'));
        $this->assertEquals('website', $message->getMetadataValue('source'));
        $this->assertNull($message->getMetadataValue('non_existent'));
        $this->assertEquals('default', $message->getMetadataValue('non_existent', 'default'));
    }

    public function testMessageWithNullLink(): void
    {
        $message = new Message(text: 'Test');

        $this->assertNull($message->getLink());
    }

    public function testMessageWithDefaultParameters(): void
    {
        $message = new Message(text: 'Simple message');

        $this->assertEquals('Simple message', $message->getText());
        $this->assertNull($message->getLink());
        $this->assertEquals([], $message->getNetworks());
        $this->assertEquals([], $message->getAttachments());
        $this->assertEquals([], $message->getMetadata());
        $this->assertFalse($message->hasAttachments());
    }
}
