<?php

namespace Logeecom\Tests\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Tests\BusinessLogic\BaseSyncTest;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryStorage;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingService;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;
use Packlink\BusinessLogic\Tasks\UpdateShippingServicesTask;

/**
 * Class UpdateShippingServicesTaskTest.
 *
 * @package Packlink\Tests\BusinessLogic\Tasks
 */
class UpdateShippingServicesTaskTest extends BaseSyncTest
{
    /**
     * @var ShippingMethodService
     */
    public $shippingMethodService;
    /**
     * Tested task instance.
     *
     * @var UpdateShippingServicesTask
     */
    public $syncTask;

    /**
     * @before
     * @inheritdoc
     */
    public function before()
    {
        parent::before();

        MemoryStorage::reset();
        RepositoryRegistry::registerRepository(ShippingMethod::CLASS_NAME, MemoryRepository::getClassName());

        $this->shopConfig->setAuthorizationToken('test_token');

        $me = $this;

        $testShopShippingMethodService = new TestShopShippingMethodService();
        TestServiceRegister::registerService(
            ShopShippingMethodService::CLASS_NAME,
            function () use ($testShopShippingMethodService) {
                return $testShopShippingMethodService;
            }
        );

        $this->shippingMethodService = ShippingMethodService::getInstance();
        TestServiceRegister::registerService(
            ShippingMethodService::CLASS_NAME,
            function () use ($me) {
                return $me->shippingMethodService;
            }
        );
    }

    /**
     * @after
     * @inheritDoc
     */
    protected function after()
    {
        ShippingMethodService::resetInstance();
        MemoryStorage::reset();

        parent::after();
    }

