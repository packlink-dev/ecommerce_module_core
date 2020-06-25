<?php

namespace Packlink\BusinessLogic\Registration;

use Packlink\BusinessLogic\DTO\FrontDto;
use Packlink\BusinessLogic\DTO\ValidationError;
use Packlink\BusinessLogic\Utility\DtoValidator;

/**
 * Class RegistrationRequest
 *
 * @package Packlink\BusinessLogic\Registration
 */
class RegistrationRequest extends FrontDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Unique class key.
     */
    const CLASS_KEY = 'registration_request';
    /**
     * User email.
     *
     * @var string
     */
    public $email;
    /**
     * User password.
     *
     * @var string
     */
    public $password;
    /**
     * Estimated delivery volume.
     *
     * Can be one of the following: "1 - 10", "11 - 50", "51 - 100", "101 - 200", "> 200"
     *
     * @var string
     */
    public $estimatedDeliveryVolume;
    /**
     * User phone number.
     *
     * @var string
     */
    public $phone;
    /**
     * Full language code (de_DE, es_ES...).
     *
     * @var string
     */
    public $language;
    /**
     * Platform (only supported platform is "PRO").
     *
     * @var string
     */
    public $platform;
    /**
     * Country based on the platform country map.
     *
     * @var string
     */
    public $platformCountry;
    /**
     * Property based on the legal policy selection box.
     *
     * @var RegistrationLegalPolicy
     */
    public $policies;
    /**
     * Shop/integration base URL.
     *
     * @var string
     */
    public $source;
    /**
     * Selected online store ("Shopify" | "PrestaShop" etc).
     *
     * @var array
     */
    public $ecommerces;
    /**
     * Selected online marketplace ("eBay" | "Amazon" etc).
     *
     * @var array
     */
    public $marketplaces;
    /**
     * Fields for this DTO.
     *
     * @var array
     */
    protected static $fields = array(
        'email',
        'password',
        'estimated_delivery_volume',
        'phone',
        'language',
        'platform',
        'platform_country',
        'policies',
        'source',
        'ecommerces',
        'marketplaces',
    );
    /**
     * Required fields for DTO to be valid.
     *
     * @var array
     */
    protected static $requiredFields = array(
        'email',
        'password',
        'estimated_delivery_volume',
        'phone',
        'language',
        'platform',
        'platform_country',
        'policies',
        'source',
        'ecommerces',
    );
    /**
     * Allowed values for estimated delivery volume.
     *
     * @var array
     */
    private static $supportedDeliveryOptions = array(
        '1 - 10',
        '11 - 50',
        '51 - 100',
        '101 - 200',
        '> 200',
    );
    /**
     * Allowed values for language.
     *
     * @var array
     */
    private static $supportedLanguages = array(
        'en_GB',
        'de_DE',
        'es_ES',
        'fr_FR',
        'it_IT',
        'nl_NL',
    );
    /**
     * Allowed values for platform country.
     *
     * @var array
     */
    private static $supportedPlatformCountries = array(
        'ES',
        'DE',
        'FR',
        'IT',
        'UN',
    );

    /**
     * Transforms raw array data to its DTO.
     *
     * @param array $raw Raw array data.
     *
     * @return static Transformed DTO object.
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public static function fromArray(array $raw)
    {
        /** @var static $instance */
        $instance = parent::fromArray($raw);

        $instance->estimatedDeliveryVolume = static::getDataValue($raw, 'estimated_delivery_volume');
        $instance->platformCountry = static::getDataValue($raw, 'platform_country');
        $instance->policies = RegistrationLegalPolicy::fromArray($raw['policies']);

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
                'estimated_delivery_volume' => $this->estimatedDeliveryVolume,
                'platform_country' => $this->platformCountry,
                'policies' => $this->policies->toArray(),
            )
        );
    }

    /**
     * Generates validation errors for the payload.
     *
     * @param array $payload The payload in key-value format.
     * @param ValidationError[] $validationErrors The array of errors to populate.
     */
    protected static function doValidate(array $payload, array &$validationErrors)
    {
        parent::doValidate($payload, $validationErrors);

        if (!empty($payload['email']) && !DtoValidator::isEmailValid($payload['email'])) {
            static::setInvalidFieldError('email', $validationErrors, 'Field must be a valid email.');
        }

        if (!empty($payload['password']) && strlen($payload['password']) < 6) {
            static::setInvalidFieldError(
                'password',
                $validationErrors,
                'The password must be at least 6 characters long.'
            );
        }

        if (!empty($payload['estimated_delivery_volume'])
            && !in_array($payload['estimated_delivery_volume'], static::$supportedDeliveryOptions, true)
        ) {
            static::setInvalidFieldError(
                'estimated_delivery_volume',
                $validationErrors,
                'Field is not a valid delivery volume.'
            );
        }

        if (!empty($payload['phone']) && !DtoValidator::isPhoneValid($payload['phone'])) {
            static::setInvalidFieldError('phone', $validationErrors, 'Field must be a valid phone number.');
        }

        if (!empty($payload['language']) && !in_array($payload['language'], static::$supportedLanguages, true)) {
            static::setInvalidFieldError('language', $validationErrors, 'Field is not a valid language.');
        }

        if (!empty($payload['platform_country'])
            && !in_array($payload['platform_country'], static::$supportedPlatformCountries, true)
        ) {
            static::setInvalidFieldError(
                'platform_country',
                $validationErrors,
                'Field is not a valid platform country.'
            );
        }

        if (!empty($payload['source']) && filter_var($payload['source'], FILTER_VALIDATE_URL) === false) {
            static::setInvalidFieldError('source', $validationErrors, 'Field must be a valid URL.');
        }

        if (!empty($payload['platform']) && $payload['platform'] !== 'PRO') {
            static::setInvalidFieldError('platform', $validationErrors, 'Field must be set to "PRO".');
        }
    }
}
