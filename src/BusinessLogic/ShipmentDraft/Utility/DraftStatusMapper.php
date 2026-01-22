<?php

namespace Packlink\BusinessLogic\ShipmentDraft\Utility;

use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;

final class DraftStatusMapper
{
    /**
     * Maps an integration-level draft status to an internal shipment status.
     *
     * @param string $draftStatus One of DraftStatus::* constants.
     *
     * @return string ShipmentStatus::* constant to store internally.
     */
    public static function toShipmentStatus($draftStatus): string
    {
        switch ($draftStatus) {
            case DraftStatus::PROCESSING:
                return ShipmentStatus::STATUS_PENDING;

            case DraftStatus::DELAYED:
                return ShipmentStatus::STATUS_PENDING;

            case DraftStatus::COMPLETED:
                return ShipmentStatus::STATUS_ACCEPTED;

            case DraftStatus::FAILED:
                return ShipmentStatus::STATUS_CANCELLED;

            case DraftStatus::NOT_QUEUED:
            default:
                /**
                 * NOT_QUEUED means "no draft task ever started".
                 * Since ShipmentStatus cannot represent "non-existent",
                 * we default to PENDING as the safest fallback.
                 */
                return ShipmentStatus::STATUS_PENDING;
        }
    }

    /**
     * Maps an internal shipment status back to an integration-level draft status.
     *
     *
     * @param string|null $shipmentStatus ShipmentStatus::* or null.
     *
     * @return string One of DraftStatus::* constants.
     */
    public static function fromShipmentStatus($shipmentStatus): string
    {
        if ($shipmentStatus === ShipmentStatus::STATUS_ACCEPTED) {
            return DraftStatus::COMPLETED;
        }

        if ($shipmentStatus === ShipmentStatus::STATUS_CANCELLED) {
            return DraftStatus::FAILED;
        }

        if ($shipmentStatus === ShipmentStatus::STATUS_PENDING) {
            return DraftStatus::PROCESSING;
        }

        /**
         * Fallback for:
         * - null values,
         * - legacy shipment statuses,
         * - corrupted or unknown data.
         */
        return DraftStatus::NOT_QUEUED;
    }
}
