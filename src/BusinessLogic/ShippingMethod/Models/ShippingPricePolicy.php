<?php

namespace Packlink\BusinessLogic\ShippingMethod\Models;

use Packlink\BusinessLogic\DTO\FrontDto;
use Packlink\BusinessLogic\DTO\ValidationError;

/**
 * Class ShippingPricePolicy.
 *
 * @package Packlink\BusinessLogic\ShippingMethod\Models
 */
class ShippingPricePolicy extends FrontDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Unique class key.
     */
    const CLASS_KEY = 'shipping_price_policy';
    /**
     * Range type: by order total price.
     */
    const RANGE_PRICE = 0;
    /**
     * Range type: by package total weight.
     */
    const RANGE_WEIGHT = 1;
    /**
     * Range type: by order total price and package total weight.
     */
    const RANGE_PRICE_AND_WEIGHT = 2;
    /**
     * Applied price policy: use Packlink prices.
     */
    const POLICY_PACKLINK = 0;
    /**
     * Applied price policy: adjust Packlink prices by specified percent.
     */
    const POLICY_PACKLINK_ADJUST = 1;
    /**
     * Applied price policy: use specified fixed price.
     */
    const POLICY_FIXED_PRICE = 2;
    /**
     * The range type. Use constants of this class to indicate a value.
     *
     * @var int
     */
    public $rangeType;
    /**
     * Weight of package in kg from which policy is applied (lower boundary).
     *
     * @var float|null
     */
    public $fromWeight;
    /**
     * Weight of package in kg to which policy is applied (upper boundary).
     *
     * @var float|null
     */
    public $toWeight;
    /**
     * Cart value in EUR from which policy is applied (lower boundary).
     *
     * @var float|null
     */
    public $fromPrice;
    /**
     * Cart value in EUR to which policy is applied (upper boundary).
     *
     * @var float|null
     */
    public $toPrice;
    /**
     * The pricing policy to apply. Use constants from this class to indicate a value.
     *
     * @var int
     */
    public $pricingPolicy;
    /**
     * Indicates whether to increase or decrease price for specified percent amount.
     *
     * @var bool
     */
    public $increase;
    /**
     * Price increase/decrease percent if adjust policy is used.
     *
     * @var float|null
     */
    public $changePercent;
    /**
     * Fixed price in EUR if fixed price policy is used.
     *
     * @var float|null
     */
    public $fixedPrice;
    /**
     * Unique, ubiquitous system identifier that can be used to identify a system that the pricing policy belongs to.
     *
     * @var string|null
     */
    public $systemId;
    /**
     * Fields for this DTO. Needed for validation and transformation from/to array.
     *
     * @var array
     */
    protected static $fields = array(
        'increase',
    );

    /**
     * Transforms raw array data to this entity instance.
     *
     * @param array $data Raw array data.
     *
     * @return ShippingPricePolicy Transformed entity object.
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public static function fromArray(array $data)
    {
        if (isset($data['range_type'])) {
            $data['range_type'] = (int) $data['range_type'];
        }

        if (isset($data['pricing_policy'])) {
            $data['pricing_policy'] = (int) $data['pricing_policy'];
        }

        $result = parent::fromArray($data);

        $result->rangeType = (int)static::getDataValue($data, 'range_type');
        $result->fromWeight = static::getDataValue($data, 'from_weight', null);
        $result->toWeight = static::getDataValue($data, 'to_weight', null);
        $result->fromPrice = static::getDataValue($data, 'from_price', null);
        $result->toPrice = static::getDataValue($data, 'to_price', null);
        $result->pricingPolicy = (int)static::getDataValue($data, 'pricing_policy');
        $result->increase = static::getDataValue($data, 'increase', false);
        $result->changePercent = static::getDataValue($data, 'change_percent', null);
        $result->fixedPrice = static::getDataValue($data, 'fixed_price', null);
        $result->systemId = static::getDataValue($data, 'system_id', null);

        return $result;
    }

    /**
     * Transforms entity to its array format representation.
     *
     * @return array Entity in array format.
     */
    public function toArray()
    {
        return array(
            'range_type' => $this->rangeType,
            'from_weight' => is_null($this->fromWeight) ? null : round($this->fromWeight, 2),
            'to_weight' => is_null($this->toWeight) ? null : round($this->toWeight, 2),
            'from_price' => is_null($this->fromPrice) ? null : round($this->fromPrice, 2),
            'to_price' => is_null($this->toPrice) ? null : round($this->toPrice, 2),
            'pricing_policy' => $this->pricingPolicy,
            'increase' => $this->increase,
            'change_percent' => is_null($this->changePercent) ? null : round($this->changePercent, 2),
            'fixed_price' => is_null($this->fixedPrice) ? null : round($this->fixedPrice, 2),
            'system_id' => $this->systemId,
        );
    }

    /**
     * Generates validation errors for the payload.
     *
     * @param array $payload The payload in key-value format.
     * @param ValidationError[] $validationErrors The array of errors to populate.
     */
    protected static function doValidate(array $payload, array &$validationErrors)
    {
        parent::doValidate($payload, $validationErrors);

        if (static::validateRequiredField($payload, 'range_type', $validationErrors)) {
            if (($payload['range_type'] === self::RANGE_PRICE
                    || $payload['range_type'] === self::RANGE_PRICE_AND_WEIGHT)
                && static::validateRequiredField($payload, 'from_price', $validationErrors)
            ) {
                static::validateRange($payload, 'from_price', 'to_price', $validationErrors);
            }

            if (($payload['range_type'] === self::RANGE_WEIGHT
                    || $payload['range_type'] === self::RANGE_PRICE_AND_WEIGHT)
                && static::validateRequiredField($payload, 'from_weight', $validationErrors)
            ) {
                static::validateRange($payload, 'from_weight', 'to_weight', $validationErrors);
            }
        }

        if (static::validateRequiredField($payload, 'pricing_policy', $validationErrors)) {
            if ($payload['pricing_policy'] === self::POLICY_PACKLINK_ADJUST) {
                if (static::validateRequiredField($payload, 'change_percent', $validationErrors)) {
                    $changePercent = (float)$payload['change_percent'];
                    if ($changePercent <= 0 || ($payload['increase'] === false && $changePercent > 99.99)) {
                        static::setInvalidFieldError('change_percent', $validationErrors);
                    }
                }
            } elseif ($payload['pricing_policy'] === self::POLICY_FIXED_PRICE
                && static::validateRequiredField($payload, 'fixed_price', $validationErrors)
                && (float)$payload['fixed_price'] < 0
            ) {
                static::setInvalidFieldError('fixed_price', $validationErrors);
            }
        }
    }

    /**
     * Checks whether the array element with the given key is set.
     *
     * @param array $payload The payload in key-value format.
     * @param string $key The field key.
     *
     * @return bool
     */
    protected static function requiredFieldSet(array $payload, $key)
    {
        return array_key_exists($key, $payload);
    }

    /**
     * Validates range.
     *
     * @param array $payload
     * @param string $lowerBoundaryKey
     * @param string $upperBoundaryKey
     * @param ValidationError[] $validationErrors
     */
    protected static function validateRange(array $payload, $lowerBoundaryKey, $upperBoundaryKey, &$validationErrors)
    {
        $lowerBoundary = (float)$payload[$lowerBoundaryKey];
        if ($lowerBoundary < 0) {
            static::setInvalidFieldError($lowerBoundaryKey, $validationErrors);
        } elseif (isset($payload[$upperBoundaryKey]) && $lowerBoundary >= (float)$payload[$upperBoundaryKey]) {
            static::setInvalidFieldError($upperBoundaryKey, $validationErrors);
        }
    }
}
