<?php
namespace Siru\Tests;

use PHPUnit\Framework\TestCase;
use Siru\Signature;

class SignatureTest extends TestCase
{

    /**
     * @param int   $merchantId
     * @param string $secret
     * @param array $fields
     * @param array $signedFields
     * @param int   $flags
     * @param string $expected
     * @test
     * @dataProvider provideDataForSignatures
     */
    public function canCreateValidSignature($merchantId, $secret, array $fields, array $signedFields, $flags, $expected)
    {
        $signature = new Signature($merchantId, $secret);
        $hash = $signature->createMessageSignature($fields, $signedFields, $flags);
        $this->assertEquals($expected, $hash, 'Correct signature was not created.');
    }

    /**
     * @test
     */
    public function messageIsSignedCorrectly()
    {
        list($merchantId, $secret, $fields, $signedFields, $flags, $expected) = $this->provideDataForSignatures()[0];

        $signature = new Signature($merchantId, $secret);

        $message = $signature->signMessage($fields, $signedFields, $flags);

        $this->assertEquals($merchantId, $signature->getMerchantId(), 'MerchantId was not set correctly by Signature::__construct().');
        $this->assertEquals($merchantId, $message['merchantId'], 'MerchantId was not set correctly in signed message.');
        $this->assertEquals($expected, $message['signature'], 'Message was not signed correctly.');
    }

    /**
     * @param int    $merchantId
     * @param string $secret
     * @param array  $fields
     * @param bool   $expectedResult
     * @test
     * @dataProvider provideNotifications
     */
    public function canAuthenticateNotification($merchantId, $secret, array $fields, $expectedResult)
    {
        $signature = new Signature($merchantId, $secret);

        $this->assertEquals($expectedResult, $signature->isNotificationAuthentic($fields));
    }

    public function provideDataForSignatures()
    {
        return [
            // values are not sorted or empty values removed, all values are included
            [
                123,
                'xooxer',
                ['foo' => 'bar', 'xoo' => 'xer', 'crux' => ''],
                [],
                0,
                'ce932ab1fe0c2c3bd8faa3ffb9a917dcc329758743cf056ec7e2d5f560dd186171251e9cc10ba7f89bffaaf869a6d07534cac53a9abc1a93d73853fe8ca26617'
            ],
            // values are not sorted or empty values removed, some values are excluded
            [
                123,
                'xooxer',
                ['foo' => 'bar', 'xoo' => 'xer', 'crux' => ''],
                ['merchantId', 'foo', 'crux'],
                0,
                '8b9d18f3b133734e039dca174bafeb3446b15925477f064d39202aa75d1f8c01473b16ae5073f8b5a6dbb6b037e1d72df050b8d80a2abff86e72b79120aba3eb'
            ],
            // values are sorted, empty values are not removed, all values are included
            [
            123,
                'xooxer',
                ['foo' => 'bar', 'xoo' => 'xer', 'crux' => ''],
                [],
                Signature::SORT_FIELDS,
                '5b70165bbfeec9c365d57464e95958003fc17019faac5b64e4b082363d7c3c690a1b55d9895e0b8c6f3456c955fe0110bf7f71e663016ac5019728c02c3f1fd9'
            ],
            // values are not sorted, empty values are removed, all values are included
            [
                123,
                'xooxer',
                ['foo' => 'bar', 'xoo' => 'xer', 'crux' => ''],
                [],
                Signature::FILTER_EMPTY,
                '360cb77039d25d5cb9b0b9a7ff7f461fa3be38084f2d60d570eb1ab1f94ab097bfab01a49aaffb94e4bd95959744ee88abcd5ef06e3c2f5e1ccd1671fb134cb4'
            ],
            // values are sorted and empty values are removed, all values are included
            [
                123,
                'xooxer',
                ['xoo' => 'xer', 'foo' => 'bar', 'crux' => ''],
                [],
                Signature::FILTER_EMPTY | Signature::SORT_FIELDS,
                'a2f5396baba48bde633fb71398aa32f63e0be6a9a9740337e52f4e67e2e4c53f27cdca9fa7ef2a21f1ddf70485032cc1d247a5f961926c32cde8e61858854e37'
            ],
            // values are sorted and empty values are removed, some values are excluded
            [
                123,
                'xooxer',
                ['xoo' => 'xer', 'foo' => 'bar', 'crux' => ''],
                ['merchantId', 'foo', 'crux'],
                Signature::FILTER_EMPTY | Signature::SORT_FIELDS,
                '05a50b6bef31edeab258c1a8ad0d89bf7c71fe3619fb8f4ecbd418ea0b3d56665669a692b9068230c13d789399ee9fcd275ddcfa53e1b3db98eb69b289035678'
            ],
        ];
    }

