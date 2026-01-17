<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Provider\LinkedIn;

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
            ->method('createShare')
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
            ->method('createShare')
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
            ->method('createShare')
            ->willThrowException(new \RuntimeException('LinkedIn API Error'));

        $result = $this->provider->publish($message);

        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('LinkedIn API Error', $result->getErrorMessage());
    }
}
