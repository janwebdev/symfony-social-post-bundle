<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Publisher\Result;

use Janwebdev\SocialPostBundle\Provider\Result\PublishResult;
use Janwebdev\SocialPostBundle\Publisher\Result\PublishResultCollection;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Janwebdev\SocialPostBundle\Publisher\Result\PublishResultCollection
 */
class PublishResultCollectionTest extends TestCase
{
    public function testGetResult(): void
    {
        $twitterResult = PublishResult::success('twitter', '123');
        $facebookResult = PublishResult::success('facebook', '456');
        
        $collection = new PublishResultCollection([
            'twitter' => $twitterResult,
            'facebook' => $facebookResult,
        ]);

        $this->assertSame($twitterResult, $collection->getResult('twitter'));
        $this->assertSame($facebookResult, $collection->getResult('facebook'));
        $this->assertNull($collection->getResult('linkedin'));
    }

    public function testGetAll(): void
    {
        $results = [
            'twitter' => PublishResult::success('twitter', '123'),
            'facebook' => PublishResult::success('facebook', '456'),
        ];
        
        $collection = new PublishResultCollection($results);

        $this->assertEquals($results, $collection->getAll());
    }

    public function testGetSuccessful(): void
    {
        $successResult = PublishResult::success('twitter', '123');
        $failureResult = PublishResult::failure('facebook', 'Error');
        
        $collection = new PublishResultCollection([
            'twitter' => $successResult,
            'facebook' => $failureResult,
        ]);

        $successful = $collection->getSuccessful();
        $this->assertCount(1, $successful);
        $this->assertArrayHasKey('twitter', $successful);
        $this->assertArrayNotHasKey('facebook', $successful);
    }

    public function testGetFailed(): void
    {
        $successResult = PublishResult::success('twitter', '123');
        $failureResult = PublishResult::failure('facebook', 'Error');
        
        $collection = new PublishResultCollection([
            'twitter' => $successResult,
            'facebook' => $failureResult,
        ]);

        $failed = $collection->getFailed();
        $this->assertCount(1, $failed);
        $this->assertArrayHasKey('facebook', $failed);
        $this->assertArrayNotHasKey('twitter', $failed);
    }

    public function testIsAllSuccessful(): void
    {
        $allSuccess = new PublishResultCollection([
            'twitter' => PublishResult::success('twitter', '123'),
            'facebook' => PublishResult::success('facebook', '456'),
        ]);

        $this->assertTrue($allSuccess->isAllSuccessful());

        $partialSuccess = new PublishResultCollection([
            'twitter' => PublishResult::success('twitter', '123'),
            'facebook' => PublishResult::failure('facebook', 'Error'),
        ]);

        $this->assertFalse($partialSuccess->isAllSuccessful());

        $empty = new PublishResultCollection([]);
        $this->assertFalse($empty->isAllSuccessful());
    }

    public function testHasAnySuccessful(): void
    {
        $hasSuccess = new PublishResultCollection([
            'twitter' => PublishResult::success('twitter', '123'),
            'facebook' => PublishResult::failure('facebook', 'Error'),
        ]);

        $this->assertTrue($hasSuccess->hasAnySuccessful());

        $allFailed = new PublishResultCollection([
            'twitter' => PublishResult::failure('twitter', 'Error1'),
            'facebook' => PublishResult::failure('facebook', 'Error2'),
        ]);

        $this->assertFalse($allFailed->hasAnySuccessful());
    }

    public function testCount(): void
    {
        $collection = new PublishResultCollection([
            'twitter' => PublishResult::success('twitter', '123'),
            'facebook' => PublishResult::success('facebook', '456'),
            'linkedin' => PublishResult::failure('linkedin', 'Error'),
        ]);

        $this->assertEquals(3, $collection->count());

        $empty = new PublishResultCollection([]);
        $this->assertEquals(0, $empty->count());
    }

    public function testIterator(): void
    {
        $results = [
            'twitter' => PublishResult::success('twitter', '123'),
            'facebook' => PublishResult::success('facebook', '456'),
        ];
        
        $collection = new PublishResultCollection($results);

        $count = 0;
        foreach ($collection as $network => $result) {
            $this->assertContains($network, ['twitter', 'facebook']);
            $this->assertInstanceOf(PublishResult::class, $result);
            $count++;
        }

        $this->assertEquals(2, $count);
    }
}
