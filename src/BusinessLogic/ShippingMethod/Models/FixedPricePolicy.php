<?php

namespace Packlink\BusinessLogic\ShippingMethod\Models;

/**
 * Class FixedPricePolicy.
 * Used for ShippingMethod when fixed price policy by weight or price is applied.
 *
 * @package Packlink\BusinessLogic\ShippingMethod\Models
 */
class FixedPricePolicy
{
    /**
     * Weight of package in kg or cart value in EUR from which policy is applied (lower boundary).
     *
     * @var float
     */
    public $from;
    /**
     * Weight of package in kg or cart value in EUR to which policy is applied (upper boundary).
     *
     * @var float
     */
    public $to;
    /**
     * Price in EUR.
     *
     * @var float
     */
    public $amount;

    /**
     * FixedPricePolicy constructor.
     *
     * @param float $from Weight of package in kg or cart value in EUR from which policy is applied (lower boundary).
     * @param float $to Weight of package in kg or cart value in EUR to which policy is applied (upper boundary).
     * @param float $amount Price in EUR.
     */
    public function __construct($from, $to, $amount)
    {
        $this->from = $from;
        $this->to = $to;
        $this->amount = $amount;
    }

    /**
     * Transforms raw array data to this entity instance.
     *
     * @param array $data Raw array data.
     *
     * @return static Transformed entity object.
     */
    public static function fromArray($data)
    {
        return new static($data['from'], $data['to'], $data['amount']);
    }

    /**
     * Transforms entity to its array format representation.
     *
     * @return array Entity in array format.
     */
    public function toArray()
    {
        return array(
            'from' => round($this->from, 2),
            'to' => round($this->to, 2),
            'amount' => round($this->amount, 2),
        );
    }
}
