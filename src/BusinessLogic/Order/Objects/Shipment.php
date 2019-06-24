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
