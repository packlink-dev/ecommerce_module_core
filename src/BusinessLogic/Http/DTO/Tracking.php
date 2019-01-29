<?php

namespace Packlink\BusinessLogic\Http\DTO;

/**
 * Class Tracking.
 *
 * @package Packlink\BusinessLogic\Http\DTO
 */
class Tracking extends BaseDto
{
    /**
     * Timestamp of tracking entry.
     *
     * @var int
     */
    public $timestamp;
    /**
     * Tracking description.
     *
     * @var string
     */
    public $description;
    /**
     * Tracking city.
     *
     * @var string
     */
    public $city;

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array(
            'timestamp' => $this->timestamp,
            'description' => $this->description,
            'city' => $this->city,
        );
    }

    /**
     * Transforms raw array data to its DTO.
     *
     * @param array $raw Raw array data.
     *
     * @return static Transformed DTO object.
     */
    public static function fromArray(array $raw)
    {
        $tracking = new static();
        $tracking->timestamp = static::getValue($raw, 'timestamp');
        $tracking->description = static::getValue($raw, 'description');
        $tracking->city = static::getValue($raw, 'city');

        return $tracking;
    }

    /**
     * Transforms batch of raw array data to its DTO.
     *
     * @param array $batchRaw Raw array data.
     *
     * @return static[] Array of transformed DTO objects.
     */
    public static function fromArrayBatch(array $batchRaw)
    {
        $batchRaw = (isset($batchRaw['history']) && is_array($batchRaw['history']) ? $batchRaw['history'] : array());

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::fromArrayBatch($batchRaw);
    }
}
