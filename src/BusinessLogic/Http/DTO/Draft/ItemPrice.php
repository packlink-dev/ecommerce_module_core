<?php

namespace Packlink\BusinessLogic\Http\DTO\Draft;

use Packlink\BusinessLogic\Http\DTO\BaseDto;

/**
 * Class ItemPrice
 * @package Packlink\BusinessLogic\Http\DTO\Draft
 */
class ItemPrice extends BaseDto
{
    /**
     * Value of item in EUR without taxes.
     *
     * @var float
     */
    public $basePrice;
    /**
     * Value of taxes by item in EUR.
     *
     * @var float
     */
    public $taxPrice;
    /**
     * Total value of item in EUR.
     *
     * @var float
     */
    public $totalPrice;
    /**
     * Information about concepts of item.
     *
     * @var string
     */
    public $concept;
    /**
     * Contains additional information about each concept
     *
     * @var array
     */
    public $extraData = array();

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array(
            'base_price' => round($this->basePrice, 2),
            'tax_price' => round($this->taxPrice, 2),
            'total_price' => round($this->totalPrice, 2),
            'concept' => $this->concept,
            'extra_data' => $this->extraData,
        );
    }
}
