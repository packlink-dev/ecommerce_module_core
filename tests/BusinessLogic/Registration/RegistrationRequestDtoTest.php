<?php

namespace BusinessLogic\Registration;

use Logeecom\Tests\BusinessLogic\Dto\BaseDtoTest;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\Registration\RegistrationRequest;

/**
 * Class RegistrationRequestDtoTest
 *
 * @package BusinessLogic\Registration
 */
class RegistrationRequestDtoTest extends BaseDtoTest
{
    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testValidRegistrationRequest()
    {
        $request = RegistrationRequest::fromArray($this->getRequest());

        self::assertEquals('john.doe@example.com', $request->email);
        self::assertEquals('test1234', $request->password);
        self::assertEquals('(024) 418 52 52', $request->phone);
        self::assertEquals('1 - 10', $request->estimatedDeliveryVolume);
        self::assertEquals('PRO', $request->platform);
        self::assertEquals('de_DE', $request->language);
        self::assertEquals('UN', $request->platformCountry);
        self::assertEquals('http://example.com', $request->source);
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testInvalidEmail()
    {
        $request = $this->getRequest();
        $request['email'] = 'test';

        $this->checkIfValidationExceptionIsThrown($request, 'email');
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testInvalidPassword()
    {
        $request = $this->getRequest();
        $request['password'] = 'pass1';

        $this->checkIfValidationExceptionIsThrown($request, 'password');
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testInvalidDeliveryVolume()
    {
        $request = $this->getRequest();
        $request['estimated_delivery_volume'] = 'test';

        $this->checkIfValidationExceptionIsThrown($request, 'estimated_delivery_volume');
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testInvalidPhone()
    {
        $request = $this->getRequest();
        $request['phone'] = '1234e756789-00';

        $this->checkIfValidationExceptionIsThrown($request, 'phone');
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testInvalidLanguage()
    {
        $request = $this->getRequest();
        $request['language'] = 'en_US';

        $this->checkIfValidationExceptionIsThrown($request, 'language');
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testInvalidPlatformCountry()
    {
        $request = $this->getRequest();
        $request['platform_country'] = 'AT';

        $this->checkIfValidationExceptionIsThrown($request, 'platform_country');
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testInvalidPlatformSource()
    {
        $request = $this->getRequest();
        $request['source'] = 'test';

        $this->checkIfValidationExceptionIsThrown($request, 'source');
    }

    /**
     * Checks whether a validation exception for a specified field has been thrown for the request.
     *
     * @param array $request
     * @param string $field
     */
    private function checkIfValidationExceptionIsThrown($request, $field)
    {
        $exceptionThrown = false;

        try {
            RegistrationRequest::fromArray($request);
        } catch (FrontDtoValidationException $e) {
            $exceptionThrown = true;
            $errors = $e->getValidationErrors();
            self::assertCount(1, $errors);

            $errorCodes = array_map(create_function('$error', 'return $error->field;'), $errors);

            self::assertArraySubset(array($field), $errorCodes);
        }

        self::assertTrue($exceptionThrown);
    }

    /**
     * @return array
     *
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    private function getRequest()
    {
        return array(
            'email' => 'john.doe@example.com',
            'password' => 'test1234',
            'phone' => '(024) 418 52 52',
            'estimated_delivery_volume' => '1 - 10',
            'platform' => 'PRO',
            'language' => 'de_DE',
            'platform_country' => 'UN',
            'policies' => array(
                'data_processing' => true,
                'terms_and_conditions' => true,
                'marketing_emails' => true,
                'marketing_calls' => true,
            ),
            'source' => 'http://example.com',
            'ecommerces' => array('Shopify'),
            'marketplaces' => array('eBay'),
        );
    }
}
