<?php
namespace Siru\API;

use DateTime;
use DateTimeZone;
use Siru\Exception\ApiException;

/**
 * Siru purchase status API methods.
 */
class PurchaseStatus extends AbstractAPI
{
    
    /**
     * Find a single purchase by purchase UUID that you received from Payment API.
     *
     * Note: that if purchase is not found, method throws ApiException.
     *
     * Example response array:
     * Array
     * (
     *     [uuid] => 88a0f51b-e6aa-41f2-8663-4a0112990a7c
     *     [submerchantReference] => null
     *     [customerReference] => null
     *     [purchaseReference] => testshop1420070765
     *     [status] => confirmed
     *     [basePrice] => 5.00
     *     [finalPrice] => 7.50
     *     [currency] => EUR
     *     [createdAt] => 2017-02-08T12:41:45+0000
     *     [startedAt] => 2017-02-08T12:42:00+0000
     *     [finishedAt] => 2017-02-08T12:42:09+0000
     *     [customerNumber] => 358441234567
     * )
     * 
     * @param  string $uuid Uuid received from Payment API
     * @return array        Single purchase details as an array
     * @throws ApiException
     */
    public function findPurchaseByUuid(string $uuid) : array
    {
        $fields = $this->signature->signMessage([ 'uuid' => $uuid ]);

        list($httpStatus, $body) = $this->transport->request($fields, '/payment/byUuid.json');

        return $this->parseJson($body);
    }

    /**
     * Returns array of purchases that match given parameters or
     * empty array if no matches are found.
     * 
     * Example response array:
     * Array
     * (
     *     [0] => Array
     *         (
     *             [uuid] => 88a0f51b-e6aa-41f2-8663-4a0112990a7c
     *             [submerchantReference] => null
     *             [customerReference] => null
     *             [purchaseReference] => testshop1420070765
     *             [status] => confirmed
     *             [basePrice] => 5.00
     *             [finalPrice] => 7.50
     *             [currency] => EUR
     *             [createdAt] => 2017-02-08T12:41:45+0000
     *             [startedAt] => 2017-02-08T12:42:00+0000
     *             [finishedAt] => 2017-02-08T12:42:09+0000
     *             [customerNumber] => 358441234567
     *         )
     * )
     * 
     * @param  string      $purchaseReference    Purchase reference sent to API
     * @param  string|null $submerchantReference Optional submerchantReference
     * @return array
     * @throws ApiException
     */
    public function findPurchasesByReference(string $purchaseReference, ?string $submerchantReference = null) : array
    {
        $fields = $this->signature->signMessage([
            'submerchantReference' => $submerchantReference,
            'purchaseReference' => $purchaseReference
        ]);

        list($httpStatus, $body) = $this->transport->request($fields, '/payment/byPurchaseReference.json');

        $json = $this->parseJson($body);
        return $json['purchases'];
    }

    /**
     * Returns array of purchases for given time period. Timestamps are automatically sent to API in UTC timezone. 
     * 
     * Example response array:
     * Array
     * (
     *     [0] => Array
     *         (
     *             [id] => 408
     *             [uuid] => 88a0f51b-e6aa-41f2-8663-4a0112990a7c
     *             [merchantId] => 1
     *             [submerchantReference] => siru-international
     *             [customerReference] => 
     *             [purchaseReference] => demoshop
     *             [customerNumber] => 358441234567
     *             [basePrice] => 5.00
     *             [finalPrice] => 7.50
     *             [currency] => EUR
     *             [status] => confirmed
     *             [createdAt] => 2017-02-08T12:41:45+0000
     *             [startedAt] => 2017-02-08T12:42:00+0000
     *             [finishedAt] => 2017-02-08T12:42:09+0000
     *         )
     * )
     * 
     * @param  DateTime $from  Lower date limit. Purchases with this datetime or higher will be included in the result.
     * @param  DateTime $to    Upper date limit. Purchases created before this datetime are included in the result.
     * @return array
     * @throws ApiException
     */
    public function findPurchasesByDateRange(DateTime $from, DateTime $to) : array
    {
        $searchFrom = clone $from;
        $searchTo = clone $to;
        $searchFrom->setTimezone(new DateTimeZone('UTC'));
        $searchTo->setTimezone(new DateTimeZone('UTC'));

        $dateFormat = 'Y-m-d H:i:s';
        $fields = $this->signature->signMessage([
            'from' => $searchFrom->format($dateFormat),
            'to' => $searchTo->format($dateFormat)
        ]);

        list($httpStatus, $body) = $this->transport->request($fields, '/payment/byDate.json');

        $json = $this->parseJson($body);
        return $json['purchases'];
    }

    /**
     * Attempt to cancel pending purchase by UUID.
     *
     * If the purchase state does not allow purchase to be canceled, an ApiException is thrown.
     *
     * @param  string $uuid Uuid received from Payment API
     * @throws ApiException
     */
    public function cancelPurchaseByUuid(string $uuid) : void
    {
        $fields = $this->signature->signMessage([ 'uuid' => $uuid ]);

        list($httpStatus, $body) = $this->transport->request($fields, '/payment/byUuid.json', 'DELETE');
    }

}
