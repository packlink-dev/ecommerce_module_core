<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\Order;

use Packlink\BusinessLogic\Http\DTO\Shipment as ShipmentDTO;
use Packlink\BusinessLogic\Http\DTO\Tracking;
use Packlink\BusinessLogic\Order\Exceptions\OrderNotFound;
use Packlink\BusinessLogic\Order\Interfaces\ShopOrderService;
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
class TestShopOrderService implements ShopOrderService
{
    /**
     * Order storage.
     *
     * @var Order[]
     */
    private static $orders;
    /**
     * Flag to throw OrderNotFound exception.
     *
     * @var bool
     */
    private $throwOrderNotFoundException = false;
    /**
     * Flag to throw generic exception.
     *
     * @var bool
     */
    private $throwGenericException = false;

    /**
     * TestOrderRepository constructor.
     */
    public function __construct()
    {
        static::$orders = array();
    }

    /**
     * Sets if OrderNotFound exception should be thrown.
     *
     * @param bool $throw Throw flag.
     */
    public function shouldThrowOrderNotFoundException($throw)
    {
        $this->throwOrderNotFoundException = $throw;
    }

    /**
     * Sets if generic exception should be thrown.
     *
     * @param bool $throw Throw flag.
     */
    public function shouldThrowGenericException($throw)
    {
        $this->throwGenericException = $throw;
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
        if ($this->throwOrderNotFoundException) {
            throw new OrderNotFound('Order not found.');
        }

        return $this->getOrder($orderId);
    }

    /**
     * Handles updated tracking info for order with a given ID.
     *
     * @param string $orderId Shop order ID.
     * @param ShipmentDTO $shipment Shipment object containing tracking codes and tracking url.
     * @param Tracking[] $trackings Shipment tracking history.
     *
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function updateTrackingInfo($orderId, ShipmentDTO $shipment, array $trackings)
    {
        if ($this->throwOrderNotFoundException) {
            throw new OrderNotFound('Order not found.');
        }

        $order = $this->getOrder($orderId);
        $trackingHistory = array();
        foreach ($trackings as $item) {
            $trackingHistory[] = TrackingHistory::fromArray($item->toArray());
        }

        $order->getShipment()->setTrackingHistory($trackingHistory);
        $order->getShipment()->setTrackingNumber(!empty($shipment->trackingCodes) ? $shipment->trackingCodes[0] : '');
    }

    /**
     * Sets order packlink shipping status to an order with a given ID.
     *
     * @param string $orderId Shop order ID.
     * @param string $shippingStatus Packlink shipping status.
     *
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided reference is not found.
     */
    public function updateShipmentStatus($orderId, $shippingStatus)
    {
        if ($this->throwOrderNotFoundException) {
            throw new OrderNotFound('Order not found.');
        }

        $order = $this->getOrder($orderId);
        $order->setStatus($shippingStatus);
    }

    /**
     * Gets order with given ID.
     *
     * @param string $orderId
     *
     * @param int $shippingMethodId
     * @param string $destinationCountry
     *
     * @param bool|null $throw
     *
     * @return Order
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function getOrder($orderId, $shippingMethodId = 0, $destinationCountry = '', $throw = null)
    {
        if ($throw === null && $this->throwOrderNotFoundException) {
            throw new OrderNotFound('Order not found.');
        }

        if (!isset(static::$orders[$orderId])) {
            $order = new Order();
            $order->setId($orderId);
            $order->setShipment(new Shipment());
            $order->setShippingMethodId($shippingMethodId);
            $order->setShippingAddress(new Address());
            $order->getShippingAddress()->setCountry($destinationCountry);
            $order->setBillingAddress(new Address());
            $order->setItems(array(new Item()));
            $order->setSellerUserId('test');

            static::$orders[$orderId] = $order;
        }

        return static::$orders[$orderId];
    }
}
