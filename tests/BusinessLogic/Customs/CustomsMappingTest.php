<?php

namespace Logeecom\Tests\BusinessLogic\Customs;

use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Logeecom\Tests\BusinessLogic\Dto\BaseDtoTest;
use Packlink\BusinessLogic\Customs\Models\CustomsMapping;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\DTO\ValidationError;

/**
 * Class CustomsMappingTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Customs
 */
class CustomsMappingTest extends BaseDtoTest
{
    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoFactoryRegistrationException
     */
    protected function before()
    {
        parent::before();

        TestFrontDtoFactory::register(CustomsMapping::CLASS_KEY, CustomsMapping::CLASS_NAME);
    }

    /**
     * @throws FrontDtoValidationException
     */
    public function testFromArrayToArrayRoundTrip()
    {
        $raw = $this->getValidPayload();

        $mapping = CustomsMapping::fromArray($raw);

        self::assertEquals($raw, $mapping->toArray());
    }

    /**
     * The mapping_tariff_number and mapping_company_vat fields select which
     * platform attribute a value comes from; they are optional since not
     * every platform exposes a matching mapping (e.g. only WooCommerce
     * supports a Company VAT mapping).
     *
     * @throws FrontDtoValidationException
     */
    public function testOptionalMappingFieldsCanBeOmitted()
    {
        $raw = $this->getValidPayload();
        unset($raw['mapping_tariff_number'], $raw['mapping_company_vat']);

        $mapping = CustomsMapping::fromArray($raw);

        self::assertSame('', $mapping->mappingTariffNumber);
        self::assertSame('', $mapping->mappingCompanyVat);
    }

    /**
     * @throws FrontDtoValidationException
     */
    public function testMissingDefaultReasonIsRejected()
    {
        $this->assertRequiredFieldError('default_reason');
    }

    /**
     * @throws FrontDtoValidationException
     */
    public function testMissingDefaultSenderTaxIdIsRejected()
    {
        $this->assertRequiredFieldError('default_sender_tax_id');
    }

    /**
     * @throws FrontDtoValidationException
     */
    public function testMissingDefaultReceiverUserTypeIsRejected()
    {
        $this->assertRequiredFieldError('default_receiver_user_type');
    }

    /**
     * default_tariff_number is optional; omitting it must not raise a
     * validation error nor a PHP undefined-key/preg_match warning.
     *
     * @throws FrontDtoValidationException
     */
    public function testMissingTariffNumberIsAccepted()
    {
        $raw = $this->getValidPayload();
        unset($raw['default_tariff_number']);

        $mapping = CustomsMapping::fromArray($raw);

        self::assertSame('', $mapping->defaultTariffNumber);
    }

    /**
     * @throws FrontDtoValidationException
     */
    public function testTariffNumberTooShortIsRejected()
    {
        $this->assertTariffNumberInvalid('123');
    }

    /**
     * @throws FrontDtoValidationException
     */
    public function testTariffNumberTooLongIsRejected()
    {
        $this->assertTariffNumberInvalid('123456789');
    }

    /**
     * @throws FrontDtoValidationException
     */
    public function testTariffNumberSixDigitsIsValid()
    {
        $raw = $this->getValidPayload();
        $raw['default_tariff_number'] = '123456';

        $mapping = CustomsMapping::fromArray($raw);

        self::assertEquals('123456', $mapping->defaultTariffNumber);
    }

    /**
     * @throws FrontDtoValidationException
     */
    public function testTariffNumberEightDigitsIsValid()
    {
        $raw = $this->getValidPayload();
        $raw['default_tariff_number'] = '12345678';

        $mapping = CustomsMapping::fromArray($raw);

        self::assertEquals('12345678', $mapping->defaultTariffNumber);
    }

    /**
     * Asserts that removing a required field surfaces a required-field validation error.
     *
     * @param string $field
     */
    private function assertRequiredFieldError($field)
    {
        $raw = $this->getValidPayload();
        unset($raw[$field]);

        $errors = null;
        try {
            CustomsMapping::fromArray($raw);
        } catch (FrontDtoValidationException $e) {
            $errors = $e->getValidationErrors();
        }

        self::assertNotNull($errors, 'Missing required field must raise a validation exception.');
        self::assertTrue(
            $this->hasError($errors, $field, ValidationError::ERROR_REQUIRED_FIELD),
            'Expected a required-field error for "' . $field . '".'
        );
    }

    /**
     * Asserts that an invalid tariff number surfaces an invalid-field validation error.
     *
     * @param string $tariffNumber
     */
    private function assertTariffNumberInvalid($tariffNumber)
    {
        $raw = $this->getValidPayload();
        $raw['default_tariff_number'] = $tariffNumber;

        $errors = null;
        try {
            CustomsMapping::fromArray($raw);
        } catch (FrontDtoValidationException $e) {
            $errors = $e->getValidationErrors();
        }

        self::assertNotNull($errors, 'Invalid tariff number must raise a validation exception.');
        self::assertTrue(
            $this->hasError($errors, 'default_tariff_number', ValidationError::ERROR_INVALID_FIELD),
            'Expected an invalid-field error for tariff number "' . $tariffNumber . '".'
        );
    }

    /**
     * @param ValidationError[] $errors
     * @param string $field
     * @param string $code
     *
     * @return bool
     */
    private function hasError($errors, $field, $code)
    {
        foreach ($errors as $error) {
            if ($error->field === $field && $error->code === $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    private function getValidPayload()
    {
        return array(
            'default_reason' => 'PURCHASE_OR_SALE',
            'default_sender_tax_id' => '123',
            'default_receiver_user_type' => 'PRIVATE_PERSON',
            'default_receiver_tax_id' => '123',
            'default_tariff_number' => '123456',
            'default_country' => 'FR',
            'mapping_receiver_tax_id' => 'tax_1',
            'mapping_tariff_number' => 'hs_code_1',
            'mapping_company_vat' => 'vat_1',
        );
    }
}
