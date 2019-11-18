<?php

namespace Packlink\BusinessLogic\Controllers\DTO;

use Packlink\BusinessLogic\Http\DTO\BaseDto;
use Packlink\BusinessLogic\ShippingMethod\Models\FixedPricePolicy;
use Packlink\BusinessLogic\ShippingMethod\Models\PercentPricePolicy;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;

/**
 * Class ShippingMethodConfiguration.
 *
 * @package Packlink\BusinessLogic\Controllers\DTO
 */
class ShippingMethodConfiguration extends BaseDto
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
     * Fixed price package weight policy.
     *
     * @var FixedPricePolicy[]
     */
    public $fixedPriceByWeightPolicy = array();
    /**
     * Fixed price by cart value policy.
     *
     * @var FixedPricePolicy[]
     */
    public $fixedPriceByValuePolicy = array();
    /**
     * Shop tax class.
     *
     * @var mixed
     */
    public $taxClass;
    /**
     * Flag that denotes whether is shipping to all countries allowed.
     *
     * @var boolean
     */
    public $isShipToAllCountries = true;
    /**
     * If `isShipToAllCountries` set to FALSe than this array contains list of countries where shipping is allowed.
     *
     * @var array
     */
    public $shippingCountries = array();

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
            'taxClass' => $this->taxClass,
            'isShipToAllCountries' => $this->isShipToAllCountries,
            'shippingCountries' => $this->shippingCountries,
        );

        if ($this->pricePolicy === ShippingMethod::PRICING_POLICY_PERCENT && $this->percentPricePolicy) {
            $result['percentPricePolicy'] = $this->percentPricePolicy->toArray();
        }

        if ($this->pricePolicy === ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_WEIGHT
            && $this->fixedPriceByWeightPolicy
        ) {
            $this->setFixedPricePolicyToArray($result, 'fixedPriceByWeightPolicy');
        }

        if ($this->pricePolicy === ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_VALUE
            && $this->fixedPriceByValuePolicy
        ) {
            $this->setFixedPricePolicyToArray($result, 'fixedPriceByValuePolicy');
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
        $result = new static();

        $result->id = $raw['id'];
        $result->name = $raw['name'];
        $result->showLogo = $raw['showLogo'];
        $result->pricePolicy = $raw['pricePolicy'];

        $result->taxClass = isset($raw['taxClass']) ? $raw['taxClass'] : null;

        if (isset($raw['isShipToAllCountries']) && is_bool($raw['isShipToAllCountries'])) {
            $result->isShipToAllCountries = $raw['isShipToAllCountries'];
        } else {
            $result->isShipToAllCountries = true;
        }

        if (isset($raw['shippingCountries']) && is_array($raw['shippingCountries'])) {
            $result->shippingCountries = $raw['shippingCountries'];
        } else {
            $result->shippingCountries = array();
        }

        if ($result->pricePolicy === ShippingMethod::PRICING_POLICY_PERCENT) {
            $value = $raw['percentPricePolicy'];
            $result->percentPricePolicy = PercentPricePolicy::fromArray($value);
        }

        if ($result->pricePolicy === ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_WEIGHT) {
            self::setFixedPricingPolicyFromArray($result, $raw, 'fixedPriceByWeightPolicy');
        }

        if ($result->pricePolicy === ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_VALUE) {
            self::setFixedPricingPolicyFromArray($result, $raw, 'fixedPriceByValuePolicy');
        }

        return $result;
    }

    /**
     * Transforms fixed price policy to array and sets it to the given array.
     *
     * @param array $result Resulting array
     * @param string $type Type of the pricing policy
     */
    protected function setFixedPricePolicyToArray(array &$result, $type)
    {
        $result[$type] = array();
        /** @var FixedPricePolicy $item */
        foreach ($this->$type as $item) {
            $result[$type][] = $item->toArray();
        }
    }

    /**
     * Transforms fixed price policy from array and sets it to the given instance.
     *
     * @param static $result
     * @param array $raw
     * @param string $type
     */
    protected static function setFixedPricingPolicyFromArray($result, array $raw, $type)
    {
        $values = array();
        if (array_key_exists($type, $raw)) {
            foreach ($raw[$type] as $policy) {
                $values[] = FixedPricePolicy::fromArray($policy);
            }
        }

        $result->$type = $values;
    }
}
