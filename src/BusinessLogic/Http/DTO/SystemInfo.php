<?php

namespace Packlink\BusinessLogic\Http\DTO;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class SystemInfo
 * @package Packlink\BusinessLogic\Http\DTO
 */
class SystemInfo extends DataTransferObject
{
    /**
     * Unique, ubiquitous system identifier that can be used to identify a system that the pricing policy belongs to.
     *
     * @var string
     */
    public $systemId;

    /**
     * System name.
     *
     * @var string
     */
    public $systemName;

    /**
     * List of currencies.
     *
     * @var string[]
     */
    public $currencies;

    /**
     * @var array
     */
    public $symbols;

    /**
     * Transforms raw array data to its DTO.
     *
     * @param array $raw Raw array data.
     *
     * @return static Transformed DTO object.
     */
    public static function fromArray(array $raw)
    {
        $instance = new static();

        $instance->systemId = !empty($raw['system_id']) ? $raw['system_id'] : null;
        $instance->systemName = !empty($raw['system_name']) ? $raw['system_name'] : '';
        $instance->currencies = !empty($raw['currencies']) ? $raw['currencies'] : array();
        $instance->symbols = !empty($raw['symbols']) ? $raw['symbols'] : array();

        return $instance;
    }

    /**
     * Transforms data transfer object to array.
     *
     * @return array Array representation of data transfer object.
     */
    public function toArray()
    {
        return array(
            'system_id' => $this->systemId,
            'system_name' => $this->systemName,
            'currencies' => $this->currencies,
            'symbols' => $this->symbols,
        );
    }
}
