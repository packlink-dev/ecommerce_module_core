<?php

namespace Packlink\BusinessLogic\WebHook\Events;

use Logeecom\Infrastructure\Utility\Events\Event;

/**
 * Class ShippingLabelEvent
 * @package Packlink\BusinessLogic\WebHook\Events
 */
class ShippingLabelEvent extends Event
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
     * ShippingLabelEvent constructor.
     *
     * @param string $referenceId Reference identifier.
     */
    public function __construct($referenceId)
    {
        $this->referenceId = $referenceId;
    }
}
