<?php
namespace Siru\Tests\API;

use Siru\API\PurchaseStatus;
use Siru\Exception\ApiException;

class PurchaseStatusTest extends AbstractApiTest
{

    /**
     * @var PurchaseStatus
     */
    private $api;

    public function setUp()
    {
        parent::setUp();
        $this->api = new PurchaseStatus($this->signature, $this->transport);
    }

    /**
     * @test
     */
    public function findsPurchaseByUuid()
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
            }), '/payment/byUuid.json', 'GET')
            ->willReturn([
                200,
                json_encode($this->getPurchaseJson())
            ]);

        $purchase = $this->api->findPurchaseByUuid($uuid);

        $this->assertEquals($this->getPurchaseJson(), $purchase);
    }

    /**
     * @test
     */
    public function purchaseIsNotFoundByUuid()
    {
        $uuid = '09b755cb-9697-4c8b-8ebb-9d54170739be';
        $this->transport
            ->expects($this->once())
            ->method('request')
            ->willThrowException(ApiException::create(404, '{"error":{"code":404,"message":"Not Found"}}'));

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(404);
        $this->api->findPurchaseByUuid($uuid);
    }

    /**
     * @test
     */
    public function findsPurchasesByReference()
    {
        $reference = '42';
        $this->transport
            ->expects($this->once())
            ->method('request')
            ->with($this->callback(function(array $fields) use ($reference) {
                return isset($fields['purchaseReference']) &&
                    isset($fields['merchantId']) &&
                    isset($fields['signature']) &&
                    $fields['purchaseReference'] === $reference &&
                    $fields['merchantId'] === 1;
            }), '/payment/byPurchaseReference.json')
            ->willReturn([
                200,
                json_encode(['purchases' => [$this->getPurchaseJson(), $this->getPurchaseJson()]])
            ]);

        $purchases = $this->api->findPurchasesByReference($reference);

        $this->assertEquals([$this->getPurchaseJson(), $this->getPurchaseJson()], $purchases);
    }

    /**
     * @test
     */
    public function findsPurchasesByDateRange()
    {
        $from = new \DateTime('2020-02-01 02:00:00+02:00');
        $to = new \DateTime('2020-02-08 02:00:00+02:00');
        $this->transport
            ->expects($this->once())
            ->method('request')
            ->with($this->callback(function(array $fields) {
                return isset($fields['from']) &&
                    isset($fields['to']) &&
                    isset($fields['merchantId']) &&
                    isset($fields['signature']) &&
                    $fields['from'] === '2020-02-01 00:00:00' &&
                    $fields['to'] === '2020-02-08 00:00:00' &&
                    $fields['merchantId'] === 1;
            }), '/payment/byDate.json')
            ->willReturn([
                200,
                json_encode(['purchases' => [$this->getPurchaseJson(), $this->getPurchaseJson()]])
            ]);

        $purchases = $this->api->findPurchasesByDateRange($from, $to);

        $this->assertEquals([$this->getPurchaseJson(), $this->getPurchaseJson()], $purchases);
    }

    /**
     * @test
     */
    public function findsNoPurchasesByReferenceAndSubmerchant()
    {
        $reference = '42';
        $subMerchant = 'xooxer';
        $this->transport
            ->expects($this->once())
            ->method('request')
            ->with($this->callback(function(array $fields) use ($reference, $subMerchant) {
                return isset($fields['purchaseReference']) &&
                    isset($fields['submerchantReference']) &&
                    isset($fields['merchantId']) &&
                    isset($fields['signature']) &&
                    $fields['purchaseReference'] === $reference &&
                    $fields['submerchantReference'] === $subMerchant &&
                    $fields['merchantId'] === 1;
            }), '/payment/byPurchaseReference.json')
            ->willReturn([
                200,
                '{"purchases":[]}'
            ]);

        $purchases = $this->api->findPurchasesByReference($reference, $subMerchant);

        $this->assertEquals([], $purchases);
    }

    /**
     * @test
     */
    public function cancelsPurchaseByUuid()
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
            }), '/payment/byUuid.json', 'DELETE')
            ->willReturn([
                204,
                ''
            ]);

        $this->api->cancelPurchaseByUuid($uuid);
    }

    private function getPurchaseJson() : array
    {
        return [
            "id" => 1234567,
            "uuid" => "f599cd21-47a2-4bbc-b44b-2cc4b1bb25f1",
            "merchantId" => 18,
            "submerchantReference" => null,
            "customerReference" => null,
            "purchaseReference" => "testshop1420070765",
            "customerNumber" => "XXXXXXX",
            "basePrice" => "5.00",
            "finalPrice" => "8.51",
            "currency" => "EUR",
            "status" => "canceled",
            "createdAt" => "2015-01-01T00:06:05+0000",
            "startedAt" => null,
            "finishedAt" => "2015-01-01T00:17:09+0000"
        ];
    }

}
