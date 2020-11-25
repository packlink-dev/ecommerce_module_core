<?php

namespace Packlink\BusinessLogic\Country;

use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;

/**
 * Class RegistrationCountry
 *
 * @package Packlink\BusinessLogic\Country
 */
class RegistrationCountry extends Country
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Unique class key.
     */
    const CLASS_KEY = 'registration_country';

    /**
     * Registration link for the country.
     *
     * @var string
     */
    public $registrationLink;
    /**
     * 2-letter platform country code.
     *
     * @var string
     */
    public $platformCountry;

    /**
     * Fields for this DTO.
     *
     * @var array
     */
    protected static $fields = array(
        'name',
        'code',
        'postal_code',
        'registration_link',
        'platform_country',
    );
    /**
     * Required fields for DTO to be valid.
     *
     * @var array
     */
    protected static $requiredFields = array(
        'name',
        'code',
        'postal_code',
        'registration_link',
        'platform_country',
    );

    /**
     * Transforms raw array data to its DTO.
     *
     * @param array $raw Raw array data.
     *
     * @return static Transformed DTO object.
     *
     * @throws FrontDtoValidationException
     */
    public static function fromArray(array $raw)
    {
        /** @var static $instance */
        $instance = parent::fromArray($raw);

        $instance->registrationLink = static::getDataValue($raw, 'registration_link');
        $instance->platformCountry = static::getDataValue($raw, 'platform_country');

        return $instance;
    }

    /**
     * Transforms DTO to its array format.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            array(
                'registration_link' => $this->registrationLink,
                'platform_country' => $this->platformCountry,
            )
        );
    }
}
