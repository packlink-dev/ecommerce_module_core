<?php

namespace Packlink\BusinessLogic\Http\DTO;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class PostalZone
 *
 * @package Packlink\BusinessLogic\Http\DTO
 */
class PostalZone extends DataTransferObject
{
    /**
     * ID of the postal zone.
     *
     * @var string
     */
    public $id;
    /**
     * Name of the postal zone.
     *
     * @var string
     */
    public $name;
    /**
     * Whether this postal zone has postal codes or not.
     *
     * @var bool
     */
    public $hasPostalCodes;
    /**
     * Two-letter ISO code.
     *
     * @var string
     */
    public $isoCode;
    /**
     * Phone prefix (ex. +49)
     *
     * @var string
     */
    public $phonePrefix;

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

        $instance->id = static::getDataValue($raw, 'id');
        $instance->name = static::getDataValue($raw, 'name');
        $instance->hasPostalCodes = static::getDataValue($raw, 'hasPostalCodes', false);
        $instance->isoCode = static::getDataValue($raw, 'isoCode');
        $instance->phonePrefix = static::getDataValue($raw, 'phonePrefix');

        return $instance;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'hasPostalCodes' => $this->hasPostalCodes,
            'isoCode' => $this->isoCode,
            'phonePrefix' => $this->phonePrefix,
        );
    }
}
