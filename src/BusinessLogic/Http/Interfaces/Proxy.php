<?php

namespace Packlink\BusinessLogic\Http\Interfaces;

use Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Packlink\BusinessLogic\Http\DTO\Customs\CustomsInvoice;
use Packlink\BusinessLogic\Http\DTO\Customs\CustomsUnionsSearchRequest;
use Packlink\BusinessLogic\Http\DTO\Draft;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceSearch;
use Packlink\BusinessLogic\Http\Exceptions\DraftNotCreatedException;

interface Proxy
{
    const CLASS_NAME = __CLASS__;

    /**
     * @return \Packlink\BusinessLogic\Http\DTO\ParcelInfo[]
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getUsersParcelInfo();

    /**
     * @return \Packlink\BusinessLogic\Warehouse\Warehouse[]
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getUsersWarehouses();

    /**
     * @param array $data
     *
     * @return string
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function register($data);

    /**
     * @return \Packlink\BusinessLogic\Http\DTO\User
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getUserData();

    /**
     * @param string $webHookUrl
     *
     * @return void
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function registerWebHookHandler($webHookUrl);

    /**
     * @param string $serviceId
     * @param string $countryCode
     * @param string $postalCode
     *
     * @return \Packlink\BusinessLogic\Http\DTO\DropOff[]
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getLocations( $serviceId,  $countryCode,  $postalCode);

    /**
     * @param string $platformCountry
     * @param string $postalZone
     * @param string $query
     *
     * @return \Packlink\BusinessLogic\Http\DTO\LocationInfo[]
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function searchLocations( $platformCountry,  $postalZone,  $query);

    /**
     * @param string $countryCode
     * @param string $zipCode
     *
     * @return \Packlink\BusinessLogic\Http\DTO\PostalCode[]
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getPostalCodes($countryCode, $zipCode);

    /**
     * @param string $countryCode
     * @param string $lang
     *
     * @return \Packlink\BusinessLogic\Http\DTO\PostalZone[]
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getPostalZones($countryCode, $lang = 'en');

    /**
     * @param ShippingServiceSearch $params
     *
     * @return \Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails[]
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getShippingServicesDeliveryDetails(ShippingServiceSearch $params);

    /**
     * @param int $id
     *
     * @return \Packlink\BusinessLogic\Http\DTO\ShippingService
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getShippingServiceDetails($id);

    /**
     * @param Draft $draft
     *
     * @return string
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws DraftNotCreatedException
     */
    public function sendDraft(Draft $draft);

    /**
     * @param string $referenceId
     *
     * @return \Packlink\BusinessLogic\Http\DTO\Shipment|null
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getShipment($referenceId);

    /**
     * @param string $referenceId
     * @return string[]
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getLabels($referenceId);

    /**
     * @param string $referenceId
     *
     * @return \Packlink\BusinessLogic\Http\DTO\Tracking[]
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getTrackingInfo($referenceId);

    /**
     * @param string $eventName
     * @return void
     */
    public function sendAnalytics($eventName);

    /**
     * @param CustomsUnionsSearchRequest $request
     *
     * @return array
     *
     * @throws HttpAuthenticationException
     * @throws HttpRequestException
     * @throws HttpCommunicationException
     */
    public function getCustomsByPostalCode(CustomsUnionsSearchRequest $request);

    /**
     * @param CustomsInvoice $customsInvoice
     *
     * @return string|null
     *
     * @throws HttpAuthenticationException
     * @throws HttpRequestException
     * @throws HttpCommunicationException
     */
    public function sendCustomsInvoice(CustomsInvoice $customsInvoice);

    /**
     * @param mixed $customsInvoiceId
     *
     * @return string
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getCustomsInvoiceDownloadUrl($customsInvoiceId);

    /**
     * @param mixed $accessToken
     *
     * @return string
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getApiKeyWithToken($accessToken);

}