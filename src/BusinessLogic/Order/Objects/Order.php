<?php

namespace Packlink\BusinessLogic\Order\Objects;

/**
 * Class Order
 * @package Packlink\BusinessLogic\Order
 */
class Order
{
    /**
     * Unique identifier.
     *
     * @var string
     */
    private $id;
    /**
     * Order number.
     *
     * @var string
     */
    private $orderNumber;
    /**
     * Order status.
     *
     * @var string
     */
    private $status;
    /**
     * Currency ISO3 code.
     *
     * @var string
     */
    private $currency;
    /**
     * High priority order
     *
     * @var bool
     */
    private $highPriority = false;
    /**
     * Order total price with tax.
     *
     * @var float
     */
    private $totalPrice;
    /**
     * Order total price without tax.
     *
     * @var float
     */
    private $basePrice;
    /**
     * Cart price with tax.
     *
     * @var float
     */
    private $cartPrice;
    /**
     * Shipping cost.
     *
     * @var float
     */
    private $shippingPrice;
    /**
     * Cart price without tax.
     *
     * @var float
     */
    private $netCartPrice;
    /**
     * Customer unique identifier.
     *
     * @var string
     */
    private $customerId;
    /**
     * Shipping method entity identifier.
     *
     * @var int
     */
    private $shippingMethodId;
    /**
     * Order shipments.
     *
     * @var Shipment
     */
    private $shipment;
    /**
     * Shipping Address.
     *
     * @var Address
     */
    private $shippingAddress;
    /**
     * Shipping drop-off point unique identifier.
     *
     * @var string
     */
    private $shippingDropOffId;
    /**
     * Billing Address.
     *
     * @var Address
     */
    private $billingAddress;
    /**
     * Order items.
     *
     * @var Item[]
     */
    private $items = array();
    /**
     * Packlink shipment reference labels.
     *
     * @var string[]
     */
    private $packlinkShipmentLabels = array();
    /**
     * Is associated shipment on Packlink has been deleted.
     *
     * @var bool
     */
    private $deleted = false;

