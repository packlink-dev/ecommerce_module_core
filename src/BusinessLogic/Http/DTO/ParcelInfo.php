<?php

namespace Packlink\BusinessLogic\Http\DTO;

use Packlink\BusinessLogic\DTO\FrontDto;
use Packlink\BusinessLogic\DTO\FrontDtoFactory;

/**
 * Class ParcelInfo.
 *
 * @package Packlink\BusinessLogic\Http\DTO
 */
class ParcelInfo extends FrontDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Weight of the parcel.
     *
     * @var float
     */
    public $weight;
    /**
     * Length of the parcel.
     *
     * @var float
     */
    public $length;
    /**
     * Height of the parcel.
     *
     * @var float
     */
    public $height;
    /**
     * Width of the parcel.
     *
     * @var float
     */
    public $width;
    /**
     * Represent if it's the default parcel.
     *
     * @var bool
     */
    public $default;
    /**
     * Fields for this DTO. Needed for validation and transformation from/to array.
     *
     * @var array
     */
    protected static $fields = array(
        'id',
        'name',
        'weight',
        'width',
        'length',
        'height',
        'default',
        'created_at',
        'updated_at',
    );

    /**
     * Gets default parcel details.
     *
     * @return static Default parcel.
     * @noinspection PhpDocMissingThrowsInspection
     */
    public static function defaultParcel()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        /** @noinspection PhpUnhandledExceptionInspection */
        return FrontDtoFactory::get(
            'parcel',
            array(
                'weight' => 1,
                'width' => 10,
                'height' => 10,
                'length' => 10,
                'default' => true,
            )
        );
    }
}
