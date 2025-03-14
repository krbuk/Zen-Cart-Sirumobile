<?php
namespace Siru\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Siru\API;
use Siru\Signature;
use Siru\Transport\TransportInterface;

class APITest extends TestCase
{

    /**
     * @var Signature
     */
    private $signature;

    /**
     * @var API
     */
    private $api;

    /**
     * @var TransportInterface|MockObject
     */
    private $transport;

    public function setUp() : void
    {
        $this->signature = new Signature(1, 'xooxer');
        $this->transport = $this->createMock(TransportInterface::class);
        $this->api = new API($this->signature, $this->transport);
    }

    /**
     * @test
     */
    public function merchantIdIsDetectedFromSignature()
    {
        $this->assertEquals($this->signature->getMerchantId(), $this->api->getDefaults('merchantId'), 'MerchantId was not automatically set from signature.');
    }

    /**
     * @test
     */
    public function signatureGetterWorks()
    {
        $this->assertSame($this->signature, $this->api->getSignature());
        $this->assertEquals($this->signature->getMerchantId(), $this->api->getDefaults('merchantId'), 'MerchantId was not automatically set from signature.');
    }

    /**
     * @test
     */
    public function endPointIsSetCorrectly()
    {
        $this->transport
            ->expects($this->exactly(2))
            ->method('setBaseUrl')
            ->withConsecutive(
                [API::ENDPOINT_PRODUCTION],
                ['https://lussu.tussi']
            );

        $this->assertEquals(API::ENDPOINT_STAGING, $this->api->getEndpointUrl(), 'Endpoint should be staging by default.');

        $this->api->useProductionEndpoint();
        $this->assertEquals(API::ENDPOINT_PRODUCTION, $this->api->getEndpointUrl(), 'Endpoint did not change as expected.');

        $this->api->setEndpointUrl('https://lussu.tussi');
        $this->assertEquals('https://lussu.tussi', $this->api->getEndpointUrl(), 'Endpoint did not change as expected.');
    }

    /**
     * @test
     */
    public function canSetDefaultValuesForRequest()
    {
        $this->api->setDefaults([
            'xoo' => 'xer',
            'foo' => 'bar'
        ]);

        $this->assertEquals('xer', $this->api->getDefaults('xoo'), 'Request default value was not set correctly.');
        $this->assertEquals('bar', $this->api->getDefaults('foo'), 'Request default value was not set correctly.');
        $this->assertEquals(null, $this->api->getDefaults('unknown'), 'Unknown value should return null as default value.');

        $this->api->setDefaults('xoo', 'lusso');
        $this->api->setDefaults('lorem', 'ipsum');
        $this->assertEquals('lusso', $this->api->getDefaults('xoo'), 'Request default value was not set correctly.');
        $this->assertEquals('ipsum', $this->api->getDefaults('lorem'), 'Request default value was not set correctly.');

        $this->api->setDefaults('lorem', null);
        $this->assertEquals(null, $this->api->getDefaults('lorem'), 'Unknown value should return null as default value.');

        $allDefaults = $this->api->getDefaults();
        $expected = [
            'merchantId' => $this->signature->getMerchantId(),
            'xoo' => 'lusso',
            'foo' => 'bar'
        ];

        foreach($expected as $key => $value) {
            $this->assertArrayHasKey($key, $allDefaults);
            $this->assertEquals($value, $allDefaults[$key]);
        }
    }

    /**
     * @test
     */
    public function returnsExpectedApiClasses()
    {
        $this->assertInstanceOf(API\Payment::class, $this->api->getPaymentApi());
        $this->assertInstanceOf(API\PurchaseStatus::class, $this->api->getPurchaseStatusApi());
        $this->assertInstanceOf(API\FeaturePhone::class, $this->api->getFeaturePhoneApi());
        $this->assertInstanceOf(API\OperationalStatus::class, $this->api->getOperationalStatusApi());
        $this->assertInstanceOf(API\Price::class, $this->api->getPriceApi());
        $this->assertInstanceOf(API\Kyc::class, $this->api->getKycApi());
    }

}
