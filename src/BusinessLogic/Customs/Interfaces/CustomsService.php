<?php

namespace Packlink\BusinessLogic\Customs\Interfaces;

use Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Packlink\BusinessLogic\Order\Objects\Order;

interface CustomsService
{
    /**
     * Checks if the shipment is international.
     *
     * @param $countryCode
     * @param $postalCode
     *
     * @return bool
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function isShipmentInternational($countryCode, $postalCode);

    /**
     * Determines whether customs invoice should be created for the given destination.
     *
     * @param $countryCode
     * @param $postalCode
     *
     * @return bool
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function shouldCreateCustoms($countryCode, $postalCode);

    /**
     * Sends customs invoice.
     *
     * @param Order $order
     *
     * @return string|null
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function sendCustomsInvoice($order);

}