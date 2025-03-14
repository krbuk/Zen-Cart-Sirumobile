<?php
namespace Siru\Tests\API;

use Siru\API\Price;
use Siru\Exception\ApiException;

class PriceTest extends AbstractApiTest
{

    /**
     * @var Price
     */
    private $api;

    public function setUp()
    {
        parent::setUp();
        $this->api = new Price($this->signature, $this->transport);
    }

    /**
     * @test
     * @dataProvider pricesProvider
     * @param array $expectedQuery
     * @param string $finalPrice
     * @param string $purchaseCountry
     * @param string $basePrice
     * @param string|null $submerchantReference
     * @param int|null $taxClass
     * @param string $variant
     * @param int|null $merchantId
     * @throws ApiException
     */
    public function priceIsCalculated(array $expectedQuery, string $finalPrice, string $purchaseCountry, string $basePrice, ?string $submerchantReference = null, $taxClass = null, string $variant = 'variant1', $merchantId = null)
    {
        $this->transport
            ->expects($this->once())
            ->method('request')
            ->with($this->callback(function(array $fields) use ($expectedQuery) {
                return $fields == $expectedQuery;
            }), '/payment/price.json', 'GET')
            ->willReturn([
                200,
                '{"finalCallPrice":"' . $finalPrice . '"}'
            ]);

        $result = $this->api->calculatePrice($purchaseCountry, $basePrice, $submerchantReference, $taxClass, $variant, $merchantId);
        $this->assertEquals($finalPrice, $result);
    }

    /**
     * @test
     */
    public function httpErrorIsHandled()
    {
        $this->transport
            ->expects($this->once())
            ->method('request')
            ->willThrowException(ApiException::create(503, '{"error":"Sailing the failboat"}'));

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(503);
        $this->api->calculatePrice('FI', '5.00');
    }

    public function pricesProvider() : array
    {
        // string $purchaseCountry, string $basePrice, ?string $submerchantReference = null, $taxClass = null, string $variant = 'variant1', $merchantId = null
        return [
            [['purchaseCountry' => 'FI', 'basePrice' => '5.00', 'variant' => 'variant1', 'merchantId' => 1], '8.51', 'FI', '5.00', null, null, 'variant1', null],
            [['purchaseCountry' => 'FI', 'basePrice' => '5.00', 'variant' => 'variant1', 'merchantId' => 22, 'submerchantReference' => 'ahem', 'taxClass' => 2], '8.51', 'FI', '5.00', 'ahem', 2, 'variant1', 22],
        ];
    }

}
