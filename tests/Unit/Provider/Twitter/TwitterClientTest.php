<?php
declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Provider\Twitter;

use Janwebdev\SocialPostBundle\Http\ClientInterface;
use Janwebdev\SocialPostBundle\Http\Response;
use Janwebdev\SocialPostBundle\Provider\Twitter\TwitterClient;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Janwebdev\SocialPostBundle\Provider\Twitter\TwitterClient
 */
class TwitterClientTest extends TestCase
{
    private ClientInterface $httpClientMock;
    private TwitterClient $client;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(ClientInterface::class);
        $this->client = new TwitterClient(
            $this->httpClientMock,
            'api_key',
            'api_secret',
            'access_token',
            'access_token_secret',
        );
    }

    public function testIsConfiguredWithAllCredentials(): void
    {
        $this->assertTrue($this->client->isConfigured());
    }

    public function testIsConfiguredWithMissingCredentials(): void
    {
        $client = new TwitterClient($this->httpClientMock, '', '', '', '');
        $this->assertFalse($client->isConfigured());
    }

    public function testCreateTweetCallsV2Endpoint(): void
    {
        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with($this->stringContains('api.twitter.com/2/tweets'))
            ->willReturn(new Response(201, '{"data":{"id":"123"}}'));

        $result = $this->client->createTweet(['text' => 'Hello']);

        $this->assertEquals(['data' => ['id' => '123']], $result);
    }

    public function testUploadMediaCallsV2Endpoint(): void
    {
        // Create a temp file to simulate local attachment
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile, 'fake image data');

        $attachment = $this->createMock(\Janwebdev\SocialPostBundle\Message\Attachment\AttachmentInterface::class);
        $attachment->method('getType')->willReturn('image');
        $attachment->method('isLocal')->willReturn(true);
        $attachment->method('getPath')->willReturn($tmpFile);

        $this->httpClientMock
            ->expects($this->once())
            ->method('postMultipart')
            ->with($this->stringContains('api.twitter.com/2/media/upload'))
            ->willReturn(new Response(200, '{"media_id_string":"media123"}'));

        $mediaIds = $this->client->uploadMedia([$attachment]);

        $this->assertEquals(['media123'], $mediaIds);

        unlink($tmpFile);
    }

    public function testUploadMediaSkipsNonImageAttachments(): void
    {
        $attachment = $this->createMock(\Janwebdev\SocialPostBundle\Message\Attachment\AttachmentInterface::class);
        $attachment->method('getType')->willReturn('video');

        $this->httpClientMock->expects($this->never())->method('postMultipart');

        $result = $this->client->uploadMedia([$attachment]);
        $this->assertEquals([], $result);
    }

    public function testUploadMediaPropagatesExceptionOnFailure(): void
    {
        $attachment = $this->createMock(\Janwebdev\SocialPostBundle\Message\Attachment\AttachmentInterface::class);
        $attachment->method('getType')->willReturn('image');
        $attachment->method('isLocal')->willReturn(true);
        $attachment->method('getPath')->willReturn('/nonexistent/path/image.jpg');

        $this->expectException(\Janwebdev\SocialPostBundle\Provider\Exception\ProviderException::class);
        $this->expectExceptionMessageMatches('/File not found/');

        $this->client->uploadMedia([$attachment]);
    }
}
