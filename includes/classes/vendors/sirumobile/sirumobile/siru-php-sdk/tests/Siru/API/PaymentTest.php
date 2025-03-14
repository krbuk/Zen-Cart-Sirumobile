<?php
namespace Siru\Tests\API;

use Siru\API\Payment;

class PaymentTest extends AbstractApiTest
{

    /**
     * @var Payment
     */
    private $api;

    public function setUp()
    {
        parent::setUp();
        $this->api = new Payment($this->signature, $this->transport);
    }

    /**
     * @test
     */
    public function returnsCorrectSignatureFields()
    {
        $fields = $this->api->getSignatureFieldsForVariant('variant1');
        $expected = [
            'basePrice',
            'currency',
            'customerNumber',
            'customerPersonalId',
            'customerReference',
            'merchantId',
            'notifyAfterCancel',
            'notifyAfterFailure',
            'notifyAfterSuccess',
            'purchaseCountry',
            'purchaseReference',
            'serviceGroup',
            'smsAfterSuccess',
            'submerchantReference',
            'taxClass',
            'variant'
        ];
        $this->assertSame($expected, $fields);
    }

    /**
     * @test
     */
    public function paymentIsCreatedSuccessfully()
    {
        $this->api
            ->set('basePrice', '5.00')
            ->set('customerNumber', '123')
            ->set('purchaseCountry', 'FI')
            ->set('variant', 'variant1');

        $this->transport
            ->expects($this->once())
            ->method('request')
            ->with($this->callback(function(array $fields) {
                if (isset($fields['signature']) === false) {
                    return false;
                }
                unset($fields['signature']);
                $expected = [
                    'merchantId' => 1,
                    'basePrice' => '5.00',
                    'customerNumber' => '123',
                    'purchaseCountry' => 'FI',
                    'variant' => 'variant1'
                ];
                return $expected === $fields;
            }), '/payment.json')
            ->willReturn([
                201,
                '{"success":true,"purchase": {"uuid":"f9503276-80bc-4f0e-a995-16c4c7e9d0f7","redirect":"https://payment.sirumobile.com/payment/call/f9503276-80bc-4f0e-a995-16c4..."}}'
            ]);

        $result = $this->api->createPayment();

        $this->assertEquals([
            'uuid' => 'f9503276-80bc-4f0e-a995-16c4c7e9d0f7',
            'redirect' => 'https://payment.sirumobile.com/payment/call/f9503276-80bc-4f0e-a995-16c4...'
        ], $result);
    }

}
