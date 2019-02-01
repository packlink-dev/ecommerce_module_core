<?php

namespace Packlink\BusinessLogic\Controllers\DTO;

use Packlink\BusinessLogic\Http\DTO\BaseDto;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;

/**
 * Class ShippingMethodConfiguration.
 *
 * @package Packlink\BusinessLogic\Controllers\DTO
 */
class ShippingMethodConfiguration extends BaseDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

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

        if ($this->pricePolicy === ShippingMethod::PRICING_POLICY_PERCENT && $this->percentPricePolicy) {
            $result['percentPricePolicy'] = $this->percentPricePolicy->toArray();
        }

        if ($this->pricePolicy === ShippingMethod::PRICING_POLICY_FIXED && $this->fixedPricePolicy) {
            $result['fixedPricePolicy'] = array();
            foreach ($this->fixedPricePolicy as $item) {
                $result['fixedPricePolicy'][] = $item->toArray();
            }
        }

        return $result;
    }

    /**
     * Creates ShippingMethodConfiguration object instance from an array of raw data.
     *
     * @param array $raw
     *
     * @return ShippingMethodConfiguration
     */
    public static function fromArray(array $raw)
    {
        $result = new self();

        $result->id = $raw['id'];
        $result->name = $raw['name'];
        $result->showLogo = $raw['showLogo'];
        $result->pricePolicy = $raw['pricePolicy'];

        if ($result->pricePolicy === ShippingMethod::PRICING_POLICY_PERCENT) {
            $value = $raw['percentPricePolicy'];
            $result->percentPricePolicy = new PercentPricePolicy();
            $result->percentPricePolicy->amount = $value['amount'];
            $result->percentPricePolicy->increase = $value['increase'];
        }

        if ($result->pricePolicy === ShippingMethod::PRICING_POLICY_FIXED) {
            $value = $raw['fixedPricePolicy'];
            $result->fixedPricePolicy = array();
            foreach ($value as $policy) {
                $fixedPolicy = new FixedPricePolicy();
                $fixedPolicy->amount = $policy['amount'];
                $fixedPolicy->from = $policy['from'];
                $fixedPolicy->to = $policy['to'];

                $result->fixedPricePolicy[] = $fixedPolicy;
            }
        }

        return $result;
    }
}
