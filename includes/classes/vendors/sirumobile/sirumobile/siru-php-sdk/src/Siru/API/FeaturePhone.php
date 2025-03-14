<?php
namespace Siru\API;

use Siru\Exception\ApiException;

/**
 * API for checking if given IP-address is allowed to use variant2 mobile payments.
 */
class FeaturePhone extends AbstractAPI
{

    /**
     * @param  string  $ip IPv4 address
     * @return bool        True if variant2 payments are possible from this IP-address
     * @throws ApiException
     */
    public function isFeaturePhoneIP(string $ip) : bool
    {
        $signedFields = $this->signature->signMessage([ 'ip' => $ip ]);

        list($httpStatus, $body) = $this->transport->request($signedFields, '/payment/ip/feature-check');

        $json = $this->parseJson($body);

        return $json['ipPaymentsEnabled'] == true;
    }

}
