<?php

namespace Packlink\BusinessLogic\WebHook\Events;

use Logeecom\Infrastructure\Utility\Events\Event;

/**
 * Class ShipmentStatusChangedEvent.
 *
 * @package Packlink\BusinessLogic\WebHook\Events
 */
class ShipmentStatusChangedEvent extends Event
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Status when draft is created.
     */
    const STATUS_PENDING = 'pending';
    /**
     * Status when carrier accepted package.
     */
    const STATUS_ACCEPTED = 'processing';
    /**
     * Status when labels are ready and shipment can be fulfilled.
     */
    const STATUS_READY = 'readyForShipping';
    /**
     * Status when shipment is in transit.
     */
    const STATUS_IN_TRANSIT = 'inTransit';
    /**
     * Status when shipment is completed.
     */
    const STATUS_DELIVERED = 'delivered';
    /**
     * Packlink shipment reference identifier.
     *
     * @var string
     */
    protected $referenceId;
    /**
     * New status.
     *
     * @var string
     */
    protected $status;

    /**
     * ShipmentLabelEvent constructor.
     *
     * @param string $referenceId Reference identifier.
     * @param string $status Shipment status.
     */
    public function __construct($referenceId, $status)
    {
        $this->referenceId = $referenceId;
        $this->status = $status;
    }

    /**
     * Gets Packlink reference.
     *
     * @return string Packlink reference.
     */
    public function getReferenceId()
    {
        return $this->referenceId;
    }

    /**
     * Gets Status.
     *
     * @return string Status.
     */
    public function getStatus()
    {
        return $this->status;
    }
}
