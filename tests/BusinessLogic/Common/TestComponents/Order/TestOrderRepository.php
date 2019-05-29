<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\Order;

use Packlink\BusinessLogic\Http\DTO\Shipment as ShipmentDetails;
use Packlink\BusinessLogic\Http\DTO\Tracking;
use Packlink\BusinessLogic\Order\Exceptions\OrderNotFound;
use Packlink\BusinessLogic\Order\Interfaces\OrderRepository;
use Packlink\BusinessLogic\Order\Objects\Address;
use Packlink\BusinessLogic\Order\Objects\Item;
use Packlink\BusinessLogic\Order\Objects\Order;
use Packlink\BusinessLogic\Order\Objects\Shipment;
use Packlink\BusinessLogic\Order\Objects\TrackingHistory;

/**
 * Class TestOrderRepository.
 *
 * @package Logeecom\Tests\BusinessLogic\Common\TestComponents\Order
 */
class TestOrderRepository implements OrderRepository
{
    /**
     * Order storage.
     *
     * @var Order[]
     */
    private static $orders;
    /**
     * Flag to throw exception.
     *
     * @var bool
     */
    private $throw = false;
    /**
     * @var int
     */
    private $shippingMethodId;

    /**
     * TestOrderRepository constructor.
     */
    public function __construct()
    {
        static::$orders = array();
    }

    /**
     * Sets if exception should be thrown.
     *
     * @param bool $throw Throw flag.
     */
    public function shouldThrowException($throw = false)
    {
        $this->throw = $throw;
    }

    /**
     * Shipping method entity id.
     *
     * @param int $id Shipping method Id
     */
    public function setShippingMethodId($id)
    {
        $this->shippingMethodId = $id;
    }

    /**
     * Sets test order.
     *
     * @param \Packlink\BusinessLogic\Order\Objects\Order $order
     */
    public function setOrder(Order $order)
    {
        static::$orders[$order->getId()] = $order;
    }

    /**
     * Returns shipment references of the orders that have not yet been completed.
     *
     * @return array Array of shipment references.
     */
    public function getIncompleteOrderReferences()
    {
        return array('test');
    }

    /**
     * Fetches and returns system order by its unique identifier.
     *
     * @param string $orderId $orderId Unique order id.
     *
     * @return Order Order object.
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided id is not found.
     */
    public function getOrderAndShippingData($orderId)
    {
        if ($this->throw) {
            throw new OrderNotFound('Order not found.');
        }

        return $this->getOrder($orderId);
    }

    /**
     * Sets order packlink reference number.
     *
     * @param string $orderId Unique order id.
     * @param string $shipmentReference Packlink shipment reference.
     *
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided id is not found.
     */
    public function setReference($orderId, $shipmentReference)
    {
        if ($this->throw) {
            throw new OrderNotFound('Order not found.');
        }

        $order = $this->getOrder($orderId);
        $order->getShipment()->setReferenceNumber($shipmentReference);
    }

    /**
     * Sets order packlink shipment labels to an order by shipment reference.
     *
     * @param string $shipmentReference Packlink shipment reference.
     * @param string[] $labels Packlink shipment labels.
     *
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided reference is not found.
     */
    public function setLabelsByReference($shipmentReference, array $labels)
    {
        if ($this->throw) {
            throw new OrderNotFound('Order not found.');
        }

        $order = $this->getOrder($shipmentReference);
        $order->setPacklinkShipmentLabels($labels);
    }

    /**
     * Sets order packlink shipment tracking history to an order by shipment reference.
     *
     * @param \Packlink\BusinessLogic\Http\DTO\Shipment $shipmentDetails Packlink shipment details.
     * @param Tracking[] $trackingHistory Shipment tracking history.
     *
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided reference is not found.
     */
    public function updateTrackingInfo(ShipmentDetails $shipmentDetails, array $trackingHistory)
    {
        if ($this->throw) {
            throw new OrderNotFound('Order not found.');
        }

        $order = $this->getOrder($shipmentDetails->reference);
        $shipment = $order->getShipment() ?: new Shipment();
        $tracking = array();
        foreach ($trackingHistory as $item) {
            $point = new TrackingHistory();
            $point->setTimestamp($item->timestamp);
            $point->setCity($item->city);
            $point->setDescription($item->description);

            $tracking[] = $point;
        }

        $shipment->setTrackingHistory($tracking);
        $order->setShipment($shipment);
    }

    /**
     * Sets order packlink shipping status to an order by shipment reference.
     *
     * @param string $shipmentReference Packlink shipment reference.
     * @param string $shippingStatus Packlink shipping status.
     *
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided reference is not found.
     */
    public function setShippingStatusByReference($shipmentReference, $shippingStatus)
    {
        if ($this->throw) {
            throw new OrderNotFound('Order not found.');
        }

        $order = $this->getOrder($shipmentReference);
        $shipment = $order->getShipment() ?: new Shipment();
        $shipment->setStatus($shippingStatus);

        $order->setStatus($shippingStatus);
        $order->setShipment($shipment);
    }

    /**
     * Sets shipping price to an order by shipment reference.
     *
     * @param string $shipmentReference Packlink shipment reference.
     * @param float $price Shipment price.
     *
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided reference is not found.
     */
    public function setShippingPriceByReference($shipmentReference, $price)
    {
        if ($this->throw) {
            throw new OrderNotFound('Order not found.');
        }

        $order = $this->getOrder($shipmentReference);
        $order->setBasePrice($price);
    }

    /**
     * Marks shipment identified by provided reference as deleted on Packlink.
     *
     * @param string $shipmentReference Packlink shipment reference.
     *
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function markShipmentDeleted($shipmentReference)
    {
        if ($this->throw) {
            throw new OrderNotFound('Order not found.');
        }

        $order = $this->getOrder($shipmentReference);

        $order->setDeleted(true);
    }

    /**
     * Returns whether shipment identified by provided reference is deleted on Packlink or not.
     *
     * @param string $shipmentReference Packlink shipment reference.
     *
     * @return bool Returns TRUE if shipment has been deleted; otherwise returns FALSE.
     *
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function isShipmentDeleted($shipmentReference)
    {
        if ($this->throw) {
            throw new OrderNotFound('Order not found.');
        }

        $order = $this->getOrder($shipmentReference);

        return $order->isDeleted();
    }

    /**
     * Test method
     *
     * @param $orderId
     *
     * @param $shippingMethodId
     * @param $destinationCountry
     *
     * @return Order
     */
    public function getOrder($orderId, $shippingMethodId = 0, $destinationCountry = '')
    {
        if (!isset(static::$orders[$orderId])) {
            $order = new Order();
            $order->setId($orderId);
            $order->setShipment(new Shipment());
            $order->setShippingMethodId($shippingMethodId ?: $this->shippingMethodId);
            $order->setShippingAddress(new Address());
            $order->getShippingAddress()->setCountry($destinationCountry);
            $order->setBillingAddress(new Address());
            $order->setItems(array(new Item()));

            static::$orders[$orderId] = $order;
        }

        return static::$orders[$orderId];
    }

    /**
     * Returns whether shipment identified by provided reference has Packlink shipment labels set.
     *
     * @param string $shipmentReference Packlink shipment reference.
     *
     * @return bool Returns TRUE if labels are set; otherwise returns FALSE.
     */
    public function isLabelSet($shipmentReference)
    {
        $order = $this->getOrder($shipmentReference);

        $packlinkShipmentLabels = $order->getPacklinkShipmentLabels();

        return !empty($packlinkShipmentLabels);
    }
}
