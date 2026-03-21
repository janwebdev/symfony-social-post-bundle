<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Provider\Pinterest;

use Janwebdev\SocialPostBundle\Message\MessageBuilder;
use Janwebdev\SocialPostBundle\Provider\Pinterest\PinterestClient;
use Janwebdev\SocialPostBundle\Provider\Pinterest\PinterestProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @covers \Janwebdev\SocialPostBundle\Provider\Pinterest\PinterestProvider
 */
class PinterestProviderTest extends TestCase
{
    private PinterestClient $clientMock;
    private PinterestProvider $provider;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(PinterestClient::class);
        $this->provider = new PinterestProvider($this->clientMock, new NullLogger());
    }

    public function testGetName(): void
    {
        $this->assertEquals('pinterest', $this->provider->getName());
    }

    public function testCanPublishForPinterestNetwork(): void
    {
        $message = MessageBuilder::create()->setText('Test')->forNetworks(['pinterest'])->build();
        $this->assertTrue($this->provider->canPublish($message));
    }

    public function testCanPublishForAllNetworks(): void
    {
        $message = MessageBuilder::create()->setText('Test')->forAllNetworks()->build();
        $this->assertTrue($this->provider->canPublish($message));
    }

    public function testCannotPublishForOtherNetworks(): void
    {
        $message = MessageBuilder::create()->setText('Test')->forNetworks(['twitter'])->build();
        $this->assertFalse($this->provider->canPublish($message));
    }

    public function testIsConfigured(): void
    {
        $this->clientMock->expects($this->once())->method('isConfigured')->willReturn(true);
        $this->assertTrue($this->provider->isConfigured());
    }

    public function testPublishTextPinSuccess(): void
    {
        $message = MessageBuilder::create()
            ->setText('Check this out!')
            ->setLink('https://example.com')
            ->build();

        $this->clientMock->expects($this->once())
            ->method('createPin')
            ->willReturn(['id' => 'pin_abc123']);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('pinterest', $result->getProviderName());
        $this->assertEquals('pin_abc123', $result->getPostId());
        $this->assertStringContainsString('pin_abc123', $result->getPostUrl() ?? '');
    }

    public function testPublishImagePinSuccess(): void
    {
        $message = MessageBuilder::create()
            ->setText('Pin with image')
            ->addImage('https://example.com/image.jpg')
            ->build();

        $this->clientMock->expects($this->once())
            ->method('createPin')
            ->with($this->callback(function ($data) {
                return isset($data['media_source']['source_type'])
                    && $data['media_source']['source_type'] === 'image_url';
            }))
            ->willReturn(['id' => 'pin_img456']);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('pin_img456', $result->getPostId());
    }

    public function testPublishFailureOnApiError(): void
    {
        $message = MessageBuilder::create()->setText('Test')->build();

        $this->clientMock->expects($this->once())
            ->method('createPin')
            ->willThrowException(new \RuntimeException('API Error'));

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('API Error', $result->getErrorMessage() ?? '');
    }

    public function testPublishFailureWhenNoIdReturned(): void
    {
        $message = MessageBuilder::create()->setText('Test')->build();

        $this->clientMock->expects($this->once())
            ->method('createPin')
            ->willReturn([]); // No 'id' key

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isFailure());
    }
}
