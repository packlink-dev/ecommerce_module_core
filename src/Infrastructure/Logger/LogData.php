<?php

namespace Logeecom\Infrastructure\Logger;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use Logeecom\Infrastructure\ORM\Entity;

/**
 * Class LogData.
 *
 * @package Logeecom\Infrastructure\Logger
 */
class LogData extends Entity
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Name of the integration.
     *
     * @var string
     */
    protected $integration;
    /**
     * Array of LogContextData.
     *
     * @var LogContextData[]
     */
    protected $context;
    /**
     * Log level.
     *
     * @var int
     */
    protected $logLevel;
    /**
     * Log timestamp.
     *
     * @var int
     */
    protected $timestamp;
    /**
     * Name of the component.
     *
     * @var string
     */
    protected $component;
    /**
     * Log message.
     *
     * @var string
     */
    protected $message;
    /**
     * Array of field names.
     *
     * @var array
     */
    protected $fields = array('id', 'integration', 'logLevel', 'timestamp', 'component', 'message');

    /**
     * LogData constructor.
     *
     * @param string $integration Name of integration.
     * @param int $logLevel Log level. Use constants in @see Logger class.
     * @param int $timestamp Log timestamp.
     * @param string $component Name of the log component.
     * @param string $message Log message.
     * @param array $context Log contexts as an array of @see LogContextData or as key value entries.
     */
    public function __construct(
        $integration = '',
        $logLevel = 0,
        $timestamp = 0,
        $component = '',
        $message = '',
        array $context = array()
    ) {
        $this->integration = $integration;
        $this->logLevel = $logLevel;
        $this->component = $component;
        $this->timestamp = $timestamp;
        $this->message = $message;
        $this->context = array();

        foreach ($context as $key => $item) {
            if (!($item instanceof LogContextData)) {
                $item = new LogContextData($key, $item);
            }

            $this->context[] = $item;
        }
    }

    /**
     * Returns entity configuration object.
     *
     * @return EntityConfiguration Configuration object.
     */
    public function getConfig()
    {
        $map = new IndexMap();
        $map->addStringIndex('integration')
            ->addIntegerIndex('logLevel')
            ->addIntegerIndex('timestamp')
            ->addStringIndex('component');

        return new EntityConfiguration($map, 'LogData');
    }

    /**
     * Transforms raw array data to this entity instance.
     *
     * @param array $data Raw array data.
     */
    public function inflate(array $data)
    {
        parent::inflate($data);

        $context = !empty($data['context']) ? $data['context'] : array();
        $this->context = array();
        foreach ($context as $key => $value) {
            $item = new LogContextData($key, $value);
            $this->context[] = $item;
        }
    }

    /**
     * Transforms entity to its array format representation.
     *
     * @return array Entity in array format.
     */
    public function toArray()
    {
        $data = parent::toArray();

        foreach ($this->context as $item) {
            $data['context'][$item->getName()] = $item->getValue();
        }

        return $data;
    }

    /**
     * Gets name of the integration.
     *
     * @return string Name of the integration.
     */
    public function getIntegration()
    {
        return $this->integration;
    }

    /**
     * Gets context data array.
     *
     * @return LogContextData[] Array of LogContextData.
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Gets log level.
     *
     * @return int
     *   Log level:
     *    - error => 0
     *    - warning => 1
     *    - info => 2
     *    - debug => 3
     */
    public function getLogLevel()
    {
        return $this->logLevel;
    }

    /**
     * Gets timestamp in seconds.
     *
     * @return int Log timestamp.
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Gets log component.
     *
     * @return string Log component.
     */
    public function getComponent()
    {
        return $this->component;
    }

    /**
     * Gets log message.
     *
     * @return string Log message.
     */
    public function getMessage()
    {
        return $this->message;
    }
}
