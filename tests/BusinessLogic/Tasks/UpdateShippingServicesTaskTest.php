<?php

namespace Packlink\Tests\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Common\TestServiceRegister;
use Logeecom\Tests\Infrastructure\BaseSyncTest;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethodCost;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;
use Packlink\BusinessLogic\Tasks\UpdateShippingServicesTask;

class UpdateShippingServicesTaskTest extends BaseSyncTest
{
    /**
     * @var TestHttpClient
     */
    private $httpClient;
    /**
     * @var ShippingMethodService
     */
    private $shippingMethodService;
    /**
     * Tested task instance.
     *
     * @var UpdateShippingServicesTask
     */
    protected $syncTask;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

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

        $this->httpClient = new TestHttpClient();
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );

        TestServiceRegister::registerService(
            Proxy::CLASS_NAME,
            function () use ($me) {
                return new Proxy($me->shopConfig->getAuthorizationToken(), $me->httpClient);
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

    protected function tearDown()
    {
        ShippingMethodService::resetInstance();

        parent::tearDown();
    }

    /**
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
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
     */
    public function testExecuteAllNew()
    {
        $this->shopConfig->setUserInfo($this->getUser());
        $this->shopConfig->setDefaultParcel(ParcelInfo::defaultParcel());

        $this->httpClient->setMockResponses($this->getValidMockResponses());

        $this->syncTask->execute();

        self::assertCount(21, $this->shippingMethodService->getAllMethods());
        self::assertCount(0, $this->shippingMethodService->getActiveMethods());

        $this->validate100Progress();

        $repo = RepositoryRegistry::getRepository(ShippingMethod::CLASS_NAME);
        $query = new QueryFilter();
        $query->where('national', Operators::EQUALS, true);
        self::assertEquals(8, $repo->count($query));

        $query = new QueryFilter();
        $query->where('national', Operators::EQUALS, false);
        self::assertEquals(13, $repo->count($query));
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testExecuteNewAndUpdate()
    {
        $this->shopConfig->setUserInfo($this->getUser());
        $this->shopConfig->setDefaultParcel(ParcelInfo::defaultParcel());

        $this->httpClient->setMockResponses($this->getValidMockResponses());

        $this->syncTask->execute();

        self::assertCount(21, $this->shippingMethodService->getAllMethods());
        self::assertCount(0, $this->shippingMethodService->getActiveMethods());

        $this->shippingMethodService->activate(20945);
        self::assertCount(1, $this->shippingMethodService->getActiveMethods());

        $repo = RepositoryRegistry::getRepository(ShippingMethod::CLASS_NAME);
        $query = new QueryFilter();
        $query->where('serviceId', Operators::EQUALS, 20945);
        /** @var ShippingMethod $method */
        $method = $repo->selectOne($query);

        $costs = $method->getShippingCosts();
        self::assertCount(1, $costs);
        self::assertEquals(8.85, $costs[0]->totalPrice);

        // update price. After test, this price should be updated from API.
        $costs[0]->totalPrice = 13;
        $method->setShippingCosts($costs);
        $repo->update($method);

        $method = $repo->selectOne($query);

        // check if price is updated.
        $costs = $method->getShippingCosts();
        self::assertCount(1, $costs);
        self::assertEquals(13, $costs[0]->totalPrice);

        // use different shipping method and add one more cost. It should stay intact.
        $query = new QueryFilter();
        $query->where('serviceId', Operators::EQUALS, 20203);
        /** @var ShippingMethod $method */
        $method = $repo->selectOne($query);

        $costs = $method->getShippingCosts();
        self::assertCount(1, $costs);
        $costs[] = new ShippingMethodCost('IT', 'RS', 10, 8, 2);
        $method->setShippingCosts($costs);
        self::assertCount(2, $method->getShippingCosts());
        $repo->update($method);

        // execute once more. All services should just be updated
        $this->httpClient->setMockResponses($this->getValidMockResponses());

        $this->syncTask->execute();

        self::assertCount(21, $this->shippingMethodService->getAllMethods());
        self::assertCount(1, $this->shippingMethodService->getActiveMethods());

        // get first method and assert that price is updated
        $query = new QueryFilter();
        $query->where('serviceId', Operators::EQUALS, 20945);
        $method = $repo->selectOne($query);

        $costs = $method->getShippingCosts();
        self::assertCount(1, $costs);
        self::assertEquals(8.85, $costs[0]->totalPrice);

        // get second method and assert that additional cost is not changed.
        $query = new QueryFilter();
        $query->where('serviceId', Operators::EQUALS, 20203);
        $method = $repo->selectOne($query);
        $costs = $method->getShippingCosts();
        self::assertCount(2, $costs);
        self::assertEquals(10, $costs[1]->totalPrice);
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testExecuteNewAndDelete()
    {
        $this->shopConfig->setUserInfo($this->getUser());
        $this->shopConfig->setDefaultParcel(ParcelInfo::defaultParcel());

        $this->httpClient->setMockResponses($this->getValidMockResponses());

        $this->syncTask->execute();

        self::assertCount(21, $this->shippingMethodService->getAllMethods());
        self::assertCount(0, $this->shippingMethodService->getActiveMethods());

        $repo = RepositoryRegistry::getRepository(ShippingMethod::CLASS_NAME);
        $query = new QueryFilter();
        $query->where('national', Operators::EQUALS, true);
        self::assertEquals(8, $repo->count($query));

        $query = new QueryFilter();
        $query->where('national', Operators::EQUALS, false);
        self::assertEquals(13, $repo->count($query));

        // only international from IT do ES
        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-ES')),
                new HttpResponse(200, array(), $this->getDemoServiceDetails(20615)),
                new HttpResponse(200, array(), $this->getDemoServiceDetails(20611)),
                new HttpResponse(200, array(), $this->getDemoServiceDetails(21105)),
                new HttpResponse(200, array(), $this->getDemoServiceDetails(20209)),
                new HttpResponse(200, array(), $this->getDemoServiceDetails(21103)),
                new HttpResponse(200, array(), $this->getDemoServiceDetails(20126)),
                new HttpResponse(200, array(), $this->getDemoServiceDetails(20030)),
                new HttpResponse(200, array(), $this->getDemoServiceDetails(20255)),
                new HttpResponse(200, array(), $this->getDemoServiceDetails(20130)),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
            )
        );

        $this->syncTask->execute();

        self::assertCount(9, $this->shippingMethodService->getAllMethods());

        $repo = RepositoryRegistry::getRepository(ShippingMethod::CLASS_NAME);
        $query = new QueryFilter();
        $query->where('national', Operators::EQUALS, true);
        self::assertEquals(0, $repo->count($query));

        $query = new QueryFilter();
        $query->where('national', Operators::EQUALS, false);
        self::assertEquals(9, $repo->count($query));
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

    protected function getValidMockResponses()
    {
        return array(
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-IT')),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20339)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(21317)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20203)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20945)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20189)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20127)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20131)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20943)),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-ES')),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20615)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20611)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(21105)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20209)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(21103)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20126)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20030)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20255)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20130)),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-DE')),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20615)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20611)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20126)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20209)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(21105)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20030)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20255)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(21103)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20130)),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-FR')),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20615)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20611)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20209)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20126)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(21105)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20030)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20255)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(21103)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20130)),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-US')),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(21279)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(21103)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20937)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20329)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(20255)),
            new HttpResponse(200, array(), $this->getDemoServiceDetails(21051)),
        );
    }

    private function getDemoServiceDeliveryDetails($countries)
    {
        return file_get_contents(
            __DIR__ . "/../../Common/ApiResponses/ShippingServices/ShippingServiceDetails-$countries.json"
        );
    }

    private function getDemoServiceDetails($id)
    {
        return file_get_contents(__DIR__ . "/../../Common/ApiResponses/ShippingServices/ServiceDetails-$id.json");
    }
}
