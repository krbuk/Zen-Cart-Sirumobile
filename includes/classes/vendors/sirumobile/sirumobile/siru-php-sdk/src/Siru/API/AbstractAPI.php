<?php
namespace Siru\API;

use Siru\Exception\ApiException;
use Siru\Signature;
use Siru\Transport\TransportInterface;

/**
 * Base class for each Siru API class.
 */
abstract class AbstractAPI
{
    
    /**
     * Signature creator.
     * @var Signature
     */
    protected $signature;

    /**
     * @var TransportInterface
     */
    protected $transport;

    /**
     * @param Signature $signature
     * @param TransportInterface $transport
     */
    public function __construct(Signature $signature, TransportInterface $transport)
    {
        $this->signature = $signature;
        $this->transport = $transport;
    }

    /**
     * Tries to convert JSON string to array.
     * 
     * @param  string $body
     * @return array|false
     */
    protected function parseJson(string $body)
    {
        return json_decode($body, true);
    }

    /**
     * Creates an exception if error has occurred.
     *
     * @param  int|null       $httpStatus
     * @param  string         $body
     * @return ApiException
     */
    protected function createException(?int $httpStatus, string $body) : ApiException
    {
        $json = $this->parseJson($body);
        if(isset($json['error']['message'])) {
            $message = $json['error']['message'];
        } else {
            $message = 'Unknown error';
        }

        return new ApiException($message, $httpStatus ?: 0, null, $body);
    }

}
