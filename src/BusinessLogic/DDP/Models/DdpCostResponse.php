<?php

namespace Packlink\BusinessLogic\DDP\Models;

use Logeecom\Infrastructure\Data\DataTransferObject;
use Packlink\BusinessLogic\Http\DTO\DDP\DdpProductCost;

/**
 * Class DdpCostResponse. Per-service DDP cost result for the checkout flow.
 * Components stay separate and the adjustment is not applied here, so that a
 * cached quote can be re-composed against the merchant's current adjustment.
 *
 * Composing the presented amount is NOT platform scope, though this docblock
 * used to say it was, and three integrations each wrote the same arithmetic on
 * the strength of it. Use DdpCostComposer.
 *
 * @package Packlink\BusinessLogic\DDP\Models
 */
class DdpCostResponse extends DataTransferObject
{
    /**
     * @var string|int
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
     * Effective behavior of the owning shipping method
     * (DdpBehavior::NONE, OPTIONAL, ENFORCED or MANDATORY).
     *
     * @var string
     */
    public $effectiveBehavior;
    /**
     * @var string|null
     */
    public $ddpAdjustmentType;
    /**
     * @var float
     */
    public $ddpAdjustmentAmount = 0.0;

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'serviceId' => $this->serviceId,
            'ddpFee' => $this->ddpFee !== null ? $this->ddpFee->toArray() : null,
            'customsAndDuties' => $this->customsAndDuties !== null ? $this->customsAndDuties->toArray() : null,
            'effectiveBehavior' => $this->effectiveBehavior,
            'ddpAdjustmentType' => $this->ddpAdjustmentType,
            'ddpAdjustmentAmount' => $this->ddpAdjustmentAmount,
        );
    }
}
