<?php
namespace Siru\Exception;

/**
 * This exception is thrown when API responds with an error message.
 * In case of Payment API, there can be multiple error messages returned at once. You can retrieve list
 * of these using getErrorStack().
 */
class ApiException extends \RuntimeException implements SiruExceptionInterface
{
    
    private $errorStack = [];

    private $responseBody;

    public function __construct($message = '', $code = 0, \Exception $e = null, ?string $body = '')
    {
        parent::__construct($message, $code, $e);

        $this->responseBody = $body;
    }

    public static function create(?int $httpStatus, string $body) : ApiException
    {
        $json = json_decode($body, true);

        $message = 'API request failed.';
        if (isset($json['error'])) {
            if (is_string($json['error']) === true) {
                $message = $json['error'];
            } elseif (is_array($json['error']) === true && isset($json['error']['message']) === true ) {
                $message = $json['error']['message'];
            }
        }

        if(isset($json['errors'])) {
            $errorStack = $json['errors'];
        } else {
            $errorStack = [];
        }

        $exception = new self($message, $httpStatus ?: 0, null, $body);
        $exception->errorStack = $errorStack;
        return $exception;
    }

    /**
     * Get raw message body from HTTP response if available.
     *
     * @return string|null
     */
    public function getResponseBody() : ?string
    {
        return $this->responseBody;
    }

    /**
     * Returns all error messages received from API.
     * 
     * @return array
     */
    public function getErrorStack() : array
    {
        return $this->errorStack;
    }

}