    public function provideNotifications()
    {
        return [
            // Everything is valid
            [
                1,
                'xooxer',
                [
                    'siru_uuid' => '9af5dd85-9ba5-4e10-9f87-81146c9c83cb',
                    'siru_merchantId' => '1',
                    'siru_submerchantReference' => '',
                    'siru_purchaseReference' => 'demoshop-20180815125916',
                    'siru_event' => 'cancel',
                    'siru_signature' => '5546697c145bf90f369f117ef5e8f7ac684d73a600450815954a25d516b1d77503c0aca69106d708ffd45969b888bc606bac5ce94a36b27dc33c458d80b2c47c'
                ],
                true
            ],
            // Array order should not matter
            [
                1,
                'xooxer',
                [
                    'siru_signature' => '5546697c145bf90f369f117ef5e8f7ac684d73a600450815954a25d516b1d77503c0aca69106d708ffd45969b888bc606bac5ce94a36b27dc33c458d80b2c47c',
                    'siru_purchaseReference' => 'demoshop-20180815125916',
                    'siru_merchantId' => '1',
                    'siru_uuid' => '9af5dd85-9ba5-4e10-9f87-81146c9c83cb',
                    'siru_event' => 'cancel',
                    'siru_submerchantReference' => ''
                ],
                true
            ],
            // Missing empty value in array does not matter
            [
                1,
                'xooxer',
                [
                    'siru_uuid' => '9af5dd85-9ba5-4e10-9f87-81146c9c83cb',
                    'siru_merchantId' => '1',
                    'siru_purchaseReference' => 'demoshop-20180815125916',
                    'siru_event' => 'cancel',
                    'siru_signature' => '5546697c145bf90f369f117ef5e8f7ac684d73a600450815954a25d516b1d77503c0aca69106d708ffd45969b888bc606bac5ce94a36b27dc33c458d80b2c47c'
                ],
                true
            ],
            // merchantId passed to Signature differs but this should not affect authentication
            [
                123,
                'xooxer',
                [
                    'siru_uuid' => '9af5dd85-9ba5-4e10-9f87-81146c9c83cb',
                    'siru_merchantId' => '1',
                    'siru_submerchantReference' => '',
                    'siru_purchaseReference' => 'demoshop-20180815125916',
                    'siru_event' => 'cancel',
                    'siru_signature' => '5546697c145bf90f369f117ef5e8f7ac684d73a600450815954a25d516b1d77503c0aca69106d708ffd45969b888bc606bac5ce94a36b27dc33c458d80b2c47c'
                ],
                true
            ],
            // Invalid signature
            [
                1,
                'xooxer',
                [
                    'siru_uuid' => '9af5dd85-9ba5-4e10-9f87-81146c9c83cb',
                    'siru_merchantId' => '1',
                    'siru_submerchantReference' => '',
                    'siru_purchaseReference' => 'demoshop-20180815125916',
                    'siru_event' => 'cancel',
                    'siru_signature' => '5546697c145bf90f369f117ef5e8f7ac684d73a600450815954a25d516b1d77503c0aca69106d708ffd45969b888bc606bac5ce94a36b27dc33c458d80b2c47b'
                ],
                false
            ],
            // Merchant secret does not match
            [
                1,
                'lussutus',
                [
                    'siru_uuid' => '9af5dd85-9ba5-4e10-9f87-81146c9c83cb',
                    'siru_merchantId' => '1',
                    'siru_submerchantReference' => '',
                    'siru_purchaseReference' => 'demoshop-20180815125916',
                    'siru_event' => 'cancel',
                    'siru_signature' => '5546697c145bf90f369f117ef5e8f7ac684d73a600450815954a25d516b1d77503c0aca69106d708ffd45969b888bc606bac5ce94a36b27dc33c458d80b2c47c'
                ],
                false
            ],
        ];
    }

}