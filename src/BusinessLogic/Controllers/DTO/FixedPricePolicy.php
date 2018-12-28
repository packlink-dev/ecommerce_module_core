<?php

namespace Packlink\BusinessLogic\Controllers\DTO;

use Packlink\BusinessLogic\Http\DTO\BaseDto;

/**
 * Class FixedPricePolicy
 * @package Packlink\BusinessLogic\Controllers\DTO
 */
class FixedPricePolicy extends BaseDto
{
    /**
     * Price amount.
     *
     * @var float
     */
    public $amount;
    /**
     * From weight.
     *
     * @var float
     */
    public $from;
    /**
     * To weight.
     *
     * @var float
     */
    public $to;

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array(
            'amount' => round($this->amount, 2),
            'from' => round($this->from, 2),
            'to' => round($this->to, 2),
        );
    }
}
