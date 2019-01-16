<?php

namespace Logeecom\Tests\BusinessLogic\User;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Tests\Common\BaseTestWithServices;
use Logeecom\Tests\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Logeecom\Tests\Common\TestComponents\TaskExecution\TestQueueService;
use Logeecom\Tests\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use Logeecom\Tests\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Common\TestServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\User\UserAccountService;

/**
 * Class UserAccountTest
 * @package Logeecom\Tests\BusinessLogic\User
 */
class UserAccountLoginTest extends BaseTestWithServices
{
    /**
     * Http client instance.
     *
     * @var TestHttpClient
     */
    public $httpClient;
    /**
     * User account service instance.
     *
     * @var UserAccountService
     */
    public $userAccountService;

    /**
     * Tests when empty value is provided for API key.
     */
    public function testEmptyApiKey()
    {
        $this->assertFalse($this->userAccountService->login(''));
        $this->assertFalse($this->userAccountService->login(null));
        /** @noinspection PhpParamsInspection */
        $this->assertFalse($this->userAccountService->login(array()));
    }

    /**
     * Tests user login and user initialization
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testLogin()
    {
        $this->httpClient->setMockResponses($this->getMockResponses());

        $this->assertTrue($this->userAccountService->login('GoodApiKey'));

        $this->assertEquals('GoodApiKey', TestShopConfiguration::getInstance()->getAuthorizationToken());
        $this->assertCount(2, $this->httpClient->getHistory());

        /** @var \Logeecom\Infrastructure\ORM\Interfaces\QueueItemRepository $queueStorage */
        $queueStorage = RepositoryRegistry::getQueueItemRepository();

        /** @var \Logeecom\Infrastructure\TaskExecution\QueueItem[] $queueItems */
        $queueItems = $queueStorage->select();
        $this->assertCount(2, $queueItems);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertEquals('GetDefaultParcelAndWarehouseTask', $queueItems[0]->getTaskType());
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertEquals('UpdateShippingServicesTask', $queueItems[1]->getTaskType());
    }

    /**
     * Returns responses for testing user initialization.
     *
     * @return HttpResponse[] Array of Http responses.
     */
    private function getMockResponses()
    {
        return array(
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../../Common/ApiResponses/user.json')
            ),
            new HttpResponse(
                200, array(), null
            ),
        );
    }

    public function testLoginBadHttp()
    {
        $this->httpClient->setMockResponses($this->getMockBadResponses());
        $this->assertFalse($this->userAccountService->login('GoodApiKey'));
        $this->assertNotEmpty($this->shopLogger->loggedMessages);
    }

    /**
     * Returns bad responses.
     *
     * @return HttpResponse[] Array of Http responses.
     */
    private function getMockBadResponses()
    {
        return array(new HttpResponse(400, array(), null));
    }

    /**
     * Tests setting of default parcel
     */
    public function testSettingParcelInfo()
    {
        $this->httpClient->setMockResponses($this->getParcelMockResponses());

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->userAccountService->setDefaultParcel(true);

        $parcelInfo = $this->shopConfig->getDefaultParcel();
        $this->assertCount(1, $this->httpClient->getHistory());
        $this->assertNotNull($parcelInfo);
        $this->assertEquals('parcel test 1', $parcelInfo->name);
    }

    /**
     * Returns responses for testing setting of Parcel Info.
     *
     * @return HttpResponse[] Array of Http responses.
     */
    private function getParcelMockResponses()
    {
        return array(
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../../Common/ApiResponses/parcels.json')
            ),
        );
    }

    /**
     * Tests setting of default warehouse
     */
    public function testSettingWarehouseInfo()
    {
        $this->httpClient->setMockResponses($this->getWarehouseMockResponses());

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->userAccountService->setWarehouseInfo(true);

        $warehouse = $this->shopConfig->getDefaultWarehouse();
        $this->assertCount(1, $this->httpClient->getHistory());
        $this->assertNotNull($warehouse);
        $this->assertEquals('222459d5e4b0ed5488fe91544', $warehouse->id);
    }

    /**
     * Returns responses for testing setting of Warehouse Info.
     *
     * @return HttpResponse[] Array of Http responses.
     */
    private function getWarehouseMockResponses()
    {
        return array(
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../../Common/ApiResponses/warehouses.json')
            ),
        );
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     */
    protected function setUp()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        parent::setUp();

        RepositoryRegistry::registerRepository(QueueItem::CLASS_NAME, MemoryQueueItemRepository::getClassName());

        $this->httpClient = new TestHttpClient();
        $queue = new TestQueueService();
        $taskRunnerStarter = new TestTaskRunnerWakeupService();
        $self = $this;

        /** @noinspection PhpUnhandledExceptionInspection */
        TestServiceRegister::registerService(
            QueueService::CLASS_NAME,
            function () use ($queue) {
                return $queue;
            }
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        TestServiceRegister::registerService(
            TaskRunnerWakeup::CLASS_NAME,
            function () use ($taskRunnerStarter) {
                return $taskRunnerStarter;
            }
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($self) {
                return $self->httpClient;
            }
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        TestServiceRegister::registerService(
            Proxy::CLASS_NAME,
            function () use ($self) {
                /** @var Configuration $config */
                $config = TestServiceRegister::getService(Configuration::CLASS_NAME);

                return new Proxy($config->getAuthorizationToken(), $self->httpClient);
            }
        );

        $this->userAccountService = UserAccountService::getInstance();
    }

    protected function tearDown()
    {
        UserAccountService::resetInstance();
        parent::tearDown();
    }
}
