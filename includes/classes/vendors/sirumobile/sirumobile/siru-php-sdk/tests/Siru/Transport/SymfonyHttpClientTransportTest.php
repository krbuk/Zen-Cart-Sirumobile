<?php

namespace Siru\Tests\Transport;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Siru\Transport\SymfonyHttpClientTransport;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SymfonyHttpClientTransportTest extends TestCase
{

    public function setUp()
    {
        if (class_exists('\Symfony\Component\HttpClient\HttpClient') === false) {
            $this->markTestSkipped('SymfonyHttpClientTransportTest requires symfony/http-client');
        }
    }

    /**
     * @test
     */
    public function createsNewClient()
    {
        $transport = new SymfonyHttpClientTransport();
        $client = $transport->getHttpClient();
        $this->assertInstanceOf(HttpClientInterface::class, $client);
    }

    /**
     * @test
     */
    public function usesPreviouslySetClient()
    {
        /** @var HttpClientInterface|MockObject $expected */
        $expected = $this->createMock(HttpClientInterface::class);
        $transport = new SymfonyHttpClientTransport();
        $transport->setHttpClient($expected);
        $client = $transport->getHttpClient();
        $this->assertSame($expected, $client);
    }

    /**
     * @test
     */
    public function sendsGetRequest()
    {
        $requestCount = 0;
        $mock = new MockHttpClient(function($method, $url, $options) use (&$requestCount) {
            $this->assertEquals('GET', $method);
            $this->assertEquals('https://localhost/test?foo=bar', $url);
            $requestCount++;
            return new MockResponse('xooxer');
        });

        $transport = new SymfonyHttpClientTransport();
        $transport->setHttpClient($mock);
        $transport->setBaseUrl('https://localhost');

        $data = $transport->request(['foo' => 'bar'], '/test', 'GET');
        $this->assertEquals(1, $requestCount, 'No request was sent.');
        $this->assertEquals(200, $data[0]);
        $this->assertEquals('xooxer', $data[1]);
    }

    /**
     * @test
     */
    public function sendsDeleteRequest()
    {
        $requestCount = 0;
        $mock = new MockHttpClient(function($method, $url, $options) use (&$requestCount) {
            $this->assertEquals('DELETE', $method);
            $this->assertEquals('https://localhost/test?foo=bar', $url);
            $requestCount++;
            return new MockResponse('', ['http_code' => 204]);
        });

        $transport = new SymfonyHttpClientTransport();
        $transport->setHttpClient($mock);
        $transport->setBaseUrl('https://localhost');

        $data = $transport->request(['foo' => 'bar'], '/test', 'DELETE');
        $this->assertEquals(1, $requestCount, 'No request was sent.');
        $this->assertEquals(204, $data[0]);
    }

    /**
     * @test
     */
    public function sendsPostRequest()
    {
        $requestCount = 0;
        $mock = new MockHttpClient(function($method, $url, $options) use (&$requestCount) {
            $this->assertEquals('POST', $method);
            $this->assertEquals('https://localhost/test', $url);
            $this->assertEquals('{"foo":"bar"}', $options['body']);
            $requestCount++;
            return new MockResponse('{"success":true}', ['http_code' => 201]);
        });

        $transport = new SymfonyHttpClientTransport();
        $transport->setHttpClient($mock);
        $transport->setBaseUrl('https://localhost');

        $data = $transport->request(['foo' => 'bar'], '/test', 'POST');
        $this->assertEquals(1, $requestCount, 'No request was sent.');
        $this->assertEquals(201, $data[0]);
        $this->assertEquals('{"success":true}', $data[1]);
    }

}