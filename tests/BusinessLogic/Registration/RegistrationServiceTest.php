<?php

namespace BusinessLogic\Registration;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Brand\BrandConfigurationService;
use Packlink\BusinessLogic\Registration\Exceptions\UnableToRegisterAccountException;
use Packlink\BusinessLogic\Registration\RegistrationRequest;
use Packlink\BusinessLogic\Registration\RegistrationService;

/**
 * Class RegistrationServiceTest
 *
 * @package BusinessLogic\Registration
 */
class RegistrationServiceTest extends BaseTestWithServices
{
    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     * @throws \Packlink\BusinessLogic\Registration\Exceptions\UnableToRegisterAccountException
     */
    public function testRegister()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/registrationSuccessful.json');
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        /** @var RegistrationService $service */
        $service = ServiceRegister::getService(RegistrationService::CLASS_NAME);

        $token = $service->register($this->getRequest());

        $this->assertNotEmpty($token, 'Token should not be an empty string');
        $this->assertEquals('ee0870a7dc61e4eda41fbae68395c672aeafe375cd90ce4adcf615c6ae86f28d', $token);
    }

    /**
     * @return void
     * @throws UnableToRegisterAccountException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testRegisterSameEmailTwice()
    {
        $successResponse = file_get_contents(__DIR__ . '/../Common/ApiResponses/registrationSuccessful.json');
        $failureResponse = file_get_contents(__DIR__ . '/../Common/ApiResponses/userAlreadyRegistered.json');
        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(200, array(), $successResponse),
                new HttpResponse(400, array(), $failureResponse),
            )
        );

        /** @var RegistrationService $service */
        $service = ServiceRegister::getService(RegistrationService::CLASS_NAME);

        $token = $service->register($this->getRequest());

        $this->assertNotEmpty($token, 'Token should not be an empty string');
        $this->assertEquals('ee0870a7dc61e4eda41fbae68395c672aeafe375cd90ce4adcf615c6ae86f28d', $token);

        $exThrown = null;
        try {
            $service->register($this->getRequest());
        } catch (\Packlink\BusinessLogic\Registration\Exceptions\UnableToRegisterAccountException $ex) {
            $exThrown = $ex;
        }

        $this->assertNotNull($exThrown);
        $this->assertEquals('Registration failed. Error: Client already exists', $exThrown->getMessage());
    }

    /**
     * @return void
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testBadRequest()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/registrationBadRequest.json');
        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), $response)));

        /** @var RegistrationService $service */
        $service = ServiceRegister::getService(RegistrationService::CLASS_NAME);
        $request = $this->getRequest();
        $request->platform = 'test';

        $exThrown = null;
        try {
            $service->register($request);
        } catch (\Packlink\BusinessLogic\Registration\Exceptions\UnableToRegisterAccountException $ex) {
            $exThrown = $ex;
        }

        $this->assertNotNull($exThrown);
        $this->assertEquals('Registration failed. Error: Bad Request', $exThrown->getMessage());
    }

    /**
     * @before
     * @inheritDoc
     */
    protected function before()
    {
        parent::before();

        TestServiceRegister::registerService(
            RegistrationService::CLASS_NAME,
            function () {
                return RegistrationService::getInstance();
            }
        );
    }

    /**
     * @return \Packlink\BusinessLogic\Registration\RegistrationRequest
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    private function getRequest()
    {
        /** @var BrandConfigurationService $brandConfigurationService */
        $brandConfigurationService = ServiceRegister::getService(BrandConfigurationService::CLASS_NAME);
        $brand = $brandConfigurationService->get();

        return RegistrationRequest::fromArray(
            array(
                'email' => 'john.doe@example.com',
                'password' => 'test1234',
                'phone' => '(024) 418 52 52',
                'estimated_delivery_volume' => '1 - 10',
                'platform' => $brand->platformCode,
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
            )
        );
    }
}
