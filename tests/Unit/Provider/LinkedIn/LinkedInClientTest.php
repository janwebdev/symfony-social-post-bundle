<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Provider\LinkedIn;

use Janwebdev\SocialPostBundle\Http\ClientInterface;
use Janwebdev\SocialPostBundle\Http\Response;
use Janwebdev\SocialPostBundle\Message\Attachment\Image;
use Janwebdev\SocialPostBundle\Provider\Exception\ProviderException;
use Janwebdev\SocialPostBundle\Provider\LinkedIn\LinkedInClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Janwebdev\SocialPostBundle\Provider\LinkedIn\LinkedInClient
 */
class LinkedInClientTest extends TestCase
{
    private ClientInterface&MockObject $httpClientMock;
    private LinkedInClient $client;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(ClientInterface::class);
        $this->client = new LinkedInClient(
            $this->httpClientMock,
            'org123',
            'token_abc',
        );
    }

    public function testUploadImageSuccessLocalFile(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'li_test_');
        assert(is_string($tmpFile));
        file_put_contents($tmpFile, 'fake-binary-content');

        try {
            $attachment = new Image($tmpFile, 'Alt text');

            $initResponseBody = json_encode([
                'value' => [
                    'uploadUrl' => 'https://linkedin.com/dms-uploads/test',
                    'image' => 'urn:li:image:ABC123',
                ],
            ]);
            assert(is_string($initResponseBody));

            $initResponse = new Response(200, $initResponseBody);
            $putResponse = new Response(201, '');

            $this->httpClientMock->expects($this->once())
                ->method('post')
                ->with(
                    $this->stringContains('/rest/images?action=initializeUpload'),
                    $this->anything(),
                    $this->anything(),
                )
                ->willReturn($initResponse);

            $this->httpClientMock->expects($this->once())
                ->method('put')
                ->with(
                    'https://linkedin.com/dms-uploads/test',
                    $this->anything(),
                    'fake-binary-content',
                )
                ->willReturn($putResponse);

            $result = $this->client->uploadImage($attachment);

            $this->assertSame('urn:li:image:ABC123', $result);
        } finally {
            unlink($tmpFile);
        }
    }

    public function testUploadImageThrowsOnLocalFileNotFound(): void
    {
        $attachment = new Image('/nonexistent/path/image.jpg');

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessageMatches('/File not found/');

        $this->client->uploadImage($attachment);
    }

    public function testUploadImageFromUrl(): void
    {
        $attachment = new Image('https://example.com/photo.jpg', 'Photo');

        $downloadResponse = new Response(200, 'url-image-binary-data');

        $initResponseBody = json_encode([
            'value' => [
                'uploadUrl' => 'https://linkedin.com/dms-uploads/url-test',
                'image' => 'urn:li:image:URL456',
            ],
        ]);
        assert(is_string($initResponseBody));

        $initResponse = new Response(200, $initResponseBody);
        $putResponse = new Response(201, '');

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('https://example.com/photo.jpg')
            ->willReturn($downloadResponse);

        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->willReturn($initResponse);

        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with(
                'https://linkedin.com/dms-uploads/url-test',
                $this->anything(),
                'url-image-binary-data',
            )
            ->willReturn($putResponse);

        $result = $this->client->uploadImage($attachment);

        $this->assertSame('urn:li:image:URL456', $result);
    }
}
