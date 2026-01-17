<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Provider\Facebook;

use Janwebdev\SocialPostBundle\Message\MessageBuilder;
use Janwebdev\SocialPostBundle\Provider\Facebook\FacebookClient;
use Janwebdev\SocialPostBundle\Provider\Facebook\FacebookProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @covers \Janwebdev\SocialPostBundle\Provider\Facebook\FacebookProvider
 */
class FacebookProviderTest extends TestCase
{
    private FacebookClient $clientMock;
    private FacebookProvider $provider;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(FacebookClient::class);
        $this->provider = new FacebookProvider($this->clientMock, new NullLogger());
    }

    public function testGetName(): void
    {
        $this->assertEquals('facebook', $this->provider->getName());
    }

    public function testCanPublishForFacebookNetwork(): void
    {
        $message = MessageBuilder::create()
            ->setText('Test')
            ->forNetworks(['facebook'])
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

    public function testPublishSuccess(): void
    {
        $message = MessageBuilder::create()
            ->setText('Hello Facebook!')
            ->setLink('https://example.com')
            ->build();

        $this->clientMock->expects($this->once())
            ->method('createPost')
            ->with($this->callback(function ($data) {
                return $data['message'] === 'Hello Facebook!' 
                    && $data['link'] === 'https://example.com';
            }))
            ->willReturn(['id' => '123_456']);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('facebook', $result->getProviderName());
        $this->assertEquals('123_456', $result->getPostId());
        $this->assertStringContainsString('123_456', $result->getPostUrl());
    }

    public function testPublishWithImage(): void
    {
        $message = MessageBuilder::create()
            ->setText('Post with photo')
            ->addImage('https://example.com/image.jpg')
            ->build();

        $this->clientMock->expects($this->once())
            ->method('createPost')
            ->with($this->callback(function ($data) {
                return isset($data['url']) && $data['url'] === 'https://example.com/image.jpg';
            }))
            ->willReturn(['id' => '789']);

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
    }

    public function testPublishFailure(): void
    {
        $message = MessageBuilder::create()->setText('Test')->build();

        $this->clientMock->expects($this->once())
            ->method('createPost')
            ->willThrowException(new \RuntimeException('Facebook API Error'));

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isFailure());
        $this->assertEquals('facebook', $result->getProviderName());
        $this->assertStringContainsString('Facebook API Error', $result->getErrorMessage());
        $this->assertInstanceOf(\RuntimeException::class, $result->getException());
    }
}
