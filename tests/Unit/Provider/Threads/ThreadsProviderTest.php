<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Provider\Threads;

use Janwebdev\SocialPostBundle\Message\MessageBuilder;
use Janwebdev\SocialPostBundle\Provider\Threads\ThreadsClient;
use Janwebdev\SocialPostBundle\Provider\Threads\ThreadsProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @covers \Janwebdev\SocialPostBundle\Provider\Threads\ThreadsProvider
 */
class ThreadsProviderTest extends TestCase
{
    private ThreadsClient $clientMock;
    private ThreadsProvider $provider;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(ThreadsClient::class);
        $this->provider = new ThreadsProvider($this->clientMock, new NullLogger());
    }

    public function testGetName(): void
    {
        $this->assertEquals('threads', $this->provider->getName());
    }

    public function testCanPublishForThreadsNetwork(): void
    {
        $message = MessageBuilder::create()
            ->setText('Test')
            ->forNetworks(['threads'])
            ->build();

        $this->assertTrue($this->provider->canPublish($message));
    }

    public function testCannotPublishForOtherNetworks(): void
    {
        $message = MessageBuilder::create()
            ->setText('Test')
            ->forNetworks(['twitter'])
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

    public function testPublishSuccessWithTwoStepProcess(): void
    {
        $message = MessageBuilder::create()
            ->setText('Hello Threads!')
            ->build();

        // Step 1: Create container
        $this->clientMock->expects($this->once())
            ->method('createMediaContainer')
            ->willReturn(['id' => 'container_123']);

        // Step 2: Publish container
        $this->clientMock->expects($this->once())
            ->method('publishMediaContainer')
            ->with('container_123')
            ->willReturn(['id' => 'post_456']);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('threads', $result->getProviderName());
        $this->assertEquals('post_456', $result->getPostId());
        $this->assertStringContainsString('post_456', $result->getPostUrl());
    }

    public function testPublishWithImage(): void
    {
        $message = MessageBuilder::create()
            ->setText('Photo post')
            ->addImage('https://example.com/photo.jpg')
            ->build();

        $this->clientMock->expects($this->once())
            ->method('createMediaContainer')
            ->with($this->callback(function ($data) {
                return isset($data['image_url']) 
                    && $data['image_url'] === 'https://example.com/photo.jpg';
            }))
            ->willReturn(['id' => 'container_789']);

        $this->clientMock->method('publishMediaContainer')
            ->willReturn(['id' => 'post_789']);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
    }

    public function testPublishWithLink(): void
    {
        $message = MessageBuilder::create()
            ->setText('Check this out')
            ->setLink('https://example.com')
            ->build();

        $this->clientMock->expects($this->once())
            ->method('createMediaContainer')
            ->with($this->callback(function ($data) {
                return str_contains($data['text'], 'https://example.com');
            }))
            ->willReturn(['id' => 'container_999']);

        $this->clientMock->method('publishMediaContainer')
            ->willReturn(['id' => 'post_999']);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
    }

    public function testPublishFailureWhenContainerCreationFails(): void
    {
        $message = MessageBuilder::create()
            ->setText('Test')
            ->build();

        $this->clientMock->expects($this->once())
            ->method('createMediaContainer')
            ->willReturn([]); // No ID returned

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('Failed to create media container', $result->getErrorMessage());
    }

    public function testPublishFailureOnException(): void
    {
        $message = MessageBuilder::create()->setText('Test')->build();

        $this->clientMock->expects($this->once())
            ->method('createMediaContainer')
            ->willThrowException(new \RuntimeException('Threads API Error'));

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('Threads API Error', $result->getErrorMessage());
    }

    public function testTextTruncation(): void
    {
        $longText = str_repeat('a', 600); // Longer than 500 chars
        $message = MessageBuilder::create()
            ->setText($longText)
            ->build();

        $this->clientMock->expects($this->once())
            ->method('createMediaContainer')
            ->with($this->callback(function ($data) {
                return mb_strlen($data['text']) <= 500;
            }))
            ->willReturn(['id' => 'container_123']);

        $this->clientMock->method('publishMediaContainer')
            ->willReturn(['id' => 'post_123']);

        $this->provider->publish($message);
    }
}
