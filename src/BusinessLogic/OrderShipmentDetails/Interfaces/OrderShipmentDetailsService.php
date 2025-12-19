<?php

namespace Packlink\BusinessLogic\OrderShipmentDetails\Interfaces;

use Packlink\BusinessLogic\Http\DTO\ShipmentLabel;
use Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;

interface OrderShipmentDetailsService
{
    /**
     * Retrieves order shipment details by order ID.
     *
     * @param string|int $orderId
     *
     * @return OrderShipmentDetails|null
     */
    public function getDetailsByOrderId($orderId);

    /**
     * Retrieves order shipment details by shipment reference.
     *
     * @param string $shipmentReference
     *
     * @return OrderShipmentDetails|null
     */
    public function getDetailsByReference($shipmentReference);

    /**
     * Returns shipment references of the orders that have not yet been completed.
     *
     * @return array
     */
    public function getIncompleteOrderReferences();

    /**
     * Retrieves list of order references where order is in one of the provided statuses.
     *
     * @param array $statuses
     * @return string[]
     */
    public function getOrderReferencesWithStatus(array $statuses);

    /**
     * Sets shipment reference number. Creates new object if it does not exist.
     *
     * @param string $orderId
     * @param string $shipmentReference
     *
     * @return void
     */
    public function setReference($orderId, $shipmentReference);

    /**
     * Sets shipment tracking URL and tracking numbers.
     *
     * @param string $shipmentReference
     * @param string $trackingUrl
     * @param array $trackingNumbers
     *
     * @return void
     * @throws OrderShipmentDetailsNotFound
     */
    public function setTrackingInfo($shipmentReference, $trackingUrl, array $trackingNumbers);

    /**
     * Updates shipment customs data.
     *
     * @param string $reference
     * @param string $customsInvoiceId
     *
     * @return void
     * @throws OrderShipmentDetailsNotFound
     */
    public function updateShipmentCustomsData($reference, $customsInvoiceId);

    /**
     * Sets shipping status.
     *
     * @param string $shipmentReference
     * @param string $shippingStatus
     *
     * @return void
     * @throws OrderShipmentDetailsNotFound
     */
    public function setShippingStatus($shipmentReference, $shippingStatus);

    /**
     * Sets shipping price and currency.
     *
     * @param string $shipmentReference
     * @param float $price
     * @param string $currency
     *
     * @return void
     * @throws OrderShipmentDetailsNotFound
     */
    public function setShippingPrice($shipmentReference, $price, $currency);

    /**
     * Sets shipment labels for shipment reference.
     *
     * @param string $shipmentReference
     * @param ShipmentLabel[] $labels
     *
     * @return void
     * @throws OrderShipmentDetailsNotFound
     */
    public function setLabelsByReference($shipmentReference, array $labels);

    /**
     * Marks a shipment label (identified by link) as printed.
     *
     * @param string $shipmentReference
     * @param string $link
     *
     * @return void
     * @throws OrderShipmentDetailsNotFound
     */
    public function markLabelPrinted($shipmentReference, $link);

    /**
     * Marks shipment identified by provided reference as deleted on Packlink.
     *
     * @param string $shipmentReference
     * @return void
     */
    public function markShipmentDeleted($shipmentReference);

    /**
     * Returns whether shipment identified by provided reference is deleted on Packlink or not.
     *
     * @param string $shipmentReference
     * @return bool
     */
    public function isShipmentDeleted($shipmentReference);
}