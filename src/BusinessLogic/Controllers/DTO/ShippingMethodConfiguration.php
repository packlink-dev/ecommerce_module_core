<?php

namespace Packlink\BusinessLogic\Controllers\DTO;

use Logeecom\Infrastructure\Data\DataTransferObject;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingPricePolicy;

/**
 * Class ShippingMethodConfiguration.
 *
 * @package Packlink\BusinessLogic\Controllers\DTO
 */
class ShippingMethodConfiguration extends DataTransferObject
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
     * Pricing policies.
     *
     * @var ShippingPricePolicy[]
     */
    public $pricingPolicies;
    /**
     * Indicates whether to use the Packlink price if the pricing policies are out of range.
     *
     * @var bool
     */
    public $usePacklinkPriceIfNotInRange = true;
    /**
     * Show logo.
     *
     * @var bool
     */
    public $showLogo = true;
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
     * Indicates if the method is activated.
     *
     * @var bool
     */
    public $activated = false;
    /**
     * Key-value pairs of system info IDs and fixed prices in the default currency
     * (used in multi-store environments when the service currency does not match the system currency).
     *
     * @var array
     */
    public $fixedPrices;
    /**
     * Key-value pairs of system info IDs and whether they are using default pricing policy
     * (used in multi-store environments when the service currency does not match the system currency).
     *
     * @var array
     */
    public $systemDefaults;

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
            'showLogo' => $this->showLogo,
            'taxClass' => $this->taxClass,
            'isShipToAllCountries' => $this->isShipToAllCountries,
            'shippingCountries' => $this->shippingCountries,
            'usePacklinkPriceIfNotInRange' => $this->usePacklinkPriceIfNotInRange,
            'pricingPolicies' => array(),
            'activated' => $this->activated,
            'fixedPrices' => $this->fixedPrices,
            'systemDefaults' => $this->systemDefaults,
        );

        if ($this->pricingPolicies) {
            foreach ($this->pricingPolicies as $policy) {
                $result['pricingPolicies'][] = $policy->toArray();
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
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public static function fromArray(array $raw)
    {
        $result = new static();

        $result->id = $raw['id'];
        $result->activated = (bool)static::getDataValue($raw, 'activated', false);
        $result->name = $raw['name'];
        $result->showLogo = $raw['showLogo'];
        $result->taxClass = isset($raw['taxClass']) ? $raw['taxClass'] : null;
        $result->usePacklinkPriceIfNotInRange = (bool)static::getDataValue($raw, 'usePacklinkPriceIfNotInRange', false);
        $result->fixedPrices = static::getDataValue($raw, 'fixedPrices', array());
        $result->systemDefaults = static::getDataValue($raw, 'systemDefaults', array());

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

        if (!empty($raw['pricingPolicies']) && is_array($raw['pricingPolicies'])) {
            foreach ($raw['pricingPolicies'] as $policy) {
                $result->pricingPolicies[] = ShippingPricePolicy::fromArray($policy);
            }
        } else {
            $result->pricingPolicies = array();
        }

        return $result;
    }
}
