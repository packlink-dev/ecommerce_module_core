<?php

namespace Packlink\BusinessLogic\Http\DTO\DDP;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class ShipmentProductsRequest. Wraps exactly ONE shipment entry for /pro/shipments/products.
 *
 * The endpoint accepts an array, but MUST NOT be batched: with more than one entry the response
 * attaches each result to the wrong `packlink_reference`, carries no `service_id`, and ignores request
 * order, so results cannot be matched back to the shipment that produced them (verified 2026-07-30).
 * A batched call therefore returns well-formed, plausible, silently wrong prices.
 *
 * This is not a limitation worth working around: DDP cost is a function of the goods and the route,
 * not the carrier service, so one call answers for every DDP-capable service on a route.
 *
 * @package Packlink\BusinessLogic\Http\DTO\DDP
 */
class ShipmentProductsRequest extends DataTransferObject
{
    /**
     * @var ShipmentProductsRequestItem
     */
    public $item;

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'shipments' => array(
                $this->item->toArray(),
            ),
        );
    }
}
