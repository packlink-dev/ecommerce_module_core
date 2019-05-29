<?php

namespace Packlink\BusinessLogic\Order\Interfaces;

use Packlink\BusinessLogic\Http\DTO\Shipment;
use Packlink\BusinessLogic\Http\DTO\Tracking;
use Packlink\BusinessLogic\Order\Objects\Order;

/**
 * Interface OrderRepository
 *
 * @package Packlink\BusinessLogic\Order\Interfaces
 */
interface OrderRepository
{
    /**
     * Fully qualified name of this interface.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Returns shipment references of the orders that have not yet been completed.
     *
     * @return array Array of shipment references.
     */
    public function getIncompleteOrderReferences();

    /**
     * Fetches and returns system order by its unique identifier.
     *
     * @param string $orderId $orderId Unique order id.
     *
     * @return Order Order object.
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided id is not found.
     */
    public function getOrderAndShippingData($orderId);

    /**
     * Sets order packlink reference number.
     *
     * @param string $orderId Unique order id.
     * @param string $shipmentReference Packlink shipment reference.
     *
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided id is not found.
     */
    public function setReference($orderId, $shipmentReference);

    /**
     * Sets order packlink shipping labels to an order by shipment reference.
     *
     * @param string $shipmentReference Packlink shipment reference.
     * @param string[] $labels Packlink shipping labels.
     *
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided reference is not found.
     */
    public function setLabelsByReference($shipmentReference, array $labels);

    /**
     * Sets order packlink shipment tracking history to an order for given shipment.
     *
     * @param Shipment $shipment Packlink shipment details.
     * @param Tracking[] $trackingHistory Shipment tracking history.
     *
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided reference is not found.
     */
    public function updateTrackingInfo(Shipment $shipment, array $trackingHistory);

    /**
     * Sets order packlink shipping status to an order by shipment reference.
     *
     * @param string $shipmentReference Packlink shipment reference.
     * @param string $shippingStatus Packlink shipping status.
     *
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided reference is not found.
     */
    public function setShippingStatusByReference($shipmentReference, $shippingStatus);

    /**
     * Sets shipping price to an order by shipment reference.
     *
     * @param string $shipmentReference Packlink shipment reference.
     * @param float $price Shipment price.
     *
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided reference is not found.
     */
    public function setShippingPriceByReference($shipmentReference, $price);

    /**
     * Marks shipment identified by provided reference as deleted on Packlink.
     *
     * @param string $shipmentReference Packlink shipment reference.
     */
    public function markShipmentDeleted($shipmentReference);

    /**
     * Returns whether shipment identified by provided reference is deleted on Packlink or not.
     *
     * @param string $shipmentReference Packlink shipment reference.
     *
     * @return bool Returns TRUE if shipment has been deleted; otherwise returns FALSE.
     */
    public function isShipmentDeleted($shipmentReference);

    /**
     * Returns whether shipment identified by provided reference has Packlink shipment label set.
     *
     * @param string $shipmentReference Packlink shipment reference.
     *
     * @return bool Returns TRUE if label is set; otherwise, FALSE.
     */
    public function isLabelSet($shipmentReference);
}
