<?php

namespace Logeecom\Infrastructure\ORM\Configuration\Indexes;

/**
 * Represents an indexed column in database table.
 *
 * @package Logeecom\Infrastructure\ORM\Configuration\Types
 */
abstract class Index
{
    /**
     * Property name (column name).
     *
     * @var string
     */
    private $property;

    /**
     * Index constructor.
     *
     * @param string $property Column name.
     */
    public function __construct($property)
    {
        $this->property = $property;
    }

    /**
     * Returns property name.
     *
     * @return string Property name.
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * Returns index field type.
     *
     * @return string Field type.
     */
    abstract public function getType();
}
