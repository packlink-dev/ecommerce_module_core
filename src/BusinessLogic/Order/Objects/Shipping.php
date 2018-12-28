<?php

namespace Packlink\BusinessLogic\Order\Objects;

/**
 * Class Shipping
 * @package Packlink\BusinessLogic\Order\Objects
 */
class Shipping
{
    /**
     * Shipping method unique identifier.
     *
     * @var string
     */
    private $id;
    /**
     * Shipping method name.
     *
     * @var string
     */
    private $name;
    /**
     * Packlink shipping service identifier.
     *
     * @var string
     */
    private $shippingServiceId;
    /**
     * Packlink shipping service name.
     *
     * @var string
     */
    private $shippingServiceName;
    /**
     * Carrier name.
     *
     * @var string
     */
    private $carrierName;

    /**
     * Returns shipping method unique identifier.
     *
     * @return string Shipping method id.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets shipping method unique identifier.
     *
     * @param string $id Shipping method id.
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Returns shipping method name.
     *
     * @return string Method name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets shipping method name.
     *
     * @param string $name Method name.
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns Packlink shipping service identifier.
     *
     * @return string Shipping service id.
     */
    public function getShippingServiceId()
    {
        return $this->shippingServiceId;
    }

    /**
     * Sets Packlink shipping service identifier.
     *
     * @param string $shippingServiceId Shipping service id.
     */
    public function setShippingServiceId($shippingServiceId)
    {
        $this->shippingServiceId = $shippingServiceId;
    }

    /**
     * Returns Packlink shipping service name.
     *
     * @return string Shipping service name.
     */
    public function getShippingServiceName()
    {
        return $this->shippingServiceName;
    }

    /**
     * Sets Packlink shipping service name.
     *
     * @param string $shippingServiceName Shipping service name.
     */
    public function setShippingServiceName($shippingServiceName)
    {
        $this->shippingServiceName = $shippingServiceName;
    }

    /**
     * Returns carrier name.
     *
     * @return string Carrier name.
     */
    public function getCarrierName()
    {
        return $this->carrierName;
    }

    /**
     * Sets carrier name.
     *
     * @param string $carrierName Carrier name.
     */
    public function setCarrierName($carrierName)
    {
        $this->carrierName = $carrierName;
    }
}
