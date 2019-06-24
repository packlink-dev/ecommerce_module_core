<?php

namespace Packlink\BusinessLogic\Http\DTO\Draft;

use Packlink\BusinessLogic\Http\DTO\BaseDto;

/**
 * Class DraftItem
 * @package Packlink\BusinessLogic\Http\DTO\Draft
 */
class DraftItem extends BaseDto
{
    /**
     * Item price.
     *
     * @var float
     */
    public $price;
    /**
     * Item title.
     *
     * @var string
     */
    public $title;
    /**
     * Item main image URL.
     *
     * @var string
     */
    public $pictureUrl;
    /**
     * Item quantity.
     *
     * @var int
     */
    public $quantity;
    /**
     * Category name
     *
     * @var string
     */
    public $categoryName;

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array(
            'price' => round($this->price, 2),
            'title' => $this->title,
            'picture_url' => $this->pictureUrl,
            'quantity' => $this->quantity,
            'category_name' => $this->categoryName,
        );
    }
}