    /**
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testNoExecution()
    {
        // should not execute if user info or default parcel is not set.
        $this->syncTask->execute();

        self::assertCount(0, $this->shippingMethodService->getAllMethods());
        self::assertEmpty($this->httpClient->getHistory());
        $this->validate100Progress();
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testExecuteAllNew()
    {
        $this->prepareAndExecuteValidTask();

        self::assertCount(19, $this->shippingMethodService->getAllMethods());
        self::assertCount(0, $this->shippingMethodService->getActiveMethods());

        $this->validate100Progress();

        $repo = RepositoryRegistry::getRepository(ShippingMethod::CLASS_NAME);
        $query = new QueryFilter();
        $query->where('national', Operators::EQUALS, true);
        self::assertEquals(7, $repo->count($query));

        $query = new QueryFilter();
        $query->where('national', Operators::EQUALS, false);
        self::assertEquals(12, $repo->count($query));
    }

    /**
     *
     * @return void
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testExecuteNewSpecialServices()
    {
        $this->prepareAndExecuteValidTask();

        $current = $this->shippingMethodService->getAllMethods();

        $this->assertNotEmpty($current);

        $this->prepareAndExecuteValidTask();
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testExecuteNew()
    {
        $this->prepareAndExecuteValidTask();

        // in test repository in this test ids of services start from 4
        $this->shippingMethodService->activate(4);
        $activeMethods = $this->shippingMethodService->getActiveMethods();
        self::assertCount(1, $activeMethods);

        $method = $activeMethods[0];
        $services = $method->getShippingServices();
        self::assertCount(1, $services);
        self::assertEquals(20339, $services[0]->serviceId);
        self::assertEquals(5.98, $services[0]->totalPrice);
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testExecuteLocalUpdate()
    {
        $this->prepareAndExecuteValidTask();

        $this->shippingMethodService->activate(4);
        $method = $this->shippingMethodService->getShippingMethod(4);

        $services = $method->getShippingServices();
        // previous price = 5.98
        $services[0]->totalPrice = 13;
        $method->setShippingServices($services);
        $repo = RepositoryRegistry::getRepository(ShippingMethod::CLASS_NAME);
        $repo->update($method);

        $method = $this->shippingMethodService->getShippingMethod($method->getId());

        // check if price is updated.
        $services = $method->getShippingServices();
        self::assertCount(1, $services);
        self::assertEquals(13, $services[0]->totalPrice);
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testExecuteAPIUpdate()
    {
        $this->prepareAndExecuteValidTask();

        $methodId = 4;
        // update locally first
        $this->shippingMethodService->activate($methodId);
        $method = $this->shippingMethodService->getShippingMethod($methodId);
        $services = $method->getShippingServices();
        // previous price 5.98
        $services[0]->totalPrice = 14.27;
        $method->setShippingServices($services);
        // add new service
        $method->addShippingService(new ShippingService(12345, 'new service', 'IT', 'IT', 12.43, 10.40, 2.03));
        $repo = RepositoryRegistry::getRepository(ShippingMethod::CLASS_NAME);
        $repo->update($method);

        $method = $this->shippingMethodService->getShippingMethod($methodId);
        self::assertCount(2, $method->getShippingServices());

        // execute task once more. All services should be updated and invalid services should be deleted.
        $this->httpClient->setMockResponses($this->getValidMockResponses());
        $this->syncTask->execute();

        self::assertCount(19, $this->shippingMethodService->getAllMethods());
        self::assertCount(1, $this->shippingMethodService->getActiveMethods());

        $method = $this->shippingMethodService->getShippingMethod($methodId);
        $services = $method->getShippingServices();
        self::assertCount(1, $services);
        self::assertEquals(5.98, $services[0]->totalPrice);
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testShippingServiceGroupingByCurrency()
    {
        $this->prepareAndExecuteValidTask();

        $methods = $this->shippingMethodService->getAllMethods();
        $repo = RepositoryRegistry::getRepository(ShippingMethod::CLASS_NAME);
        foreach ($methods as $method) {
            $method->setCurrency('GBP');
            $repo->update($method);
        }

        $this->prepareAndExecuteValidTask();
        $methods = $this->shippingMethodService->getAllMethods();

        self::assertCount(19, $methods);
        foreach ($methods as $method) {
            self::assertEquals('EUR', $method->getCurrency());
        }
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testExecuteNewAndDelete()
    {
        $this->prepareAndExecuteValidTask();

        self::assertCount(19, $this->shippingMethodService->getAllMethods());
        self::assertCount(0, $this->shippingMethodService->getActiveMethods());

        $repo = RepositoryRegistry::getRepository(ShippingMethod::CLASS_NAME);
        $query = new QueryFilter();
        $query->where('national', Operators::EQUALS, true);
        self::assertEquals(7, $repo->count($query));

        $query = new QueryFilter();
        $query->where('national', Operators::EQUALS, false);
        self::assertEquals(12, $repo->count($query));

        // only international from IT do ES
        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-ES')),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]')
            )
        );

        $this->syncTask->execute();

        self::assertCount(7, $this->shippingMethodService->getAllMethods());

        $repo = RepositoryRegistry::getRepository(ShippingMethod::CLASS_NAME);
        $query = new QueryFilter();
        $query->where('national', Operators::EQUALS, true);
        self::assertEquals(0, $repo->count($query));

        $query = new QueryFilter();
        $query->where('national', Operators::EQUALS, false);
        self::assertEquals(7, $repo->count($query));
    }

    /**
     * @return Task
     */
    protected function createSyncTaskInstance()
    {
        return new UpdateShippingServicesTask();
    }

    /**
     * @return \Packlink\BusinessLogic\Http\DTO\User
     */
    protected function getUser()
    {
        $user = new User();
        $user->country = 'IT';
        $user->firstName = 'Test';
        $user->lastName = 'User';
        $user->email = 'test.user@example.com';

        return $user;
    }

    /**
     * @return array
     */
    protected function getValidMockResponses()
    {
        return array(
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-SP')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-IT')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-ES')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-DE')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-FR')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-AT')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-NL')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-BE')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-PT')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-TR')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-IE')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-GB')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-HU')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-PL')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-CH')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-LU')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-AR')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-US')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-BO')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-MX')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-CL')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-CZ')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-SE')),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(200, array(), '[]'),
        );
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    protected function prepareAndExecuteValidTask()
    {
        $this->shopConfig->setUserInfo($this->getUser());
        $this->shopConfig->setDefaultParcel(ParcelInfo::defaultParcel());

        $this->httpClient->setMockResponses($this->getValidMockResponses());

        $this->syncTask->execute();
    }

    /**
     * @param string $countries
     *
     * @return string
     */
    private function getDemoServiceDeliveryDetails($countries)
    {
        return file_get_contents(
            __DIR__ . "/../Common/ApiResponses/ShippingServices/ShippingServiceDetails-$countries.json"
        );
    }
}
