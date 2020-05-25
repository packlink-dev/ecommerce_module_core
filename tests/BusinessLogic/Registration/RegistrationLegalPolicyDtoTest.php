<?php

namespace BusinessLogic\Registration;

use Logeecom\Tests\BusinessLogic\Dto\BaseDtoTest;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\Registration\RegistrationLegalPolicy;

/**
 * Class RegistrationLegalPolicyDtoTest
 *
 * @package BusinessLogic\Registration
 */
class RegistrationLegalPolicyDtoTest extends BaseDtoTest
{
    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testValidRegistrationLegalPolicy()
    {
        $policy = RegistrationLegalPolicy::fromArray(
            array(
                'data_processing' => true,
                'terms_and_conditions' => true,
                'marketing_emails' => false,
                'marketing_calls' => false,
            )
        );

        self::assertTrue($policy->isDataProcessingAccepted);
        self::assertTrue($policy->isTermsAccepted);
        self::assertFalse($policy->isMarketingCallsAccepted);
        self::assertFalse($policy->isMarketingCallsAccepted);
    }

    public function testInvalidRegistrationLegalPolicy()
    {
        $policy = array(
            'data_processing' => false,
            'terms_and_conditions' => false,
            'marketing_emails' => false,
            'marketing_calls' => false,
        );

        $exceptionThrown = false;

        try {
            RegistrationLegalPolicy::fromArray($policy);
        } catch (FrontDtoValidationException $e) {
            $exceptionThrown = true;
            $errors = $e->getValidationErrors();
            self::assertCount(2, $errors);

            $errorCodes = array_map(
                function ($error) {
                    return $error->field;
                },
                $errors
            );

            self::assertArraySubset(array('data_processing', 'terms_and_conditions'), $errorCodes);
        }

        self::assertTrue($exceptionThrown);
    }
}
