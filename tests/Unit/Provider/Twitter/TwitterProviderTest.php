<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Provider\Twitter;

use Janwebdev\SocialPostBundle\Message\MessageBuilder;
use Janwebdev\SocialPostBundle\Provider\Twitter\TwitterClient;
use Janwebdev\SocialPostBundle\Provider\Twitter\TwitterProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @covers \Janwebdev\SocialPostBundle\Provider\Twitter\TwitterProvider
 */
class TwitterProviderTest extends TestCase
{
    private TwitterClient $clientMock;
    private TwitterProvider $provider;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(TwitterClient::class);
        $this->provider = new TwitterProvider($this->clientMock, new NullLogger());
    }

    public function testGetName(): void
    {
        $this->assertEquals('twitter', $this->provider->getName());
    }

    public function testCanPublishForTwitterNetwork(): void
    {
        $message = MessageBuilder::create()
            ->setText('Test')
            ->forNetworks(['twitter'])
            ->build();

        $this->assertTrue($this->provider->canPublish($message));
    }

    public function testCanPublishForAllNetworks(): void
    {
        $message = MessageBuilder::create()
            ->setText('Test')
            ->forAllNetworks()
            ->build();

        $this->assertTrue($this->provider->canPublish($message));
    }

    public function testCannotPublishForOtherNetworks(): void
    {
        $message = MessageBuilder::create()
            ->setText('Test')
            ->forNetworks(['facebook'])
            ->build();

        $this->assertFalse($this->provider->canPublish($message));
    }

    public function testIsConfigured(): void
    {
        $this->clientMock->expects($this->once())
            ->method('isConfigured')
            ->willReturn(true);

        $this->assertTrue($this->provider->isConfigured());
    }

    public function testPublishSuccess(): void
    {
        $message = MessageBuilder::create()
            ->setText('Hello Twitter!')
            ->build();

        $this->clientMock->expects($this->once())
            ->method('isConfigured')
            ->willReturn(true);

        $this->clientMock->expects($this->once())
            ->method('uploadMedia')
            ->willReturn([]);

        $this->clientMock->expects($this->once())
            ->method('createTweet')
            ->willReturn(['data' => ['id' => '1234567890']]);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('twitter', $result->getProviderName());
        $this->assertEquals('1234567890', $result->getPostId());
        $this->assertStringContainsString('1234567890', $result->getPostUrl());
    }

    public function testPublishWithImage(): void
    {
        $message = MessageBuilder::create()
            ->setText('With image')
            ->addImage('/path/to/image.jpg')
            ->build();

        $this->clientMock->method('isConfigured')->willReturn(true);
        
        $this->clientMock->expects($this->once())
            ->method('uploadMedia')
            ->willReturn(['media123']);

        $this->clientMock->expects($this->once())
            ->method('createTweet')
            ->with($this->callback(function ($data) {
                return isset($data['media']['media_ids']) && $data['media']['media_ids'] === ['media123'];
            }))
            ->willReturn(['data' => ['id' => '9999']]);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
    }

    public function testPublishFailure(): void
    {
        $message = MessageBuilder::create()->setText('Test')->build();

        $this->clientMock->method('isConfigured')->willReturn(true);
        $this->clientMock->method('uploadMedia')->willReturn([]);
        $this->clientMock->expects($this->once())
            ->method('createTweet')
            ->willThrowException(new \RuntimeException('API Error'));

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isFailure());
        $this->assertEquals('twitter', $result->getProviderName());
        $this->assertStringContainsString('API Error', $result->getErrorMessage());
    }

    public function testTextTruncation(): void
    {
        $longText = str_repeat('a', 300); // Longer than 280 chars
        $message = MessageBuilder::create()
            ->setText($longText)
            ->build();

        $this->clientMock->method('isConfigured')->willReturn(true);
        $this->clientMock->method('uploadMedia')->willReturn([]);
        
        $this->clientMock->expects($this->once())
            ->method('createTweet')
            ->with($this->callback(function ($data) {
                return mb_strlen($data['text']) <= 280;
            }))
            ->willReturn(['data' => ['id' => '123']]);

        $this->provider->publish($message);
    }
}
