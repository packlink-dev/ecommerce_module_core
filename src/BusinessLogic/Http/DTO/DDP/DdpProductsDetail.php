<?php

namespace Packlink\BusinessLogic\Http\DTO\DDP;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class DdpProductsDetail. The single per-shipment entry of a /pro/shipments/products response.
 *
 * The response carries `packlink_reference`, never `service_id`, so there is nothing to correlate a
 * result to a requested service by. That does not matter because the request is never batched (see
 * ShipmentProductsRequest) -- the one entry returned belongs to the one entry sent.
 *
 * A service that does not support DDP on the requested route simply omits `ddp_fee` entirely. That is
 * a normal answer, not an error.
 *
 * @package Packlink\BusinessLogic\Http\DTO\DDP
 */
class DdpProductsDetail extends DataTransferObject
{
    /**
     * @var DdpProductCost|null
     */
    public $ddpFee;
    /**
     * @var DdpProductCost|null
     */
    public $customsAndDuties;

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data)
    {
        $result = new static();

        $products = isset($data['products']) && is_array($data['products']) ? $data['products'] : array();
        if (isset($products['ddp_fee']) && is_array($products['ddp_fee'])) {
            $result->ddpFee = DdpProductCost::fromArray($products['ddp_fee']);
        }

        if (isset($products['customs_and_duties']) && is_array($products['customs_and_duties'])) {
            $result->customsAndDuties = DdpProductCost::fromArray($products['customs_and_duties']);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        $products = array();
        if ($this->ddpFee !== null) {
            $products['ddp_fee'] = $this->ddpFee->toArray();
        }

        if ($this->customsAndDuties !== null) {
            $products['customs_and_duties'] = $this->customsAndDuties->toArray();
        }

        return array(
            'products' => $products,
        );
    }
}
