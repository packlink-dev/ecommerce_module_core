<?php

namespace Logeecom\Tests\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Configuration\ConfigEntity;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestRegistrationInfoService;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\RegistrationController;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\IntegrationRegistration\IntegrationRegistrationService;
use Packlink\BusinessLogic\IntegrationRegistration\Interfaces\IntegrationRegistrationServiceInterface;
use Packlink\BusinessLogic\Registration\RegistrationInfoService;
use Packlink\BusinessLogic\Registration\RegistrationRequest;
use Packlink\BusinessLogic\Registration\RegistrationService;
use Packlink\BusinessLogic\User\UserAccountService;

/**
 * Class RegistrationControllerTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Controllers
 */
class RegistrationControllerTest extends BaseTestWithServices
{
    /**
     * @var RegistrationController
     */
    public $registrationController;
    /**
     * @var IntegrationRegistrationServiceInterface
     */
    public $integrationRegistrationService;
    /**
     * User account service instance.
     *
     * @var UserAccountService
     */
    public $userAccountService;

    /**
     * @before
     * @inheritdoc
     */
    public function before()
    {
        parent::before();

        RepositoryRegistry::registerRepository(ConfigEntity::CLASS_NAME, MemoryRepository::getClassName());
        TestFrontDtoFactory::register(RegistrationRequest::CLASS_KEY, RegistrationRequest::CLASS_NAME);

        TestServiceRegister::registerService(
            RegistrationInfoService::CLASS_NAME,
            function () {
                return new TestRegistrationInfoService();
            }
        );

        $me = $this;

        /** @var \Packlink\BusinessLogic\Configuration $config */
        $config = TestServiceRegister::getService(Configuration::CLASS_NAME);
        $proxy = new Proxy($config, $this->httpClient, $me->integrationRegistrationDataProvider);
        $me->integrationRegistrationService = new IntegrationRegistrationService(
            $proxy, $me->integrationRegistrationDataProvider);

        TestServiceRegister::registerService(
            IntegrationRegistrationServiceInterface::CLASS_NAME,
            function () use ($me) {
                return $me->integrationRegistrationService;
            }
        );

        TestServiceRegister::registerService(
            RegistrationService::CLASS_NAME,
            function () {
                return RegistrationService::getInstance();
            }
        );

        $this->userAccountService = UserAccountService::getInstance();
        TestServiceRegister::registerService(
            UserAccountService::CLASS_NAME,
            function () use ($me) {
                return $me->userAccountService;
            }
        );

        $this->registrationController = new RegistrationController();
    }

    /**
     * @after
     * @inheritdoc
     */
    protected function after()
    {
        UserAccountService::resetInstance();

        parent::after();
    }

    public function testGetRegisterData()
    {
        $data = $this->registrationController->getRegisterData('FR');

        $this->assertEquals('test@test.com', $data['email']);
        $this->assertEquals('1111111111111', $data['phone']);
        $this->assertEquals('localhost:7000', $data['source']);
        $this->assertEquals('FR', $data['platform_country']);
    }

    /**
     *  registerIntegration() fails when no ID stored and proxy call fails —
     *  must reset auth credentials, set error code, return false.
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\Brand\Exceptions\PlatformCountryNotSupportedByBrandException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     * @throws \Packlink\BusinessLogic\Registration\Exceptions\UnableToRegisterAccountException
     */
    public function testRegisterIntegrationFailsWhenProxyFails()
    {
        $this->integrationRegistrationDataProvider->setStoredIntegrationId(null);

        $this->httpClient->setMockResponses(array(
            new HttpResponse(200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/registrationSuccessful.json')),
            new HttpResponse(500, array(), ''),
        ));

        $result = $this->registrationController->register($this->getValidPayload());

        $this->assertFalse($result['success']);
    }

    private function getValidPayload()
    {
        return array(
            'email'                     => 'john.doe@example.com',
            'password'                  => 'Test1234567#',
            'phone'                     => '(024) 418 52 52',
            'estimated_delivery_volume' => '1 - 10',
            'platform_country'          => 'ES',
            'terms_and_conditions'      => true,
            'marketing_emails'          => false,
            'source'                    => 'example.com',
            'ecommerces'                => array('Shopify'),
            'marketplaces'              => array('eBay'),
        );
    }
}
