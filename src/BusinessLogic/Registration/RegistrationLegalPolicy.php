<?php

namespace Packlink\BusinessLogic\Registration;

use Packlink\BusinessLogic\DTO\FrontDto;
use Packlink\BusinessLogic\DTO\ValidationError;

/**
 * Class RegistrationLegalPolicy
 *
 * @package Packlink\BusinessLogic\Registration
 */
class RegistrationLegalPolicy extends FrontDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Unique class key.
     */
    const CLASS_KEY = 'registration_legal_policy';
    /**
     * Value based on the terms and condition checkbox.
     *
     * @var bool
     */
    public $isDataProcessingAccepted;
    /**
     * Value based on the terms and condition checkbox.
     *
     * @var bool
     */
    public $isTermsAccepted;
    /**
     * Value based on the commercial communication checkbox.
     *
     * @var bool
     */
    public $isMarketingEmailsAccepted;
    /**
     * Value based on the commercial communication checkbox.
     *
     * @var bool
     */
    public $isMarketingCallsAccepted;
    /**
     * Fields for this DTO.
     *
     * @var array
     */
    protected static $fields = array(
        'data_processing',
        'terms_and_conditions',
        'marketing_emails',
        'marketing_calls',
    );
    /**
     * Required fields for DTO to be valid.
     *
     * @var array
     */
    protected static $requiredFields = array(
        'data_processing',
        'terms_and_conditions',
        'marketing_emails',
        'marketing_calls',
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

        $instance->isDataProcessingAccepted = static::getValue($raw, 'data_processing');
        $instance->isTermsAccepted = static::getValue($raw, 'terms_and_conditions');
        $instance->isMarketingEmailsAccepted = static::getValue($raw, 'marketing_emails');
        $instance->isMarketingCallsAccepted = static::getValue($raw, 'marketing_calls');

        return $instance;
    }

    /**
     * Transforms DTO to its array format.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array(
            'data_processing' => $this->isDataProcessingAccepted,
            'terms_and_conditions' => $this->isTermsAccepted,
            'marketing_emails' => $this->isMarketingEmailsAccepted,
            'marketing_calls' => $this->isMarketingCallsAccepted,
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

        if ($payload['data_processing'] === false) {
            $validationErrors[] = static::getValidationError(
                ValidationError::ERROR_INVALID_FIELD,
                'data_processing',
                'Field must be set to true.'
            );
        }

        if ($payload['terms_and_conditions'] === false) {
            $validationErrors[] = static::getValidationError(
                ValidationError::ERROR_INVALID_FIELD,
                'terms_and_conditions',
                'Field must be set to true.'
            );
        }
    }
}
