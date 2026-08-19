<?php

namespace Logeecom\Tests\BusinessLogic\Customs;

use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Logeecom\Tests\BusinessLogic\Dto\BaseDtoTest;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\CountryLabels\CountryService as CountryLabelService;
use Packlink\BusinessLogic\Customs\Models\CustomsMapping;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\DTO\ValidationError;
use Packlink\BusinessLogic\FileResolver\FileResolverService;

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

        // Validation errors are built through Translator, which resolves the
        // country-labels CountryService (and its FileResolverService) from the
        // service register.
        new TestServiceRegister(
            array(
                FileResolverService::CLASS_NAME => function () {
                    return new FileResolverService(
                        array(
                            __DIR__ . '/../../../src/BusinessLogic/Resources/countries',
                        )
                    );
                },
                \Packlink\BusinessLogic\CountryLabels\Interfaces\CountryService::CLASS_NAME => function () {
                    $fileResolverService = ServiceRegister::getService(FileResolverService::CLASS_NAME);

                    /** @noinspection PhpParamsInspection */
                    return new CountryLabelService($fileResolverService);
                },
            )
        );

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
        unset($raw['mapping_tariff_number'], $raw['mapping_company_vat'], $raw['mapping_country_of_origin']);

        $mapping = CustomsMapping::fromArray($raw);

        self::assertSame('', $mapping->mappingTariffNumber);
        self::assertSame('', $mapping->mappingCompanyVat);
        self::assertSame('', $mapping->mappingCountryOfOrigin);
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
     * The default tariff number is the fallback for products that carry no HS code of their own,
     * so it is required even when a platform attribute is mapped - a mapping resolves per product
     * and yields nothing for a product whose attribute is empty.
     *
     * @throws FrontDtoValidationException
     */
    public function testMissingTariffNumberIsRejected()
    {
        $this->assertRequiredFieldError('default_tariff_number');
    }

    /**
     * @throws FrontDtoValidationException
     */
    public function testMissingCountryOfOriginIsRejected()
    {
        $this->assertRequiredFieldError('default_country');
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
     * A select rendered with an empty first option submits '', not an absent key. That must
     * count as missing, otherwise every required-field rule is decorative.
     *
     * @throws FrontDtoValidationException
     */
    public function testEmptyRequiredFieldIsRejected()
    {
        $raw = $this->getValidPayload();
        $raw['default_reason'] = '';

        $errors = $this->collectErrors($raw);

        self::assertNotNull($errors, 'An empty required field must raise a validation exception.');
        self::assertTrue($this->hasError($errors, 'default_reason', ValidationError::ERROR_REQUIRED_FIELD));
    }

    /**
     * @throws FrontDtoValidationException
     */
    public function testUnknownReasonIsRejected()
    {
        $raw = $this->getValidPayload();
        $raw['default_reason'] = 'BECAUSE_I_SAID_SO';

        $errors = $this->collectErrors($raw);

        self::assertNotNull($errors);
        self::assertTrue($this->hasError($errors, 'default_reason', ValidationError::ERROR_INVALID_FIELD));
    }

    /**
     * @throws FrontDtoValidationException
     */
    public function testUnknownReceiverUserTypeIsRejected()
    {
        $raw = $this->getValidPayload();
        $raw['default_receiver_user_type'] = 'ROBOT';

        $errors = $this->collectErrors($raw);

        self::assertNotNull($errors);
        self::assertTrue($this->hasError($errors, 'default_receiver_user_type', ValidationError::ERROR_INVALID_FIELD));
    }

    /**
     * Stores configured before the form carried the Packlink-cased tokens hold lowercase values.
     * They stay valid and are normalized, so no merchant is forced to re-save.
     *
     * @throws FrontDtoValidationException
     */
    public function testLegacyLowercaseEnumValuesAreAcceptedAndNormalized()
    {
        $raw = $this->getValidPayload();
        $raw['default_reason'] = 'purchase_or_sale';
        $raw['default_receiver_user_type'] = 'private_person';
        $raw['default_country'] = 'fr';

        $mapping = CustomsMapping::fromArray($raw);

        self::assertSame('PURCHASE_OR_SALE', $mapping->defaultReason);
        self::assertSame('PRIVATE_PERSON', $mapping->defaultReceiverUserType);
        self::assertSame('FR', $mapping->defaultCountry);
    }

    /**
     * @throws FrontDtoValidationException
     */
    public function testInvalidCountryOfOriginIsRejected()
    {
        $raw = $this->getValidPayload();
        $raw['default_country'] = 'FRANCE';

        $errors = $this->collectErrors($raw);

        self::assertNotNull($errors);
        self::assertTrue($this->hasError($errors, 'default_country', ValidationError::ERROR_INVALID_FIELD));
    }

    /**
     * A mapped platform attribute does not excuse the default: the mapping supplies the HS code of
     * products that have one, the default covers every product that does not.
     *
     * @throws FrontDtoValidationException
     */
    public function testMappedTariffNumberDoesNotMakeTheDefaultOptional()
    {
        $raw = $this->getValidPayload();
        $raw['default_tariff_number'] = '';
        $raw['mapping_tariff_number'] = 'hs_code_1';

        $errors = $this->collectErrors($raw);

        self::assertNotNull($errors);
        self::assertTrue($this->hasError($errors, 'default_tariff_number', ValidationError::ERROR_REQUIRED_FIELD));
    }

    /**
     * @throws FrontDtoValidationException
     */
    public function testMappedCountryOfOriginDoesNotMakeTheDefaultOptional()
    {
        $raw = $this->getValidPayload();
        $raw['default_country'] = '';
        $raw['mapping_country_of_origin'] = 'origin_1';

        $errors = $this->collectErrors($raw);

        self::assertNotNull($errors);
        self::assertTrue($this->hasError($errors, 'default_country', ValidationError::ERROR_REQUIRED_FIELD));
    }

    /**
     * The readiness predicate must agree with save-time validation, so a mapping stored without a
     * default HS code cannot report itself usable.
     */
    public function testIsConfiguredIsFalseWithoutDefaultTariffNumber()
    {
        $mapping = CustomsMapping::fromStoredArray($this->getValidPayload());
        $mapping->defaultTariffNumber = '';

        self::assertFalse($mapping->isConfigured());
    }

    /**
     * A default receiver tax id is a stand-in for per-customer data, so it is offered as a
     * last-resort fallback and never demanded.
     *
     * @throws FrontDtoValidationException
     */
    public function testReceiverTaxIdIsNotRequired()
    {
        $raw = $this->getValidPayload();
        $raw['default_receiver_tax_id'] = '';
        $raw['mapping_receiver_tax_id'] = '';

        $mapping = CustomsMapping::fromArray($raw);

        self::assertTrue($mapping->isConfigured());
    }

    /**
     * @throws FrontDtoValidationException
     */
    public function testIsConfiguredIsTrueForCompleteMapping()
    {
        self::assertTrue(CustomsMapping::fromArray($this->getValidPayload())->isConfigured());
    }

    /**
     * The readiness predicate must also answer for DTOs built programmatically by a platform,
     * which bypass validation entirely.
     */
    public function testIsConfiguredIsFalseForEmptyMapping()
    {
        self::assertFalse((new CustomsMapping())->isConfigured());
    }

    /**
     * A store that pressed "Save Changes" without touching the form under the old rules is
     * persisted and must load - but must not count as configured.
     */
    public function testLegacyStoredMappingLoadsWithoutValidationAndIsNotConfigured()
    {
        $mapping = CustomsMapping::fromStoredArray(
            array(
                'default_reason' => 'purchase_or_sale',
                'default_sender_tax_id' => '123',
                'default_receiver_user_type' => 'private_person',
                'default_receiver_tax_id' => '',
                'default_tariff_number' => '',
                'default_country' => '',
                'mapping_receiver_tax_id' => '',
                'mapping_tariff_number' => '',
                'mapping_company_vat' => '',
                'mapping_country_of_origin' => '',
            )
        );

        self::assertSame('PURCHASE_OR_SALE', $mapping->defaultReason);
        self::assertFalse($mapping->isConfigured());
    }

    /**
     * Runs the payload through validation and returns the errors, or null when it validated.
     *
     * @param array $raw
     *
     * @return ValidationError[]|null
     */
    private function collectErrors(array $raw)
    {
        try {
            CustomsMapping::fromArray($raw);
        } catch (FrontDtoValidationException $e) {
            return $e->getValidationErrors();
        }

        return null;
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
            'mapping_country_of_origin' => 'origin_1',
        );
    }
}
