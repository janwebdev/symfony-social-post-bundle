<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Provider\LinkedIn;

use Janwebdev\SocialPostBundle\Message\Attachment\Image;
use Janwebdev\SocialPostBundle\Message\MessageBuilder;
use Janwebdev\SocialPostBundle\Provider\LinkedIn\LinkedInClient;
use Janwebdev\SocialPostBundle\Provider\LinkedIn\LinkedInProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @covers \Janwebdev\SocialPostBundle\Provider\LinkedIn\LinkedInProvider
 */
class LinkedInProviderTest extends TestCase
{
    private LinkedInClient $clientMock;
    private LinkedInProvider $provider;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(LinkedInClient::class);
        $this->provider = new LinkedInProvider($this->clientMock, new NullLogger());
    }

    public function testGetName(): void
    {
        $this->assertEquals('linkedin', $this->provider->getName());
    }

    public function testCanPublishForLinkedInNetwork(): void
    {
        $message = MessageBuilder::create()
            ->setText('Test')
            ->forNetworks(['linkedin'])
            ->build();

        $this->assertTrue($this->provider->canPublish($message));
    }

    public function testPublishSuccess(): void
    {
        $message = MessageBuilder::create()
            ->setText('Hello LinkedIn!')
            ->build();

        $this->clientMock->expects($this->once())
            ->method('createPost')
            ->willReturn(['id' => 'urn:li:share:1234567']);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('linkedin', $result->getProviderName());
        $this->assertEquals('urn:li:share:1234567', $result->getPostId());
    }

    public function testPublishWithLink(): void
    {
        $message = MessageBuilder::create()
            ->setText('Check out this article')
            ->setLink('https://example.com/article')
            ->build();

        $this->clientMock->expects($this->once())
            ->method('createPost')
            ->with($this->callback(function ($data) {
                return isset($data['content']['article']['source'])
                    && $data['content']['article']['source'] === 'https://example.com/article';
            }))
            ->willReturn(['id' => 'urn:li:share:999']);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
    }

    public function testPublishFailure(): void
    {
        $message = MessageBuilder::create()->setText('Test')->build();

        $this->clientMock->expects($this->once())
            ->method('createPost')
            ->willThrowException(new \RuntimeException('LinkedIn API Error'));

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('LinkedIn API Error', $result->getErrorMessage());
    }

    public function testPublishSuccessWithNewPostsApi(): void
    {
        $message = MessageBuilder::create()
            ->setText('LinkedIn post via new API')
            ->build();

        $this->clientMock->method('isConfigured')->willReturn(true);

        $this->clientMock->expects($this->once())
            ->method('createPost')
            ->willReturn(['id' => 'urn:li:share:987654321']);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('urn:li:share:987654321', $result->getPostId());
        $this->assertStringContainsString('987654321', $result->getPostUrl() ?? '');
    }

    public function testPublishWithImage(): void
    {
        $image = new Image('/tmp/test.jpg', 'A test image');

        $message = MessageBuilder::create()
            ->setText('Post with image')
            ->addAttachment($image)
            ->build();

        $this->clientMock->expects($this->once())
            ->method('uploadImage')
            ->with($image)
            ->willReturn('urn:li:image:TEST123');

        $this->clientMock->expects($this->once())
            ->method('createPost')
            ->with($this->callback(function (array $data): bool {
                return isset($data['content']['media']['id'])
                    && $data['content']['media']['id'] === 'urn:li:image:TEST123'
                    && isset($data['content']['media']['altText'])
                    && $data['content']['media']['altText'] === 'A test image';
            }))
            ->willReturn(['id' => 'urn:li:share:IMG456']);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('urn:li:share:IMG456', $result->getPostId());
    }

    public function testPublishWithImageAndLink(): void
    {
        $image = new Image('/tmp/test.jpg');

        $message = MessageBuilder::create()
            ->setText('Post with image and link')
            ->setLink('https://example.com')
            ->addAttachment($image)
            ->build();

        $this->clientMock->expects($this->once())
            ->method('uploadImage')
            ->willReturn('urn:li:image:TEST456');

        $this->clientMock->expects($this->once())
            ->method('createPost')
            ->with($this->callback(function (array $data): bool {
                return isset($data['content']['media']['id'])
                    && !isset($data['content']['article']);
            }))
            ->willReturn(['id' => 'urn:li:share:IMG789']);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
    }
}
