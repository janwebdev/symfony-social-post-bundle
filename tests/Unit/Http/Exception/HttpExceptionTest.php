<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Http\Exception;

use Janwebdev\SocialPostBundle\Http\Exception\HttpException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Janwebdev\SocialPostBundle\Http\Exception\HttpException
 */
class HttpExceptionTest extends TestCase
{
    public function testExceptionExtendsRuntimeException(): void
    {
        $exception = new HttpException('Test error');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testExceptionWithMessage(): void
    {
        $exception = new HttpException('HTTP request failed');

        $this->assertEquals('HTTP request failed', $exception->getMessage());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $exception = new HttpException('Not Found', 404);

        $this->assertEquals('Not Found', $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }

    public function testExceptionWithPreviousException(): void
    {
        $previousException = new \RuntimeException('cURL error');
        $exception = new HttpException('Connection error', 0, $previousException);

        $this->assertEquals('Connection error', $exception->getMessage());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testExceptionWithDifferentHttpCodes(): void
    {
        $exception400 = new HttpException('Bad Request', 400);
        $this->assertEquals(400, $exception400->getCode());

        $exception500 = new HttpException('Internal Server Error', 500);
        $this->assertEquals(500, $exception500->getCode());

        $exception503 = new HttpException('Service Unavailable', 503);
        $this->assertEquals(503, $exception503->getCode());
    }
}
