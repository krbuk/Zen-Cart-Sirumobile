<?php
namespace Siru\Tests\API;

use Siru\API\OperationalStatus;
use Siru\Exception\ApiException;

class OperationalStatusTest extends AbstractApiTest
{

    /**
     * @var OperationalStatus
     */
    private $api;

    public function setUp()
    {
        parent::setUp();
        $this->api = new OperationalStatus($this->signature, $this->transport);
    }

    /**
     * @test
     */
    public function apiStatusIsReported()
    {
        $this->transport
            ->expects($this->once())
            ->method('request')
            ->with([], '/status', 'GET')
            ->willReturn([200, '']);

        $this->assertSame(200, $this->api->check());
    }

    /**
     * @test
     */
    public function outageIsReported()
    {
        $this->transport
            ->expects($this->once())
            ->method('request')
            ->with([], '/status', 'GET')
            ->willThrowException(ApiException::create(503, ''));

        $this->assertSame(503, $this->api->check());
    }

}
