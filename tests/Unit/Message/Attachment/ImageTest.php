<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Message\Attachment;

use Janwebdev\SocialPostBundle\Message\Attachment\AttachmentInterface;
use Janwebdev\SocialPostBundle\Message\Attachment\Image;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Janwebdev\SocialPostBundle\Message\Attachment\Image
 */
class ImageTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $image = new Image(
            path: '/path/to/image.jpg',
            altText: 'Alt text description'
        );

        $this->assertEquals('/path/to/image.jpg', $image->getPath());
        $this->assertEquals('Alt text description', $image->getAltText());
    }

    public function testConstructorWithoutAltText(): void
    {
        $image = new Image(path: '/path/to/image.jpg');

        $this->assertEquals('/path/to/image.jpg', $image->getPath());
        $this->assertNull($image->getAltText());
    }

    public function testImplementsAttachmentInterface(): void
    {
        $image = new Image(path: '/path/to/image.jpg');

        $this->assertInstanceOf(AttachmentInterface::class, $image);
    }

    public function testWithUrl(): void
    {
        $image = new Image(path: 'https://example.com/image.jpg', altText: 'Remote image');

        $this->assertEquals('https://example.com/image.jpg', $image->getPath());
        $this->assertEquals('Remote image', $image->getAltText());
    }

    public function testWithLocalPath(): void
    {
        $image = new Image(path: '/var/www/uploads/photo.png');

        $this->assertEquals('/var/www/uploads/photo.png', $image->getPath());
    }
}
