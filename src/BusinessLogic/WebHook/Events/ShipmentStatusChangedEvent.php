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
