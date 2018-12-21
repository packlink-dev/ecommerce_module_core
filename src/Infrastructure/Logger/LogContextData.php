<?php

namespace Logeecom\Infrastructure\Logger;

/**
 * Class LogContextData.
 *
 * @package Logeecom\Infrastructure\Logger
 */
class LogContextData
{

    /**
     * Name of data.
     *
     * @var string
     */
    private $name;

    /**
     * Value of data.
     *
     * @var string
     */
    private $value;

    /**
     * LogContextData constructor.
     *
     * @param string $name Name of data.
     * @param string $value Value of data.
     */
    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Gets name of data.
     *
     * @return string Name of data.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets value of data.
     *
     * @return string Value of data.
     */
    public function getValue()
    {
        return $this->value;
    }
}
