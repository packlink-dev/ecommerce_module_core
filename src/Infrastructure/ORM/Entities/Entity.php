<?php

namespace Logeecom\Infrastructure\ORM\Entities;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;

/**
 * Class Entity.
 *
 * @package Logeecom\Infrastructure\ORM\Entities
 */
abstract class Entity
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Identifier.
     *
     * @var int
     */
    public $id;

    /**
     * Returns full class name.
     *
     * @return string Fully qualified class name.
     */
    public static function getClassName()
    {
        return static::CLASS_NAME;
    }

    /**
     * Returns entity configuration object.
     *
     * @return EntityConfiguration Configuration object.
     */
    abstract public function getConfig();

    /**
     * Gets entity identifier.
     *
     * @return int Identifier.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets instance value for given index key.
     *
     * @param string $indexKey Name of index column.
     *
     * @return mixed Value for index.
     */
    public function getIndexValue($indexKey)
    {
        $methodName = 'get' . ucfirst($indexKey);
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        }

        $methodName = 'is' . ucfirst($indexKey);
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        }

        if (isset($this->$indexKey)) {
            return $this->$indexKey;
        }

        throw new \InvalidArgumentException('Neither field not getter found for index "' . $indexKey . '".');
    }
}
