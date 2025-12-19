<?php

namespace Packlink\BusinessLogic\Order\Interfaces;

use Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Packlink\BusinessLogic\Http\DTO\Draft;
use Packlink\BusinessLogic\Http\DTO\Shipment;
use Packlink\BusinessLogic\Http\DTO\ShipmentLabel;
use Packlink\BusinessLogic\Order\Exceptions\EmptyOrderException;
use Packlink\BusinessLogic\Order\Exceptions\OrderNotFound;
use Packlink\BusinessLogic\Order\Objects\Order;
use Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound;

interface OrderService
{
    /**
     * Prepares shipment draft object for the given order.
     *
     * @param Order $order
     *
     * @return Draft
     *
     * @throws OrderNotFound
     * @throws EmptyOrderException
     */
    public function prepareDraft(Order $order);

    /**
     * Sets order Packlink reference number.
     *
     * @param string $orderId
     * @param string $shipmentReference
     * @return void
     */
    public function setReference($orderId, $shipmentReference);

    /**
     * @param Shipment $shipment
     * @param string $customsId
     *
     * @return void
     *
     * @throws HttpAuthenticationException
     * @throws HttpBaseException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws OrderShipmentDetailsNotFound
     */
    public function updateShipmentData(Shipment $shipment, $customsId = '');

    /**
     * Updates shipment details customs data.
     *
     * @param string $reference
     * @param string $customsInvoiceId
     *
     * @return void
     *
     * @throws OrderShipmentDetailsNotFound
     */
    public function updateShipmentCustomsData($reference, $customsInvoiceId);

    /**
     * Updates shipping status for order with the given shipment reference.
     *
     * @param Shipment $shipment
     * @param string $status
     *
     * @return void
     */
    public function updateShippingStatus(Shipment $shipment, $status);

    /**
     * Updates tracking info from API for order with given shipment reference.
     *
     * @param Shipment $shipment
     * @return void
     *
     * @throws HttpBaseException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function updateTrackingInfo(Shipment $shipment);

    /**
     * Retrieves list of order labels.
     *
     * @param string $reference
     * @return ShipmentLabel[]
     */
    public function getShipmentLabels($reference);

    /**
     * Checks whether shipment labels are available for given status.
     *
     * @param string $status
     *
     * @return bool
     */
    public function isReadyToFetchShipmentLabels($status);

    /**
     * Checks if tracking info should be updated for given status.
     *
     * @param string $status
     * @return bool
     */
    public function isTrackingInfoUpdatable($status);
}