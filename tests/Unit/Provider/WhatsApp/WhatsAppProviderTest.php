<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Provider\WhatsApp;

use Janwebdev\SocialPostBundle\Message\MessageBuilder;
use Janwebdev\SocialPostBundle\Provider\WhatsApp\WhatsAppClient;
use Janwebdev\SocialPostBundle\Provider\WhatsApp\WhatsAppProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @covers \Janwebdev\SocialPostBundle\Provider\WhatsApp\WhatsAppProvider
 */
class WhatsAppProviderTest extends TestCase
{
    private WhatsAppClient $clientMock;
    private WhatsAppProvider $provider;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(WhatsAppClient::class);
        $this->provider = new WhatsAppProvider($this->clientMock, new NullLogger());
    }

    public function testGetName(): void
    {
        $this->assertEquals('whatsapp', $this->provider->getName());
    }

    public function testCanPublishForWhatsAppNetwork(): void
    {
        $message = MessageBuilder::create()
            ->setText('Test')
            ->forNetworks(['whatsapp'])
            ->build();

        $this->assertTrue($this->provider->canPublish($message));
    }

    public function testPublishTextMessageSuccess(): void
    {
        $message = MessageBuilder::create()
            ->setText('Hello WhatsApp!')
            ->build();

        $this->clientMock->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function ($payload) {
                return $payload['messaging_product'] === 'whatsapp'
                    && $payload['type'] === 'text'
                    && $payload['text']['body'] === 'Hello WhatsApp!';
            }))
            ->willReturn([
                'messages' => [
                    ['id' => 'wamid.123456'],
                ],
            ]);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('whatsapp', $result->getProviderName());
        $this->assertEquals('wamid.123456', $result->getPostId());
    }

    public function testPublishWithImage(): void
    {
        $message = MessageBuilder::create()
            ->setText('Photo message')
            ->addImage('https://example.com/photo.jpg')
            ->build();

        $this->clientMock->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function ($payload) {
                return $payload['type'] === 'image'
                    && $payload['image']['link'] === 'https://example.com/photo.jpg'
                    && $payload['image']['caption'] === 'Photo message';
            }))
            ->willReturn(['messages' => [['id' => 'wamid.789']]]);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
    }

    public function testPublishFailureOnException(): void
    {
        $message = MessageBuilder::create()->setText('Test')->build();

        $this->clientMock->expects($this->once())
            ->method('sendMessage')
            ->willThrowException(new \RuntimeException('WhatsApp API Error'));

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('WhatsApp API Error', $result->getErrorMessage());
    }
}
