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

        $this->setExpectedException(FrontDtoValidationException::CLASS_NAME);

        RegistrationRequest::fromArray($request);
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testInvalidPassword()
    {
        $request = $this->getRequest();
        $request['password'] = 'pass1';

        $this->setExpectedException(FrontDtoValidationException::CLASS_NAME);

        RegistrationRequest::fromArray($request);
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testInvalidDeliveryVolume()
    {
        $request = $this->getRequest();
        $request['estimated_delivery_volume'] = 'test';

        $this->setExpectedException(FrontDtoValidationException::CLASS_NAME);

        RegistrationRequest::fromArray($request);
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testInvalidPhone()
    {
        $request = $this->getRequest();
        $request['phone'] = '1234e756789-00';

        $this->setExpectedException(FrontDtoValidationException::CLASS_NAME);

        RegistrationRequest::fromArray($request);
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testInvalidLanguage()
    {
        $request = $this->getRequest();
        $request['language'] = 'en_US';

        $this->setExpectedException(FrontDtoValidationException::CLASS_NAME);

        RegistrationRequest::fromArray($request);
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testInvalidPlatformCountry()
    {
        $request = $this->getRequest();
        $request['platform_country'] = 'AT';

        $this->setExpectedException(FrontDtoValidationException::CLASS_NAME);

        RegistrationRequest::fromArray($request);
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testInvalidPlatformSource()
    {
        $request = $this->getRequest();
        $request['source'] = 'test';

        $this->setExpectedException(FrontDtoValidationException::CLASS_NAME);

        RegistrationRequest::fromArray($request);
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
