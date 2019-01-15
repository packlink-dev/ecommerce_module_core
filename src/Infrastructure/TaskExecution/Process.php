<?php

namespace Logeecom\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\Indexes\StringIndex;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use Logeecom\Infrastructure\ORM\Entity;
use Logeecom\Infrastructure\TaskExecution\Interfaces\Runnable;

/**
 * Class Process
 * @package Logeecom\Infrastructure\ORM\Entities
 */
class Process extends Entity
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Unique identifier.
     *
     * @var string
     */
    protected $guid;
    /**
     * Runnable instance.
     *
     * @var Runnable
     */
    protected $runner;

    /**
     * Transforms raw array data to this entity instance.
     *
     * @param array $data Raw array data with keys 'id', 'guid' and 'runner'.
     *
     * @return static Transformed entity object.
     *
     * @throws \InvalidArgumentException In case when @see $data does not have all needed keys.
     */
    public static function fromArray(array $data)
    {
        if (!isset($data['guid'], $data['runner'])) {
            throw new \InvalidArgumentException('Data array needs to have "guid" and "runner" keys.');
        }

        /** @var self $instance */
        $instance = parent::fromArray($data);
        $instance->setGuid($data['guid']);
        $instance->setRunner(unserialize($data['runner']));

        return $instance;
    }

    /**
     * Transforms entity to its array format representation.
     *
     * @return array Entity in array format.
     */
    public function toArray()
    {
        $data = parent::toArray();
        $data['guid'] = $this->getGuid();
        $data['runner'] = serialize($this->getRunner());

        return $data;
    }

    /**
     * Returns entity configuration object
     *
     * @return EntityConfiguration
     */
    public function getConfig()
    {
        $indexMap = new IndexMap();
        $indexMap->addIndex(new StringIndex('guid'));

        return new EntityConfiguration($indexMap, 'Process');
    }

    /**
     * Gets Guid.
     *
     * @return string Guid.
     */
    public function getGuid()
    {
        return $this->guid;
    }

    /**
     * Sets Guid.
     *
     * @param string $guid Guid.
     */
    public function setGuid($guid)
    {
        $this->guid = $guid;
    }

    /**
     * Gets Runner.
     *
     * @return Runnable Runner.
     */
    public function getRunner()
    {
        return $this->runner;
    }

    /**
     * Sets Runner.
     *
     * @param Runnable $runner Runner.
     */
    public function setRunner(Runnable $runner)
    {
        $this->runner = $runner;
    }
}
