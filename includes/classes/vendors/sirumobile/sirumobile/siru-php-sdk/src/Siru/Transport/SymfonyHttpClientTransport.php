<?php

namespace Siru\Transport;

use Siru\Exception\ApiException;
use Siru\Exception\TransportException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SymfonyHttpClientTransport implements TransportInterface
{

    private $baseUrl;

    private $client;

    /**
     * @param HttpClientInterface $client
     * @internal
     */
    public function setHttpClient(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @return HttpClientInterface
     * @internal
     */
    public function getHttpClient() : HttpClientInterface
    {
        if ($this->client === null) {
            $this->client = $client = HttpClient::create();
        }
        return $this->client;
    }

    /**
     * @inheritDoc
     */
    public function setBaseUrl(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * @inheritDoc
     */
    public function request(array $fields, string $endPoint, string $method = 'GET'): array
    {
        $options = [];
        if ($method === 'GET' || $method === 'DELETE') {
            $options['query'] = $fields;
        } elseif ($method === 'POST') {
            $options['json'] = $fields;
        }

        try {
            $response = $this->getHttpClient()->request($method, $this->baseUrl . $endPoint, $options);
            return [
                $response->getStatusCode(),
                $response->getContent()
            ];
        } catch(HttpExceptionInterface $e) {
            $response = $e->getResponse();
            throw ApiException::create($response->getStatusCode(), (string)$response->getContent(false));
        } catch(TransportExceptionInterface $e) {
            throw new TransportException($e->getMessage(), 0, $e);
        }
    }
}