    /**
     * Returns order unique identifier.
     *
     * @return string Unique identifier.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets order unique identifier.
     *
     * @param string $id Unique identifier.
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Returns order number.
     *
     * @return string Order number.
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    /**
     * Sets order number.
     *
     * @param string $orderNumber Order number.
     */
    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = $orderNumber;
    }

    /**
     * Returns status.
     *
     * @return string status.
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets order status.
     *
     * @param string $status Order status.
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Returns order currency.
     *
     * @return string Currency code.
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Sets order currency.
     *
     * @param string $currency Currency code.
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * Is high priority order.
     *
     * @return bool Priority flag.
     */
    public function isHighPriority()
    {
        return $this->highPriority;
    }

    /**
     * Sets order priority flag.
     *
     * @param bool $highPriority Priority flag.
     */
    public function setHighPriority($highPriority)
    {
        $this->highPriority = $highPriority;
    }

    /**
     * Returns order total price with tax.
     *
     * @return float Order total price.
     */
    public function getTotalPrice()
    {
        return $this->totalPrice;
    }

    /**
     * Sets order total price with tax.
     *
     * @param float $totalPrice Order total price.
     */
    public function setTotalPrice($totalPrice)
    {
        $this->totalPrice = $totalPrice;
    }

    /**
     * Returns order total price without tax.
     *
     * @return float Order net price.
     */
    public function getBasePrice()
    {
        return $this->basePrice;
    }

    /**
     * Sets order total price without tax.
     *
     * @param float $basePrice Order net price.
     */
    public function setBasePrice($basePrice)
    {
        $this->basePrice = $basePrice;
    }

    /**
     * Returns order cart price with tax.
     *
     * @return float Order cart price.
     */
    public function getCartPrice()
    {
        return $this->cartPrice;
    }

    /**
     * Sets order cart price with tax.
     *
     * @param float $cartPrice Order cart price.
     */
    public function setCartPrice($cartPrice)
    {
        $this->cartPrice = $cartPrice;
    }

    /**
     * Returns order shipping price.
     *
     * @return float Order shipping price.
     */
    public function getShippingPrice()
    {
        return $this->shippingPrice;
    }

    /**
     * Sets order shipping price.
     *
     * @param float $shippingPrice Order shipping price.
     */
    public function setShippingPrice($shippingPrice)
    {
        $this->shippingPrice = $shippingPrice;
    }

    /**
     * Returns order cart price without tax.
     *
     * @return float Cart net price.
     */
    public function getNetCartPrice()
    {
        return $this->netCartPrice;
    }

    /**
     * Sets order cart price without tax.
     *
     * @param float $netCartPrice Cart net price.
     */
    public function setNetCartPrice($netCartPrice)
    {
        $this->netCartPrice = $netCartPrice;
    }

    /**
     * Returns customer unique identifier.
     *
     * @return string Customer unique identifier.
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * Sets customer unique identifier.
     *
     * @param string $customerId Customer unique identifier.
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;
    }

    /**
     * Returns shipping method entity identifier.
     *
     * @return int Shipping method entity identifier.
     */
    public function getShippingMethodId()
    {
        return $this->shippingMethodId;
    }

    /**
     * Sets shipping method entity identifier.
     *
     * @param int $shippingMethodId Shipping method entity identifier.
     */
    public function setShippingMethodId($shippingMethodId)
    {
        $this->shippingMethodId = $shippingMethodId;
    }

    /**
     * Returns shipping address.
     *
     * @return Address Shipping address information.
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }

    /**
     * Sets shipping address.
     *
     * @param Address $shippingAddress Shipping address information.
     */
    public function setShippingAddress($shippingAddress)
    {
        $this->shippingAddress = $shippingAddress;
    }

    /**
     * Returns billing address.
     *
     * @return Address Billing address information.
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * Sets billing address.
     *
     * @param Address $billingAddress Billing address information.
     */
    public function setBillingAddress($billingAddress)
    {
        $this->billingAddress = $billingAddress;
    }

    /**
     * Returns order items.
     *
     * @return Item[] Array of order items.
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Sets order items.
     *
     * @param Item[] $items Array of order items.
     */
    public function setItems($items)
    {
        $this->items = $items;
    }

    /**
     * Returns order shipment.
     *
     * @return Shipment Order shipment.
     */
    public function getShipment()
    {
        return $this->shipment;
    }

    /**
     * Sets order shipment.
     *
     * @param Shipment $shipment Order shipment.
     */
    public function setShipment(Shipment $shipment)
    {
        $this->shipment = $shipment;
    }

    /**
     * Returns Packlink shipment labels.
     *
     * @return string[] Array of Packlink shipment labels.
     */
    public function getPacklinkShipmentLabels()
    {
        return $this->packlinkShipmentLabels;
    }

    /**
     * Sets Packlink shipment labels.
     *
     * @param string[] $packlinkShipmentLabels Array of Packlink shipment labels.
     */
    public function setPacklinkShipmentLabels($packlinkShipmentLabels)
    {
        $this->packlinkShipmentLabels = $packlinkShipmentLabels;
    }

    /**
     * Returns shipping drop-off point unique identifier.
     *
     * @return string Drop-off point identifier.
     */
    public function getShippingDropOffId()
    {
        return $this->shippingDropOffId;
    }

    /**
     * Sets shipping drop-off point unique identifier.
     *
     * @param string $shippingDropOffId Drop-off point identifier.
     */
    public function setShippingDropOffId($shippingDropOffId)
    {
        $this->shippingDropOffId = $shippingDropOffId;
    }

    /**
     * Returns whether associated shipment on Packlink has been deleted.
     *
     * @return bool TRUE if shipment has been deleted, otherwise FALSE.
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Sets whether associated shipment on Packlink has been deleted.
     *
     * @param bool $deleted TRUE if shipment has been deleted, otherwise FALSE.
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }
}
