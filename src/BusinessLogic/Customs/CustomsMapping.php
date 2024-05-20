<?php

namespace Packlink\BusinessLogic\Customs;

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
        static::validate($raw);

        $mapping = new self();
        $mapping->defaultReason = static::getDataValue($raw,'default_reason');
        $mapping->defaultSenderTaxId = static::getDataValue($raw,'default_sender_tax_id');
        $mapping->defaultReceiverUserType = static::getDataValue($raw,'default_receiver_user_type');
        $mapping->defaultReceiverTaxId = static::getDataValue($raw,'default_receiver_tax_id');
        $mapping->defaultTariffNumber = static::getDataValue($raw,'default_tariff_number');
        $mapping->defaultCountry = static::getDataValue($raw,'default_country');
        $mapping->mappingReceiverTaxId = static::getDataValue($raw,'mapping_receiver_tax_id');

        return $mapping;
    }

    /**
     * Transforms DTO to its array format.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array(
            'default_reason' => $this->defaultReason,
            'default_sender_tax_id' => $this->defaultSenderTaxId,
            'default_receiver_user_type' => $this->defaultReceiverUserType,
            'default_receiver_tax_id' => $this->defaultReceiverTaxId,
            'default_tariff_number' => $this->defaultTariffNumber,
            'default_country' => $this->defaultCountry,
            'mapping_receiver_tax_id' => $this->mappingReceiverTaxId,
        );
    }

    protected static function doValidate(array $payload, array &$validationErrors)
    {
        parent::doValidate($payload, $validationErrors);

        if (!preg_match('/^[0-9]{6,8}$/', $payload['default_tariff_number'])) {
            static::setInvalidFieldError(
                'default_tariff_number',
                $validationErrors,
                Translator::translate('validation.invalidField')
            );
        }
    }
}
