<?php
namespace Siru\Tests\API;

use Siru\API\FeaturePhone;
use Siru\Exception\ApiException;

class FeaturePhoneTest extends AbstractApiTest
{

    /**
     * @var FeaturePhone
     */
    private $api;

    public function setUp()
    {
        parent::setUp();
        $this->api = new FeaturePhone($this->signature, $this->transport);
    }

    /**
     * @test
     */
    public function ipIsFeaturePhone()
    {
        $ip = '1.1.1.1';
        $this->transport
            ->expects($this->once())
            ->method('request')
            ->with($this->callback(function(array $fields) use ($ip) {
                return isset($fields['ip']) &&
                    isset($fields['merchantId']) &&
                    isset($fields['signature']) &&
                    $fields['ip'] === $ip &&
                    $fields['merchantId'] === 1;
            }), '/payment/ip/feature-check')
            ->willReturn([
                200,
                '{"ipPaymentsEnabled":true}'
            ]);

        $this->assertTrue($this->api->isFeaturePhoneIP($ip));
    }

    /**
     * @test
     */
    public function ipIsNotFeaturePhone()
    {
        $ip = '1.1.1.1';
        $this->transport
            ->expects($this->once())
            ->method('request')
            ->with($this->callback(function(array $fields) use ($ip) {
                return isset($fields['ip']) &&
                    isset($fields['merchantId']) &&
                    isset($fields['signature']) &&
                    $fields['ip'] === $ip &&
                    $fields['merchantId'] === 1;
            }), '/payment/ip/feature-check')
            ->willReturn([
                200,
                '{"ipPaymentsEnabled":false}'
            ]);

        $this->assertFalse($this->api->isFeaturePhoneIP($ip));
    }

    /**
     * @test
     */
    public function authenticationFails()
    {
        $ip = '1.1.1.1';
        $this->transport
            ->expects($this->once())
            ->method('request')
            ->willThrowException(ApiException::create(403, '{"error":{"code":403,"message":"Forbidden"}}'));

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(403);
        $this->api->isFeaturePhoneIP($ip);
    }

}
