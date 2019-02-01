<?php

namespace Packlink\BusinessLogic\Controllers\DTO;

use Packlink\BusinessLogic\Http\DTO\BaseDto;

/**
 * Class PercentPricePolicy
 * @package Packlink\BusinessLogic\Controllers\DTO
 */
class PercentPricePolicy extends BaseDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Percent amount.
     *
     * @var float
     */
    public $amount;
    /**
     * Increase price.
     *
     * @var bool
     */
    public $increase;

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array(
            'amount' => round($this->amount, 2),
            'increase' => $this->increase,
        );
    }
}
