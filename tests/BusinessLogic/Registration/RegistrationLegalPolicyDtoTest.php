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

        self::assertEquals(true, $policy->isDataProcessingAccepted);
        self::assertEquals(true, $policy->isTermsAccepted);
        self::assertEquals(false, $policy->isMarketingCallsAccepted);
        self::assertEquals(false, $policy->isMarketingCallsAccepted);
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testInvalidRegistrationLegalPolicy()
    {
        $policy = array(
            'data_processing' => false,
            'terms_and_conditions' => false,
            'marketing_emails' => false,
            'marketing_calls' => false,
        );

        $this->setExpectedException(FrontDtoValidationException::CLASS_NAME);

        RegistrationLegalPolicy::fromArray($policy);
    }
}
