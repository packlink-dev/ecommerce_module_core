<?php

namespace Packlink\BusinessLogic\Http\DTO\DDP;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class DdpProductsDetail. One per-service entry of the /pro/shipments/products response.
 *
 * @package Packlink\BusinessLogic\Http\DTO\DDP
 */
class DdpProductsDetail extends DataTransferObject
{
    /**
     * @var string|null
     */
    public $serviceId;
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

        $result->serviceId = isset($data['service_id']) ? $data['service_id'] : null;
        $products = isset($data['products']) ? $data['products'] : array();
        if (isset($products['ddp_fee'])) {
            $result->ddpFee = DdpProductCost::fromArray($products['ddp_fee']);
        }

        if (isset($products['customs_and_duties'])) {
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
            'service_id' => $this->serviceId,
            'products' => $products,
        );
    }
}
