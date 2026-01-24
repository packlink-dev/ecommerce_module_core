<?php

namespace Packlink\BusinessLogic\ShipmentDraft\Interfaces;

use Packlink\BusinessLogic\ShipmentDraft\Objects\ShipmentDraftStatus;

interface ShipmentDraftServiceInterface
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Enqueues the task for creating shipment draft for provided order id.
     *
     * @param string $orderId
     * @param bool $isDelayed
     * @param int $delayInterval
     *
     * @return void
     */
    public function enqueueCreateShipmentDraftTask($orderId, $isDelayed = false, $delayInterval = 5);

    /**
     * Returns the status of the CreateDraftTask.
     *
     * @param string $orderId
     *
     * @return ShipmentDraftStatus Entity with correct status and optional failure message.
     */
    public function getDraftStatus($orderId);

    /**
     * Checks if draft is expired.
     *
     * @param string $reference
     *
     * @return bool
     */
    public function isDraftExpired($reference);
}
