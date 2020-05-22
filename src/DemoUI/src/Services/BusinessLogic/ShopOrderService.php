<?php

namespace Packlink\DemoUI\Services\BusinessLogic;

use Packlink\BusinessLogic\Http\DTO\Draft;
use Packlink\BusinessLogic\Http\DTO\Shipment;
use Packlink\BusinessLogic\Order\Interfaces\ShopOrderService as ShopOrderServiceInterface;

/**
 * Class OrderRepositoryService
 *
 * @package Packlink\PacklinkPro\Services\BusinessLogic
 */
class ShopOrderService implements ShopOrderServiceInterface
{
    /**
     * Fetches and returns system order by its unique identifier.
     *
     * @param string $orderId $orderId Unique order id.
     *
     * @return Draft Order object.
     *
     */
    public function getOrderAndShippingData($orderId)
    {
        return new Draft();
    }

    /**
     * @inheritDoc
     */
    public function updateTrackingInfo($orderId, Shipment $shipment, array $trackingHistory)
    {
        if (!isset($shipment->trackingCodes)) {
            return;
        }
    }

    /**
     * @inheritDoc
     */
    public function updateShipmentStatus($orderId, $shippingStatus)
    {

    }
}
