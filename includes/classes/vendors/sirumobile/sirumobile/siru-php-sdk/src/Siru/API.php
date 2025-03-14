<?php

namespace Siru;

use Siru\API\FeaturePhone;
use Siru\API\Kyc;
use Siru\API\OperationalStatus;
use Siru\API\Payment;
use Siru\API\Price;
use Siru\API\PurchaseStatus;
use Siru\Transport\TransportInterface;

/**
 * This class is used to create instances of different API objects which in turn are used to call different API methods.
 */
class API
{

    const ENDPOINT_STAGING = 'https://staging.sirumobile.com';
    const ENDPOINT_PRODUCTION = 'https://payment.sirumobile.com';

    /**
     * @var TransportInterface
     */
    private $transport;
    
    /**
     * Signature creator.
     * @var Signature
     */
    private $signature;

    /**
     * Siru API endpoint host name
     * @var string
     */
    private $endPoint;

    /**
     * Default values for payment requests.
     * @var array
     */
    private $defaults = [];

    /**
     * @param Signature $signature
     * @param TransportInterface|null $transport
     */
    public function __construct(Signature $signature, TransportInterface $transport = null)
    {
        $this->signature = $signature;
        $this->transport = $transport !== null ? $transport : TransportFactory::create();
        $this->useStagingEndpoint();
        $this->setDefaults('merchantId', $signature->getMerchantId());
    }

    /**
     * Send API requests to Siru staging endpoint. Used for testing during integration.
     * 
     * @return API
     */
    public function useStagingEndpoint() : self
    {
        return $this->setEndpointUrl(self::ENDPOINT_STAGING);
    }

    /**
     * Send API requests to Siru production endpoint. You must explicitly call this method when in live environment.
     * 
     * @return API
     */
    public function useProductionEndpoint() : self
    {
        return $this->setEndpointUrl(self::ENDPOINT_PRODUCTION);
    }

    /**
     * Set payment gateway base URL.
     *
     * @param string $url
     * @return API
     * @internal
     */
    public function setEndpointUrl(string $url) : self
    {
        $this->endPoint = $url;
        $this->transport->setBaseUrl($url);

        return $this;
    }

    /**
     * Get payment gateway base URL.
     *
     * @return string
     */
    public function getEndpointUrl() : string
    {
        return $this->endPoint;
    }

    /**
     * Sets default values for payment requests.
     *
     * You can pass all values as an array with key/value pairs or you can give
     * field name as first parameter and value as second parameter.
     * Using NULL as value will remove the default value.
     *
     * @param   string|array $keyOrArray Field name or array of field/value pairs
     * @param   string|null  $value      Field value when $keyOrArray is a string
     * @return  API
     */
    public function setDefaults($keyOrArray, ?string $value = null) : self
    {
        if(is_array($keyOrArray) || is_object($keyOrArray)) {

            foreach($keyOrArray as $k => $v) {
                $this->setDefaults($k, $v);
            }

        } else {
            if($value === null) {
                unset($this->defaults[$keyOrArray]);
            } else {
                $this->defaults[$keyOrArray] = $value;
            }
        }

        return $this;
    }

    /**
     * Returns default values or a single default value for payment requests.
     * 
     * @param  string|null $key Field name or null to return all defaults as an array
     * @return string|null|array
     */
    public function getDefaults(?string $key = null)
    {
        if($key) {
            return array_key_exists($key, $this->defaults) ? $this->defaults[$key] : null;
        }

        return $this->defaults;
    }

    /**
     * @return TransportInterface
     */
    protected function getTransport() : TransportInterface
    {
        return $this->transport;
    }

    /**
     * @param TransportInterface $transport
     * @return API
     */
    protected function setTransport(TransportInterface $transport) : self
    {
        $this->transport = $transport;
        $this->transport->setBaseUrl($this->endPoint);
        return $this;
    }

    /**
     * Get Signature object for this API.
     *
     * @return Signature
     */
    public function getSignature() : Signature
    {
        return $this->signature;
    }

    /**
     * Returns Payment API object.
     * All default values set using setDefaults() are automatically passed to Payment API object.
     * 
     * @return Payment
     */
    public function getPaymentApi() : Payment
    {
        $api = new Payment($this->signature, $this->transport);

        array_walk($this->defaults, function($value, $key) use ($api) {
            $api->set($key, $value);
        });

        return $api;
    }

    /**
     * Returns Purchase status API object. Used for retrieving single payment status or search payments.
     *
     * @return PurchaseStatus
     */
    public function getPurchaseStatusApi() : PurchaseStatus
    {
        return new PurchaseStatus($this->signature, $this->transport);
    }

    /**
     * Returns KYC API object. Used for KYC data of successful payments.
     *
     * @return Kyc
     */
    public function getKycApi() : Kyc
    {
        return new Kyc($this->signature, $this->transport);
    }

    /**
     * Returns Price API object. Used for calculating final call price for variant1 payments if needed.
     * 
     * @return Price
     */
    public function getPriceApi() : Price
    {
        return new Price($this->signature, $this->transport);
    }

    /**
     * Returns Feature detection API object. Used to check if variant2 payments are possible for given IP-address.
     * 
     * @return FeaturePhone
     */
    public function getFeaturePhoneApi() : FeaturePhone
    {
        return new FeaturePhone($this->signature, $this->transport);
    }

    /**
     * Returns Operational status API which can be used to check if Siru API is up.
     * 
     * @return OperationalStatus
     */
    public function getOperationalStatusApi() : OperationalStatus
    {
        return new OperationalStatus($this->signature, $this->transport);
    }

}
