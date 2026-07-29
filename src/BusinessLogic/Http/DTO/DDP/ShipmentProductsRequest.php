<?php

namespace Packlink\BusinessLogic\Http\DTO\DDP;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class ShipmentProductsRequest. Batches all eligible services into one
 * /pro/shipments/products call.
 *
 * @package Packlink\BusinessLogic\Http\DTO\DDP
 */
class ShipmentProductsRequest extends DataTransferObject
{
    /**
     * @var ShipmentProductsRequestItem[]
     */
    public $items = array();

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        $shipments = array();
        foreach ($this->items as $item) {
            $shipments[] = $item->toArray();
        }

        return array(
            'shipments' => $shipments,
        );
    }
}
