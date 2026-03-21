<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Provider\Instagram;

use Janwebdev\SocialPostBundle\Message\MessageBuilder;
use Janwebdev\SocialPostBundle\Provider\Instagram\InstagramClient;
use Janwebdev\SocialPostBundle\Provider\Instagram\InstagramProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @covers \Janwebdev\SocialPostBundle\Provider\Instagram\InstagramProvider
 */
class InstagramProviderTest extends TestCase
{
    private InstagramClient $clientMock;
    private InstagramProvider $provider;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(InstagramClient::class);
        $this->provider = new InstagramProvider($this->clientMock, new NullLogger(), pollIntervalSeconds: 0);
    }

    public function testGetName(): void
    {
        $this->assertEquals('instagram', $this->provider->getName());
    }

    public function testCanPublishForInstagramNetwork(): void
    {
        $message = MessageBuilder::create()
            ->setText('Test')
            ->forNetworks(['instagram'])
            ->build();

        $this->assertTrue($this->provider->canPublish($message));
    }

    public function testPublishSuccessWithTwoStepProcess(): void
    {
        $message = MessageBuilder::create()
            ->setText('Instagram post')
            ->addImage('https://example.com/photo.jpg')
            ->build();

        // Step 1: Create container
        $this->clientMock->expects($this->once())
            ->method('createMediaContainer')
            ->willReturn(['id' => 'container_123']);

        $this->clientMock->method('getContainerStatus')
            ->willReturn(['status_code' => 'FINISHED']);

        // Step 2: Publish container
        $this->clientMock->expects($this->once())
            ->method('publishMediaContainer')
            ->with('container_123')
            ->willReturn(['id' => 'post_456']);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('instagram', $result->getProviderName());
        $this->assertEquals('post_456', $result->getPostId());
    }

    public function testPublishFailureWhenContainerCreationFails(): void
    {
        $message = MessageBuilder::create()
            ->setText('Test')
            ->addImage('https://example.com/photo.jpg')
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
        $message = MessageBuilder::create()->setText('Test')->addImage('test.jpg')->build();

        $this->clientMock->expects($this->once())
            ->method('createMediaContainer')
            ->willThrowException(new \RuntimeException('Instagram API Error'));

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('Instagram API Error', $result->getErrorMessage());
    }

    public function testCaptionTruncation(): void
    {
        $longCaption = str_repeat('a', 2250); // Longer than 2200 chars
        $message = MessageBuilder::create()
            ->setText($longCaption)
            ->addImage('test.jpg')
            ->build();

        $this->clientMock->expects($this->once())
            ->method('createMediaContainer')
            ->with($this->callback(function ($data) {
                return mb_strlen($data['caption']) <= 2200;
            }))
            ->willReturn(['id' => 'container_123']);

        $this->clientMock->method('getContainerStatus')
            ->willReturn(['status_code' => 'FINISHED']);

        $this->clientMock->method('publishMediaContainer')
            ->willReturn(['id' => 'post_456']);

        $this->provider->publish($message);
    }

    public function testPublishPollesContainerStatusBeforePublishing(): void
    {
        $message = MessageBuilder::create()
            ->setText('Test post')
            ->build();

        $this->clientMock->method('isConfigured')->willReturn(true);

        $this->clientMock->expects($this->once())
            ->method('createMediaContainer')
            ->willReturn(['id' => 'container123']);

        // Polling must be called — first returns IN_PROGRESS, then FINISHED
        $this->clientMock->expects($this->exactly(2))
            ->method('getContainerStatus')
            ->with('container123')
            ->willReturnOnConsecutiveCalls(
                ['status_code' => 'IN_PROGRESS'],
                ['status_code' => 'FINISHED'],
            );

        $this->clientMock->expects($this->once())
            ->method('publishMediaContainer')
            ->with('container123')
            ->willReturn(['id' => 'post456']);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('post456', $result->getPostId());
    }

    public function testPublishFailsOnContainerError(): void
    {
        $message = MessageBuilder::create()->setText('Test')->build();

        $this->clientMock->method('isConfigured')->willReturn(true);
        $this->clientMock->method('createMediaContainer')->willReturn(['id' => 'container123']);
        $this->clientMock->method('getContainerStatus')
            ->willReturn(['status_code' => 'ERROR']);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('failed', strtolower($result->getErrorMessage() ?? ''));
    }
}
