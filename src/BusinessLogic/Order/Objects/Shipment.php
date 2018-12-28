<?php

namespace Packlink\BusinessLogic\Order\Objects;

/**
 * Class Shipment
 * @package Packlink\BusinessLogic\Order\Objects
 */
class Shipment
{
    /**
     * Packlink shipment reference number.
     *
     * @var string
     */
    private $referenceNumber;
    /**
     * Carrier tracking number.
     *
     * @var string
     */
    private $trackingNumber;
    /**
     * Weight of package in kg.
     *
     * @var float
     */
    private $weight;
    /**
     * Width of package in cm.
     *
     * @var float
     */
    private $width;
    /**
     * Height of package in cm.
     *
     * @var float
     */
    private $height;
    /**
     * Length of package in cm.
     *
     * @var float
     */
    private $length;
    /**
     * Tracking history
     *
     * @var TrackingHistory[]
     */
    private $trackingHistory = array();
    /**
     * Shipment status.
     *
     * @var string
     */
    private $status;

    /**
     * Return Packlink shipment reference number.
     *
     * @return string Reference number.
     */
    public function getReferenceNumber()
    {
        return $this->referenceNumber;
    }

    /**
     * Sets Packlink shipment reference number.
     *
     * @param string $referenceNumber Reference number.
     */
    public function setReferenceNumber($referenceNumber)
    {
        $this->referenceNumber = $referenceNumber;
    }

    /**
     * Returns carrier tracking number.
     *
     * @return string Tracking number.
     */
    public function getTrackingNumber()
    {
        return $this->trackingNumber;
    }

    /**
     * Sets carrier tracking number.
     *
     * @param string $trackingNumber Tracking number.
     */
    public function setTrackingNumber($trackingNumber)
    {
        $this->trackingNumber = $trackingNumber;
    }

    /**
     * Return weight of package in kg.
     *
     * @return float Shipment package weight.
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Sets weight of package in kg.
     *
     * @param float $weight Shipment package weight.
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * Returns width of package in cm.
     *
     * @return float Shipment package width.
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Sets width of package in cm.
     *
     * @param float $width Shipment package width.
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * Return height of package in cm.
     *
     * @return float Shipment package height.
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Sets height of package in cm.
     *
     * @param float $height Shipment package height.
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * Returns length of package in cm.
     *
     * @return float Shipment package length.
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Sets length of package in cm.
     *
     * @param float $length Shipment package length.
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * Returns tracking history.
     *
     * @return TrackingHistory[] Tracking history.
     */
    public function getTrackingHistory()
    {
        return $this->trackingHistory;
    }

    /**
     * Sets tracking history.
     *
     * @param TrackingHistory[] $trackingHistory Tracking history.
     */
    public function setTrackingHistory($trackingHistory)
    {
        $this->trackingHistory = $trackingHistory;
    }

    /**
     * Returns status.
     *
     * @return string Shipment status.
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets shipment status.
     *
     * @param string $status Shipment status.
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
}
