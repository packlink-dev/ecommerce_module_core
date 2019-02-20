<?php

namespace Packlink\BusinessLogic\WebHook\Events;

use Logeecom\Infrastructure\Utility\Events\Event;

/**
 * Class TrackingInfoEvent
 * @package Packlink\BusinessLogic\WebHook\Events
 */
class TrackingInfoEvent extends Event
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
    public $referenceId;
    /**
     * Whether to set shipment status when handling this event.
     *
     * @var bool
     */
    public $updateShipmentStatus;

    /**
     * ShipmentLabelEvent constructor.
     *
     * @param string $referenceId Reference identifier.
     * @param bool $updateShipmentStatus Whether to set shipment status when handling this event.
     */
    public function __construct($referenceId, $updateShipmentStatus = true)
    {
        $this->referenceId = $referenceId;
        $this->updateShipmentStatus = $updateShipmentStatus;
    }
}
