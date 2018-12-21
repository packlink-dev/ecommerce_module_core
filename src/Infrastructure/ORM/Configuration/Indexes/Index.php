<?php

namespace Logeecom\Infrastructure\ORM\Configuration\Indexes;

/**
 * Class Index
 * @package Logeecom\Infrastructure\ORM\Configuration\Types
 */
abstract class Index
{
    /**
     * Index number
     *
     * @var int
     */
    private $index;
    /**
     * Property name
     *
     * @var string
     */
    private $property;

    /**
     * Index constructor.
     *
     * @param $property
     * @param int $index
     */
    public function __construct($property, $index)
    {
        $this->index = $index;
        $this->property = $property;
    }

    /**
     * Returns index number
     *
     * @return int Index number
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * Returns index field type
     *
     * @return string Field type
     */
    abstract public function getType();
}
