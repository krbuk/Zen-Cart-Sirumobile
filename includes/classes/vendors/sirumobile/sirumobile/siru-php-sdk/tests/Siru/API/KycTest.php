<?php
namespace Siru\Tests\API;

use Siru\API\Kyc;
use Siru\Exception\ApiException;

class KycTest extends AbstractApiTest
{

    /**
     * @var Kyc
     */
    private $api;

    public function setUp()
    {
        parent::setUp();
        $this->api = new Kyc($this->signature, $this->transport);
    }

    /**
     * @test
     */
    public function kycDataIsFound()
    {
        $uuid = '09b755cb-9697-4c8b-8ebb-9d54170739be';
        $this->transport
            ->expects($this->once())
            ->method('request')
            ->with($this->callback(function(array $fields) use ($uuid) {
                return isset($fields['uuid']) &&
                    isset($fields['merchantId']) &&
                    isset($fields['signature']) &&
                    $fields['uuid'] === $uuid &&
                    $fields['merchantId'] === 1;
            }), '/payment/kyc')
            ->willReturn([
                200,
                '{"report":{"firstName":"Foo","lastName":"Bar"}}'
            ]);

        $report = $this->api->findKycByUuid($uuid);
        $this->assertEquals([
            'report' => [
                'firstName' => 'Foo',
                'lastName' => 'Bar'
            ]
        ], $report);
    }

    /**
     * @test
     */
    public function kycDataIsNotFound()
    {
        $uuid = '09b755cb-9697-4c8b-8ebb-9d54170739be';
        $this->transport
            ->expects($this->once())
            ->method('request')
            ->willThrowException(ApiException::create(404, '{"error":{"code":404,"message":"Not Found"}}'));

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(404);
        $this->api->findKycByUuid($uuid);
    }
}
