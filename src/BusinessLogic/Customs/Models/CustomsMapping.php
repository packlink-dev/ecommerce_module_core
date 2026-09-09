<?php

namespace Packlink\BusinessLogic\Customs\Models;

use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\DTO\FrontDto;
use Packlink\BusinessLogic\Language\Translator;

/**
 * Class CustomsMapping
 *
 * @package Packlink\BusinessLogic\Customs
 */
class CustomsMapping extends FrontDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Unique class key.
     */
    const CLASS_KEY = 'customs_mapping';
    /**
     * Receiver user types, as the Packlink receiver.user_type enum spells them.
     */
    const USER_TYPE_PRIVATE_PERSON = 'PRIVATE_PERSON';
    const USER_TYPE_COMPANY = 'COMPANY';
    /**
     * Fields whose value is a Packlink enum token or an ISO code, and is therefore
     * case-normalized on the way in.
     *
     * @var array
     */
    protected static $upperCaseFields = array(
        'default_reason',
        'default_receiver_user_type',
        'default_country',
    );
    /**
     * Allowed reason_for_export values (Packlink customs schema).
     *
     * @var array
     */
    protected static $reasons = array(
        'PURCHASE_OR_SALE',
        'PERSONAL_BELONGINGS',
        'SAMPLE',
        'DOCUMENTS',
        'RETURN',
    );
    /**
     * Allowed receiver user types (Packlink customs schema).
     *
     * @var array
     */
    protected static $receiverUserTypes = array(
        self::USER_TYPE_PRIVATE_PERSON,
        self::USER_TYPE_COMPANY,
    );
    /**
     * @var string
     */
    public $defaultReason;
    /**
     * @var string
     */
    public $defaultSenderTaxId;
    /**
     * @var string
     */
    public $defaultReceiverUserType;
    /**
     * @var string
     */
    public $defaultReceiverTaxId;
    /**
     * @var string
     */
    public $defaultTariffNumber;
    /**
     * @var string
     */
    public $defaultCountry;
    /**
     * @var string
     */
    public $mappingReceiverTaxId;
    /**
     * @var string
     */
    public $mappingTariffNumber;
    /**
     * @var string
     */
    public $mappingCompanyVat;
    /**
     * @var string
     */
    public $mappingCountryOfOrigin;
    /**
     * Fields for this DTO.
     *
     * @var array
     */
    protected static $fields = array(
        'default_reason',
        'default_sender_tax_id',
        'default_receiver_user_type',
        'default_receiver_tax_id',
        'default_tariff_number',
        'default_country',
        'mapping_receiver_tax_id',
        'mapping_tariff_number',
        'mapping_company_vat',
        'mapping_country_of_origin',
    );
    /**
     * Required fields for DTO to be valid.
     *
     * @var array
     */
    protected static $requiredFields = array(
        'default_reason',
        'default_sender_tax_id',
        'default_receiver_user_type',
        // The schema requires a tariff number and a country of origin on EVERY inventory item.
        // A platform mapping only says which product attribute to read - it resolves per item and
        // silently yields nothing for a product that has not filled it in. The default is the only
        // guarantee that every item resolves, so it is required even when a mapping exists.
        'default_tariff_number',
        'default_country',
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
    public static function fromArray(array $raw): CustomsMapping
    {
        $raw = static::normalize($raw);

        static::validate($raw);

        return static::hydrate($raw);
    }

    /**
     * Hydrates a mapping from stored configuration WITHOUT validating it.
     *
     * Mappings persisted before a rule was added are never re-validated, and a platform may build
     * this DTO programmatically past all validation. Validating on read would throw
     * FrontDtoValidationException inside an unrelated page render, so the read path is lenient and
     * isConfigured() answers readiness instead.
     *
     * @param array $raw Raw array data.
     *
     * @return static Transformed DTO object.
     */
    public static function fromStoredArray(array $raw)
    {
        return static::hydrate(static::normalize($raw));
    }

    /**
     * Indicates whether this mapping holds everything a customs invoice needs.
     *
     * This is the readiness predicate the DDP banner and the DDP cost service share. It answers the
     * question save-time validation cannot: whether an ALREADY STORED mapping - or one a platform
     * assembled by hand - is usable.
     *
     * @return bool TRUE when a customs invoice can be assembled from this configuration.
     */
    public function isConfigured()
    {
        $validationErrors = array();
        $payload = $this->toArray();

        static::validateRequiredFields($payload, $validationErrors);
        static::doValidate($payload, $validationErrors);

        return empty($validationErrors);
    }

    /**
     * Upper-cases the enum and ISO-code fields, so a payload saved before the form carried the
     * Packlink-cased tokens (or typed by a platform in any casing) still validates and is stored
     * the way the API spells it.
     *
     * @param array $raw Raw array data.
     *
     * @return array Normalized data.
     */
    protected static function normalize(array $raw)
    {
        foreach (static::$upperCaseFields as $field) {
            if (isset($raw[$field]) && is_string($raw[$field])) {
                $raw[$field] = strtoupper(trim($raw[$field]));
            }
        }

        return $raw;
    }

    /**
     * @param array $raw Normalized array data.
     *
     * @return static
     */
    protected static function hydrate(array $raw)
    {
        $mapping = new self();
        $mapping->defaultReason = static::getDataValue($raw,'default_reason');
        $mapping->defaultSenderTaxId = static::getDataValue($raw,'default_sender_tax_id');
        $mapping->defaultReceiverUserType = static::getDataValue($raw,'default_receiver_user_type');
        $mapping->defaultReceiverTaxId = static::getDataValue($raw,'default_receiver_tax_id');
        $mapping->defaultTariffNumber = static::getDataValue($raw,'default_tariff_number');
        $mapping->defaultCountry = static::getDataValue($raw,'default_country');
        $mapping->mappingReceiverTaxId = static::getDataValue($raw,'mapping_receiver_tax_id');
        $mapping->mappingTariffNumber = static::getDataValue($raw,'mapping_tariff_number');
        $mapping->mappingCompanyVat = static::getDataValue($raw,'mapping_company_vat');
        $mapping->mappingCountryOfOrigin = static::getDataValue($raw,'mapping_country_of_origin');

        return $mapping;
    }

    /**
     * Transforms DTO to its array format.
     *
     * @return array DTO in array format.
     */
    public function toArray(): array
    {
        return array(
            'default_reason' => $this->defaultReason,
            'default_sender_tax_id' => $this->defaultSenderTaxId,
            'default_receiver_user_type' => $this->defaultReceiverUserType,
            'default_receiver_tax_id' => $this->defaultReceiverTaxId,
            'default_tariff_number' => $this->defaultTariffNumber,
            'default_country' => $this->defaultCountry,
            'mapping_receiver_tax_id' => $this->mappingReceiverTaxId,
            'mapping_tariff_number' => $this->mappingTariffNumber,
            'mapping_company_vat' => $this->mappingCompanyVat,
            'mapping_country_of_origin' => $this->mappingCountryOfOrigin,
        );
    }

    protected static function doValidate(array $payload, array &$validationErrors)
    {
        parent::doValidate($payload, $validationErrors);

        static::validateEnum($payload, 'default_reason', static::$reasons, $validationErrors);
        static::validateEnum($payload, 'default_receiver_user_type', static::$receiverUserTypes, $validationErrors);

        $country = static::getDataValue($payload, 'default_country', '');
        if ($country !== '' && !preg_match('/^[A-Z]{2}$/', $country)) {
            static::setInvalidFieldError('default_country', $validationErrors);
        }

        // default_tariff_number is optional (not in $requiredFields); validate its
        // format only when a value is actually supplied.
        $tariffNumber = static::getDataValue($payload, 'default_tariff_number', '');
        if ($tariffNumber !== '' && !preg_match('/^[0-9]{6,8}$/', $tariffNumber)) {
            static::setInvalidFieldError(
                'default_tariff_number',
                $validationErrors,
                Translator::translate('validation.invalidField')
            );
        }

    }

    /**
     * Adds an invalid-field error when a set value is outside the allowed set.
     *
     * @param array $payload The payload.
     * @param string $field The field code.
     * @param array $allowed Allowed values.
     * @param \Packlink\BusinessLogic\DTO\ValidationError[] $validationErrors The array of errors to populate.
     */
    protected static function validateEnum(array $payload, $field, array $allowed, array &$validationErrors)
    {
        $value = static::getDataValue($payload, $field, '');

        if ($value !== '' && !in_array($value, $allowed, true)) {
            static::setInvalidFieldError($field, $validationErrors);
        }
    }

    /**
     * A select rendered with an empty first option submits an empty string rather than omitting
     * the key, so the inherited isset() check would pass every required rule. Treat blank as
     * missing.
     *
     * @param array $payload The input payload.
     * @param string $field Field code.
     *
     * @return bool TRUE if the field carries a value; otherwise, FALSE.
     */
    protected static function isFieldSet(array $payload, $field)
    {
        if (!isset($payload[$field])) {
            return false;
        }

        $value = $payload[$field];

        return is_string($value) ? trim($value) !== '' : !empty($value);
    }
}
