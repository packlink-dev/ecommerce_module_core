<?php

namespace Logeecom\Infrastructure\ORM\Configuration;

/**
 * Class EntityConfiguration
 * @package Logeecom\Infrastructure\ORM
 */
class EntityConfiguration
{
    /**
     * Index map
     *
     * @var IndexMap
     */
    private $indexMap;
    /**
     * Entity type
     *
     * @var string
     */
    private $type;
    /**
     * Entity Code
     *
     * @var string
     */
    private $code;

    /**
     * EntityConfiguration constructor.
     *
     * @param IndexMap $indexMap Index map object
     * @param string $type Entity unique type
     * @param string $code Entity code
     */
    public function __construct(IndexMap $indexMap, $type, $code)
    {
        $this->indexMap = $indexMap;
        $this->type = $type;
        $this->code = $code;
    }

    /**
     * Returns index map
     *
     * @return IndexMap Index map
     */
    public function getIndexMap()
    {
        return $this->indexMap;
    }

    /**
     * Returns type
     *
     * @return string Entity type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns code
     *
     * @return string Entity code
     */
    public function getCode()
    {
        return $this->code;
    }
}
