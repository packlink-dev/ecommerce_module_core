<?php

namespace Logeecom\Infrastructure\Data;

/**
 * Class Transformer
 *
 * @package Logeecom\Infrastructure\Data
 */
class Transformer
{
    /**
     * Transforms data transfer object to different format.
     *
     * @param \Logeecom\Infrastructure\Data\DataTransferObject $transformable Object to be transformed.
     *
     * @return array Transformed result.
     *
     */
    public static function transform(DataTransferObject $transformable)
    {
        return $transformable->toArray();
    }

    /**
     * Transforms a batch of transformable object.
     *
     * @param \Logeecom\Infrastructure\Data\DataTransferObject[] $batch Batch of transformable objects.
     *
     * @return array Batch of transformed objects.
     */
    public static function batchTransform($batch)
    {
        $result = array();

        if (!is_array($batch)) {
            return $result;
        }

        foreach ($batch as $index => $transformable) {
            $result[$index] = static::transform($transformable);
        }

        return $result;
    }

    /**
     * Trims empty arrays or null values.
     *
     * @param array $data
     */
    protected static function trim(array &$data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                static::trim($data[$key]);
            }

            if ($value === null || (is_array($value) && empty($value))) {
                unset($data[$key]);
            }
        }
    }
}