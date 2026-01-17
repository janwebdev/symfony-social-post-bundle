<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Message;

use Janwebdev\SocialPostBundle\Message\Message;
use Janwebdev\SocialPostBundle\Message\MessageBuilder;
use Janwebdev\SocialPostBundle\Message\Attachment\Image;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Janwebdev\SocialPostBundle\Message\MessageBuilder
 */
class MessageBuilderTest extends TestCase
{
    public function testCreateReturnsBuilder(): void
    {
        $builder = MessageBuilder::create();
        
        $this->assertInstanceOf(MessageBuilder::class, $builder);
    }

    public function testSetText(): void
    {
        $message = MessageBuilder::create()
            ->setText('Hello World')
            ->build();
        
        $this->assertEquals('Hello World', $message->getText());
    }

    public function testSetLink(): void
    {
        $message = MessageBuilder::create()
            ->setText('Test')
            ->setLink('https://example.com')
            ->build();
        
        $this->assertEquals('https://example.com', $message->getLink());
    }

    public function testForNetworks(): void
    {
        $message = MessageBuilder::create()
            ->setText('Test')
            ->forNetworks(['twitter', 'facebook'])
            ->build();
        
        $this->assertEquals(['twitter', 'facebook'], $message->getNetworks());
    }

    public function testForAllNetworks(): void
    {
        $message = MessageBuilder::create()
            ->setText('Test')
            ->forAllNetworks()
            ->build();
        
        $this->assertEquals([], $message->getNetworks());
    }

    public function testAddImage(): void
    {
        $message = MessageBuilder::create()
            ->setText('Test')
            ->addImage('/path/to/image.jpg', 'Alt text')
            ->build();
        
        $this->assertTrue($message->hasAttachments());
        $attachments = $message->getAttachments();
        $this->assertCount(1, $attachments);
        $this->assertInstanceOf(Image::class, $attachments[0]);
    }

    public function testSetMetadata(): void
    {
        $metadata = ['key1' => 'value1', 'key2' => 'value2'];
        $message = MessageBuilder::create()
            ->setText('Test')
            ->setMetadata($metadata)
            ->build();
        
        $this->assertEquals($metadata, $message->getMetadata());
    }

    public function testAddMetadata(): void
    {
        $message = MessageBuilder::create()
            ->setText('Test')
            ->addMetadata('campaign_id', '123')
            ->addMetadata('source', 'website')
            ->build();
        
        $this->assertEquals('123', $message->getMetadataValue('campaign_id'));
        $this->assertEquals('website', $message->getMetadataValue('source'));
    }

    public function testCompleteMessage(): void
    {
        $message = MessageBuilder::create()
            ->setText('Complete message')
            ->setLink('https://example.com')
            ->forNetworks(['twitter'])
            ->addImage('/path/to/image.jpg')
            ->addMetadata('test', 'value')
            ->build();
        
        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals('Complete message', $message->getText());
        $this->assertEquals('https://example.com', $message->getLink());
        $this->assertTrue($message->isForNetwork('twitter'));
        $this->assertTrue($message->hasAttachments());
        $this->assertEquals('value', $message->getMetadataValue('test'));
    }
}
