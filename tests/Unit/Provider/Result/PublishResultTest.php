<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Provider\Result;

use Janwebdev\SocialPostBundle\Provider\Result\PublishResult;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Janwebdev\SocialPostBundle\Provider\Result\PublishResult
 */
class PublishResultTest extends TestCase
{
    public function testSuccessResult(): void
    {
        $result = PublishResult::success(
            providerName: 'twitter',
            postId: '123456',
            postUrl: 'https://twitter.com/i/web/status/123456'
        );

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailure());
        $this->assertEquals('twitter', $result->getProviderName());
        $this->assertEquals('123456', $result->getPostId());
        $this->assertEquals('https://twitter.com/i/web/status/123456', $result->getPostUrl());
        $this->assertNull($result->getErrorMessage());
        $this->assertNull($result->getException());
    }

    public function testSuccessResultWithMetadata(): void
    {
        $metadata = ['raw_response' => ['id' => '123']];
        $result = PublishResult::success(
            providerName: 'facebook',
            postId: '123',
            postUrl: 'https://facebook.com/123',
            metadata: $metadata
        );

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($metadata, $result->getMetadata());
    }

    public function testFailureResult(): void
    {
        $result = PublishResult::failure(
            providerName: 'twitter',
            errorMessage: 'API rate limit exceeded'
        );

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFailure());
        $this->assertEquals('twitter', $result->getProviderName());
        $this->assertNull($result->getPostId());
        $this->assertNull($result->getPostUrl());
        $this->assertEquals('API rate limit exceeded', $result->getErrorMessage());
    }

    public function testFailureResultWithException(): void
    {
        $exception = new \RuntimeException('Connection failed');
        $result = PublishResult::failure(
            providerName: 'facebook',
            errorMessage: 'Connection error',
            exception: $exception
        );

        $this->assertTrue($result->isFailure());
        $this->assertEquals('Connection error', $result->getErrorMessage());
        $this->assertSame($exception, $result->getException());
    }

    public function testFailureResultWithMetadata(): void
    {
        $metadata = ['error_code' => 429, 'retry_after' => 3600];
        $result = PublishResult::failure(
            providerName: 'linkedin',
            errorMessage: 'Rate limited',
            metadata: $metadata
        );

        $this->assertEquals($metadata, $result->getMetadata());
    }
}
