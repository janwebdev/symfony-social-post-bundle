<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Provider\Discord;

use Janwebdev\SocialPostBundle\Message\MessageBuilder;
use Janwebdev\SocialPostBundle\Provider\Discord\DiscordClient;
use Janwebdev\SocialPostBundle\Provider\Discord\DiscordProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @covers \Janwebdev\SocialPostBundle\Provider\Discord\DiscordProvider
 */
class DiscordProviderTest extends TestCase
{
    private DiscordClient $clientMock;
    private DiscordProvider $provider;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(DiscordClient::class);
        $this->provider = new DiscordProvider($this->clientMock, new NullLogger());
    }

    public function testGetName(): void
    {
        $this->assertEquals('discord', $this->provider->getName());
    }

    public function testCanPublishForDiscordNetwork(): void
    {
        $message = MessageBuilder::create()
            ->setText('Test')
            ->forNetworks(['discord'])
            ->build();

        $this->assertTrue($this->provider->canPublish($message));
    }

    public function testPublishSimpleMessage(): void
    {
        $message = MessageBuilder::create()
            ->setText('Hello Discord!')
            ->build();

        $this->clientMock->expects($this->once())
            ->method('executeWebhook')
            ->with($this->callback(function ($payload) {
                return $payload['content'] === 'Hello Discord!';
            }))
            ->willReturn(['id' => '123456', 'channel_id' => '789']);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('discord', $result->getProviderName());
        $this->assertEquals('123456', $result->getPostId());
    }

    public function testPublishWithEmbedForRichContent(): void
    {
        $message = MessageBuilder::create()
            ->setText('Check this out')
            ->setLink('https://example.com')
            ->build();

        $this->clientMock->expects($this->once())
            ->method('executeWebhook')
            ->with($this->callback(function ($payload) {
                return isset($payload['embeds']) 
                    && $payload['embeds'][0]['description'] === 'Check this out'
                    && $payload['embeds'][0]['url'] === 'https://example.com';
            }))
            ->willReturn(['id' => '999']);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
    }

    public function testPublishWithImage(): void
    {
        $message = MessageBuilder::create()
            ->setText('Photo post')
            ->addImage('https://example.com/image.jpg')
            ->build();

        $this->clientMock->expects($this->once())
            ->method('executeWebhook')
            ->with($this->callback(function ($payload) {
                return isset($payload['embeds'][0]['image']['url']) 
                    && $payload['embeds'][0]['image']['url'] === 'https://example.com/image.jpg';
            }))
            ->willReturn(['id' => '888']);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
    }

    public function testPublishFailure(): void
    {
        $message = MessageBuilder::create()->setText('Test')->build();

        $this->clientMock->expects($this->once())
            ->method('executeWebhook')
            ->willThrowException(new \RuntimeException('Discord webhook error'));

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('Discord webhook error', $result->getErrorMessage());
    }
}
