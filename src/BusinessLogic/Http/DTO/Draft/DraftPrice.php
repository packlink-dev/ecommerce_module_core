<?php

namespace Packlink\BusinessLogic\Http\DTO\Draft;

use Packlink\BusinessLogic\Http\DTO\BaseDto;

/**
 * Class DraftPrice
 * @package Packlink\BusinessLogic\Http\DTO\Draft
 */
class DraftPrice extends BaseDto
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
     * Information about price of items.
     *
     * @var ItemPrice[]
     */
    public $items = array();
    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        $result = array(
            'base_price' => round($this->basePrice, 2),
            'tax_price' => round($this->taxPrice, 2),
            'total_price' => round($this->totalPrice, 2),
        );

        if (!empty($this->items)) {
            $result['items'] = array();
            foreach ($this->items as $item) {
                $result['items'][] = $item->toArray();
            }
        }

        return $result;
    }
}
