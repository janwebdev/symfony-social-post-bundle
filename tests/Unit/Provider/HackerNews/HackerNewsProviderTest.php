<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Provider\HackerNews;

use Janwebdev\SocialPostBundle\Message\MessageBuilder;
use Janwebdev\SocialPostBundle\Provider\HackerNews\HackerNewsClient;
use Janwebdev\SocialPostBundle\Provider\HackerNews\HackerNewsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @covers \Janwebdev\SocialPostBundle\Provider\HackerNews\HackerNewsProvider
 */
class HackerNewsProviderTest extends TestCase
{
    private HackerNewsClient&MockObject $clientMock;
    private HackerNewsProvider $provider;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(HackerNewsClient::class);
        $this->provider = new HackerNewsProvider($this->clientMock, new NullLogger());
    }

    public function testGetName(): void
    {
        $this->assertSame('hackernews', HackerNewsProvider::getName());
    }

    public function testCanPublishReturnsFalseWithoutLink(): void
    {
        $message = MessageBuilder::create()
            ->setText('Just text, no link')
            ->forNetworks(['hackernews'])
            ->build();

        $this->assertFalse($this->provider->canPublish($message));
    }

    public function testCanPublishReturnsTrueWithLink(): void
    {
        $message = MessageBuilder::create()
            ->setText('Title')
            ->setLink('https://example.com')
            ->forNetworks(['hackernews'])
            ->build();

        $this->assertTrue($this->provider->canPublish($message));
    }

    public function testCanPublishReturnsFalseForOtherNetwork(): void
    {
        $message = MessageBuilder::create()
            ->setText('Title')
            ->setLink('https://example.com')
            ->forNetworks(['twitter'])
            ->build();

        $this->assertFalse($this->provider->canPublish($message));
    }

    public function testPublishSuccess(): void
    {
        $message = MessageBuilder::create()
            ->setText('My Post Title')
            ->setLink('https://example.com/article')
            ->build();

        $this->clientMock
            ->expects($this->once())
            ->method('submitPost')
            ->with('My Post Title', 'https://example.com/article')
            ->willReturn('https://news.ycombinator.com/newest');

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('https://news.ycombinator.com/newest', $result->getPostUrl());
        $this->assertSame('hackernews', $result->getProviderName());
    }

    public function testPublishFailsWithoutLink(): void
    {
        $message = MessageBuilder::create()
            ->setText('No link here')
            ->build();

        $this->clientMock->expects($this->never())->method('submitPost');

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('URL', $result->getErrorMessage() ?? '');
    }

    public function testPublishHandlesClientException(): void
    {
        $message = MessageBuilder::create()
            ->setText('Title')
            ->setLink('https://example.com')
            ->build();

        $this->clientMock
            ->method('submitPost')
            ->willThrowException(new \RuntimeException('HackerNews login failed'));

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('login failed', $result->getErrorMessage() ?? '');
    }

    public function testIsConfiguredReturnsTrueWhenClientConfigured(): void
    {
        $this->clientMock->method('isConfigured')->willReturn(true);
        $this->assertTrue($this->provider->isConfigured());
    }

    public function testIsConfiguredReturnsFalseWhenClientNotConfigured(): void
    {
        $client = $this->createMock(HackerNewsClient::class);
        $client->method('isConfigured')->willReturn(false);
        $provider = new HackerNewsProvider($client, new NullLogger());
        $this->assertFalse($provider->isConfigured());
    }
}
