<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Provider\Telegram;

use Janwebdev\SocialPostBundle\Message\MessageBuilder;
use Janwebdev\SocialPostBundle\Provider\Telegram\TelegramClient;
use Janwebdev\SocialPostBundle\Provider\Telegram\TelegramProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @covers \Janwebdev\SocialPostBundle\Provider\Telegram\TelegramProvider
 */
class TelegramProviderTest extends TestCase
{
    private TelegramClient $clientMock;
    private TelegramProvider $provider;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(TelegramClient::class);
        $this->provider = new TelegramProvider($this->clientMock, new NullLogger());
    }

    public function testGetName(): void
    {
        $this->assertEquals('telegram', $this->provider->getName());
    }

    public function testCanPublishForTelegramNetwork(): void
    {
        $message = MessageBuilder::create()
            ->setText('Test')
            ->forNetworks(['telegram'])
            ->build();

        $this->assertTrue($this->provider->canPublish($message));
    }

    public function testPublishTextMessageSuccess(): void
    {
        $message = MessageBuilder::create()
            ->setText('Hello Telegram!')
            ->build();

        $this->clientMock->expects($this->once())
            ->method('sendMessage')
            ->willReturn([
                'ok' => true,
                'result' => [
                    'message_id' => 123,
                    'chat' => ['id' => -1001234567890],
                ],
            ]);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('telegram', $result->getProviderName());
        $this->assertEquals('123', $result->getPostId());
    }

    public function testPublishPhotoWithCaption(): void
    {
        $message = MessageBuilder::create()
            ->setText('Photo caption')
            ->addImage('https://example.com/photo.jpg')
            ->build();

        $this->clientMock->expects($this->once())
            ->method('sendPhoto')
            ->with(
                $this->equalTo('https://example.com/photo.jpg'),
                $this->stringContains('Photo caption')
            )
            ->willReturn([
                'ok' => true,
                'result' => [
                    'message_id' => 456,
                    'chat' => [
                        'id' => -1001234567890,
                        'username' => 'my_channel',
                    ],
                ],
            ]);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('456', $result->getPostId());
        $this->assertStringContainsString('t.me/my_channel/456', $result->getPostUrl());
    }

    public function testPublishFailureWhenNotOk(): void
    {
        $message = MessageBuilder::create()->setText('Test')->build();

        $this->clientMock->expects($this->once())
            ->method('sendMessage')
            ->willReturn([
                'ok' => false,
                'description' => 'Bot was blocked',
            ]);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('Bot was blocked', $result->getErrorMessage());
    }

    public function testPublishFailureOnException(): void
    {
        $message = MessageBuilder::create()->setText('Test')->build();

        $this->clientMock->expects($this->once())
            ->method('sendMessage')
            ->willThrowException(new \RuntimeException('Network error'));

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('Network error', $result->getErrorMessage());
    }
}
