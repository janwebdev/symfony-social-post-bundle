<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Provider\Exception;

use Janwebdev\SocialPostBundle\Provider\Exception\ProviderException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Janwebdev\SocialPostBundle\Provider\Exception\ProviderException
 */
class ProviderExceptionTest extends TestCase
{
    public function testExceptionExtendsRuntimeException(): void
    {
        $exception = new ProviderException('Test error');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testExceptionWithMessage(): void
    {
        $exception = new ProviderException('Provider error occurred');

        $this->assertEquals('Provider error occurred', $exception->getMessage());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $exception = new ProviderException('API rate limit exceeded', 429);

        $this->assertEquals('API rate limit exceeded', $exception->getMessage());
        $this->assertEquals(429, $exception->getCode());
    }

    public function testExceptionWithPreviousException(): void
    {
        $previousException = new \RuntimeException('Connection failed');
        $exception = new ProviderException('Provider error', 0, $previousException);

        $this->assertEquals('Provider error', $exception->getMessage());
        $this->assertSame($previousException, $exception->getPrevious());
    }
}
