<?php

namespace Packlink\BusinessLogic\Http\DTO\Draft;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class DraftItem
 * @package Packlink\BusinessLogic\Http\DTO\Draft
 */
class DraftItem extends DataTransferObject
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
