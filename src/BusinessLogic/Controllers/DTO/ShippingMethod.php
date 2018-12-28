<?php

namespace Packlink\BusinessLogic\Controllers\DTO;

use Packlink\BusinessLogic\Http\DTO\BaseDto;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod as ShippingMethodModel;

/**
 * Class ShippingMethod
 * @package Packlink\BusinessLogic\Controllers\DTO
 */
class ShippingMethod extends BaseDto
{
    /**
     * Shipping method identifier.
     *
     * @var int
     */
    public $id;
    /**
     * Shipping method name.
     *
     * @var string
     */
    public $name;
    /**
     * Price policy code.
     *
     * @var int
     */
    public $pricePolicy;
    /**
     * Show logo.
     *
     * @var bool
     */
    public $showLogo = true;
    /**
     * Percent price policy.
     *
     * @var PercentPricePolicy
     */
    public $percentPricePolicy;
    /**
     * Fixed price policy.
     *
     * @var FixedPricePolicy[]
     */
    public $fixedPricePolicy = array();

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        $result = array(
            'id' => $this->id,
            'name' => $this->name,
            'pricePolicy' => $this->pricePolicy,
            'showLogo' => $this->showLogo,
        );

        if ($this->pricePolicy === ShippingMethodModel::PRICING_POLICY_PERCENT && $this->percentPricePolicy) {
            $result['percentPricePolicy'] = $this->percentPricePolicy->toArray();
        }

        if ($this->pricePolicy === ShippingMethodModel::PRICING_POLICY_FIXED && $this->fixedPricePolicy) {
            $result['fixedPricePolicy'] = array();
            foreach ($this->fixedPricePolicy as $item) {
                $result['fixedPricePolicy'][] = $item->toArray();
            }
        }

        return $result;
    }
}
