<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\Http;

use Janwebdev\SocialPostBundle\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Janwebdev\SocialPostBundle\Http\Response
 */
class ResponseTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $response = new Response(
            statusCode: 200,
            body: '{"success": true}',
            headers: $headers
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"success": true}', $response->getBody());
        $this->assertEquals($headers, $response->getHeaders());
    }

    public function testConstructorWithoutHeaders(): void
    {
        $response = new Response(
            statusCode: 404,
            body: 'Not Found'
        );

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Not Found', $response->getBody());
        $this->assertEquals([], $response->getHeaders());
    }

    public function testIsSuccessfulWithSuccessStatusCodes(): void
    {
        $response200 = new Response(statusCode: 200, body: 'OK');
        $this->assertTrue($response200->isSuccessful());

        $response201 = new Response(statusCode: 201, body: 'Created');
        $this->assertTrue($response201->isSuccessful());

        $response204 = new Response(statusCode: 204, body: '');
        $this->assertTrue($response204->isSuccessful());

        $response299 = new Response(statusCode: 299, body: 'Success');
        $this->assertTrue($response299->isSuccessful());
    }

    public function testIsSuccessfulWithErrorStatusCodes(): void
    {
        $response400 = new Response(statusCode: 400, body: 'Bad Request');
        $this->assertFalse($response400->isSuccessful());

        $response404 = new Response(statusCode: 404, body: 'Not Found');
        $this->assertFalse($response404->isSuccessful());

        $response500 = new Response(statusCode: 500, body: 'Internal Server Error');
        $this->assertFalse($response500->isSuccessful());

        $response199 = new Response(statusCode: 199, body: 'Info');
        $this->assertFalse($response199->isSuccessful());

        $response300 = new Response(statusCode: 300, body: 'Redirect');
        $this->assertFalse($response300->isSuccessful());
    }

    public function testToArrayWithValidJson(): void
    {
        $jsonBody = '{"id": 123, "name": "Test", "active": true}';
        $response = new Response(statusCode: 200, body: $jsonBody);

        $expected = [
            'id' => 123,
            'name' => 'Test',
            'active' => true,
        ];

        $this->assertEquals($expected, $response->toArray());
    }

    public function testToArrayWithInvalidJson(): void
    {
        $response = new Response(statusCode: 200, body: 'Not a JSON');

        $this->assertEquals([], $response->toArray());
    }

    public function testToArrayWithEmptyBody(): void
    {
        $response = new Response(statusCode: 204, body: '');

        $this->assertEquals([], $response->toArray());
    }

    public function testToArrayWithNestedJson(): void
    {
        $jsonBody = '{"user": {"id": 1, "name": "John"}, "posts": [{"id": 1}, {"id": 2}]}';
        $response = new Response(statusCode: 200, body: $jsonBody);

        $expected = [
            'user' => ['id' => 1, 'name' => 'John'],
            'posts' => [['id' => 1], ['id' => 2]],
        ];

        $this->assertEquals($expected, $response->toArray());
    }

    public function testToArrayWithJsonArray(): void
    {
        $jsonBody = '[{"id": 1}, {"id": 2}, {"id": 3}]';
        $response = new Response(statusCode: 200, body: $jsonBody);

        $expected = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ];

        $this->assertEquals($expected, $response->toArray());
    }
}
