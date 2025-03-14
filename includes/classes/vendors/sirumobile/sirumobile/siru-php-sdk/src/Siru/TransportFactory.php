<?php

namespace Siru;

use Siru\Transport\GuzzleTransport;
use Siru\Transport\SymfonyHttpClientTransport;
use Siru\Transport\TransportInterface;
use Siru\Transport\WordPressTransport;

class TransportFactory
{

    /**
     * @return TransportInterface
     */
    public static function create() : TransportInterface
    {
        if (interface_exists('\GuzzleHttp\ClientInterface') === true) {
            return new GuzzleTransport();
        }
        if (class_exists('\Symfony\Component\HttpClient\HttpClient') === true) {
            return new SymfonyHttpClientTransport();
        }
        if (defined('ABSPATH') === true && function_exists('wp_remote_get') === true) {
            return new WordPressTransport();
        }

        throw new \RuntimeException(__CLASS__ . ' requires \GuzzleHttp\ClientInterface, \Symfony\Component\HttpClient\HttpClient or \WP_Http installed.');
    }

}