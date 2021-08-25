<?php

namespace Packlink\BusinessLogic\DTO;

/**
 * Base class for all data transfer objects.
 *
 * @package Packlink\BusinessLogic\Http\DTO
 *
 * @deprecated This class is replaced with DataTransferObject from infrastructure core. It is left behind for backwards
 *      compatibility.
 */
abstract class BaseDto
{
    /**
     * Transforms DTO to its array format.
     *
     * @return array DTO in array format.
     */
    abstract public function toArray();

    /**
     * Transforms raw array data to its DTO.
     *
     * @param array $raw Raw array data.
     *
     * @return static Transformed DTO object.
     * @noinspection PhpDocSignatureInspection
     */
    public static function fromArray(array $raw)
    {
        throw new \BadMethodCallException(
            'Method "fromArray" not implemented! Given array: ' . print_r($raw, true)
        );
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
        $results = array();
        foreach ($batchRaw as $item) {
            $results[] = static::fromArray($item);
        }

        return $results;
    }

    /**
     * Gets value from the array for given key.
     *
     * @param array $search An array with keys to check.
     * @param string $key Key to get value for.
     * @param mixed $default Default value if key is not present.
     *
     * @return string Value from the array for given key if key exists; otherwise, $default value.
     */
    protected static function getValue($search, $key, $default = '')
    {
        return array_key_exists($key, $search) ? $search[$key] : $default;
    }
}
