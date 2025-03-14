<?php
namespace Siru\API;

use Siru\Exception\ApiException;

/**
 * Siru Price calculation API methods.
 * Can be used to calculate final call price in variant1 payments if needed.
 */
class Price extends AbstractAPI
{
    
    /**
     * Returns actual price that will be charged from the end user.
     * 
     * @param  string      $purchaseCountry      Country code, for example FI
     * @param  string      $basePrice            Price with two decimal points, fe "5.00"
     * @param  string|null $submerchantReference Optional submerchant reference
     * @param  int|null    $taxClass             Optional tax class number
     * @param  string      $variant              Variant, usually variant1 which is default
     * @param  int         $merchantId           MerchantId. If empty, merchantId from signature is used
     * @return string
     * @throws ApiException
     */
    public function calculatePrice(string $purchaseCountry, string $basePrice, ?string $submerchantReference = null, $taxClass = null, string $variant = 'variant1', $merchantId = null) : string
    {
        $fields = array_filter([
            'purchaseCountry' => $purchaseCountry,
            'basePrice' => $basePrice,
            'submerchantReference' => $submerchantReference,
            'taxClass' => $taxClass,
            'variant' => $variant,
            'merchantId' => is_numeric($merchantId) ? $merchantId : $this->signature->getMerchantId()
        ]);

        list($httpStatus, $body) = $this->transport->request($fields, '/payment/price.json');

        $json = $this->parseJson($body);

        return $json['finalCallPrice'];
    }

}
