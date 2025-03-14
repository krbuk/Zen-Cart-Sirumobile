<?php
namespace Siru\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Siru\Exception\ApiException;

class ApiExceptionTest extends TestCase
{

    /**
     * @test
     */
    public function constructsException()
    {
        $body = 'response text';
        $exception = new ApiException('error', 123,null, $body);
        $this->assertEquals($body, $exception->getResponseBody());

        $previous = new \Exception('previous');
        $exception = new ApiException('error', 123, $previous);
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertEmpty($exception->getResponseBody());
    }

    /**
     * @test
     * @dataProvider factoryDataProvider
     * @param int $httpCode
     * @param string $body
     * @param string $expectedMessage
     * @param array $expectedStack
     */
    public function factoryCreatesException(int $httpCode, string $body, string $expectedMessage, array $expectedStack = [])
    {
        $exception = ApiException::create($httpCode, $body);
        $this->assertEquals($httpCode, $exception->getCode());
        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals($expectedStack, $exception->getErrorStack());

    }

    public function factoryDataProvider() : array
    {
        return [
            [404, '{"error":{"code":404,"message":"Not Found"}}', 'Not Found', []],
            [400, '{"error":"old style error"}', 'old style error', []],
            [400, '{"success":false,"errors":["error","and another"]}', 'API request failed.', ['error', 'and another']],
        ];
    }

}
