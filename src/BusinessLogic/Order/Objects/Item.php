<?php

namespace Packlink\BusinessLogic\Order\Objects;

/**
 * Class Item
 * @package Packlink\BusinessLogic\Order\Objects
 */
class Item
{
    /**
     * Item product unique identifier.
     *
     * @var string
     */
    private $id;
    /**
     * Item product SKU.
     *
     * @var string
     */
    private $sku;
    /**
     * Item base price.
     *
     * @var float
     */
    private $price;
    /**
     * Item total price.
     *
     * @var float
     */
    private $totalPrice;
    /**
     * Item title.
     *
     * @var string
     */
    private $title;
    /**
     * Item concept.
     *
     * @var string
     */
    private $concept;
    /**
     * Item main image URL.
     *
     * @var string
     */
    private $pictureUrl;
    /**
     * Item quantity.
     *
     * @var int
     */
    private $quantity;
    /**
     * Category name
     *
     * @var string
     */
    private $categoryName;
    /**
     * Weight of item in kg.
     *
     * @var float
     */
    private $weight;
    /**
     * Width of item in cm.
     *
     * @var float
     */
    private $width;
    /**
     * Height of item in cm.
     *
     * @var float
     */
    private $height;
    /**
     * Length of item in cm.
     *
     * @var float
     */
    private $length;
    /**
     * Other extra item properties in key => value format
     *
     * @var array
     */
    private $extraProperties = array();

    /**
     * Returns item product unique identifier.
     *
     * @return string Item product unique identifier.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets item product unique identifier.
     *
     * @param string $id Item product unique identifier.
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Returns item product SKU.
     *
     * @return string Product SKU.
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * Sets item product SKU.
     *
     * @param string $sku Product SKU.
     */
    public function setSku($sku)
    {
        $this->sku = $sku;
    }

    /**
     * Returns item base price.
     *
     * @return float Item price.
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Sets item base price.
     *
     * @param float $price Item price.
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * Returns item title.
     *
     * @return string Item title.
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets item title.
     *
     * @param string $title Item title.
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns item main picture URL.
     *
     * @return string Picture URL.
     */
    public function getPictureUrl()
    {
        return $this->pictureUrl;
    }

    /**
     * Sets item main picture URL.
     *
     * @param string $pictureUrl Picture URL.
     */
    public function setPictureUrl($pictureUrl)
    {
        $this->pictureUrl = $pictureUrl;
    }

    /**
     * Returns ordered item quantity.
     *
     * @return int Item quantity.
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Sets ordered item quantity.
     *
     * @param int $quantity Item quantity.
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * Returns item category name.
     *
     * @return string Category name.
     */
    public function getCategoryName()
    {
        return $this->categoryName;
    }

    /**
     * Sets item category name.
     *
     * @param string $categoryName Category name.
     */
    public function setCategoryName($categoryName)
    {
        $this->categoryName = $categoryName;
    }

    /**
     * Returns item total price.
     *
     * @return float Total price.
     */
    public function getTotalPrice()
    {
        return $this->totalPrice;
    }

    /**
     * Sets item total price.
     *
     * @param float $totalPrice Total price.
     */
    public function setTotalPrice($totalPrice)
    {
        $this->totalPrice = $totalPrice;
    }

    /**
     * Returns item concept.
     *
     * @return string Item concept.
     */
    public function getConcept()
    {
        return $this->concept;
    }

    /**
     * Sets item concept.
     *
     * @param string $concept Item concept.
     */
    public function setConcept($concept)
    {
        $this->concept = $concept;
    }

    /**
     * Return weight of item in kg.
     *
     * @return float Item weight.
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Sets weight of item in kg.
     *
     * @param float $weight Item weight.
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * Returns width of item in cm.
     *
     * @return float Item width.
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Sets width of item in cm.
     *
     * @param float $width Item width.
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * Return height of item in cm.
     *
     * @return float Item height.
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Sets height of item in cm.
     *
     * @param float $height Item height.
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * Returns length of item in cm.
     *
     * @return float Item length.
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Sets length of item in cm.
     *
     * @param float $length Item length.
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * Returns extra properties of the item.
     *
     * @return array Map of item properties.
     */
    public function getExtraProperties()
    {
        return $this->extraProperties;
    }

    /**
     * Sets extra properties of the item.
     *
     * @param array $extraProperties Map of item properties.
     */
    public function setExtraProperties($extraProperties)
    {
        $this->extraProperties = $extraProperties;
    }
}
