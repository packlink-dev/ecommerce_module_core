<?php

namespace Logeecom\Tests\BusinessLogic\User;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestQueueService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Scheduler\Models\DailySchedule;
use Packlink\BusinessLogic\Scheduler\Models\HourlySchedule;
use Packlink\BusinessLogic\Scheduler\Models\Schedule;
use Packlink\BusinessLogic\Scheduler\Models\WeeklySchedule;
use Packlink\BusinessLogic\Tasks\TaskCleanupTask;
use Packlink\BusinessLogic\Tasks\UpdateShipmentDataTask;
use Packlink\BusinessLogic\Tasks\UpdateShippingServicesTask;
use Packlink\BusinessLogic\User\UserAccountService;

/**
 * Class UserAccountTest.
 *
 * @package Logeecom\Tests\BusinessLogic\User
 */
class UserAccountLoginTest extends BaseTestWithServices
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     */
    protected function setUp()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        parent::setUp();

        TestRepositoryRegistry::registerRepository(QueueItem::CLASS_NAME, MemoryQueueItemRepository::getClassName());
        TestRepositoryRegistry::registerRepository(Schedule::CLASS_NAME, MemoryRepository::getClassName());

        $queue = new TestQueueService();
        $taskRunnerStarter = new TestTaskRunnerWakeupService();

        TestServiceRegister::registerService(
            QueueService::CLASS_NAME,
            function () use ($queue) {
                return $queue;
            }
        );

        TestServiceRegister::registerService(
            TaskRunnerWakeup::CLASS_NAME,
            function () use ($taskRunnerStarter) {
                return $taskRunnerStarter;
            }
        );

        $this->userAccountService = UserAccountService::getInstance();
    }

    /**
     * User account service instance.
     *
     * @var UserAccountService
     */
    public $userAccountService;

    /**
     * Tests when empty value is provided for API key.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
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
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testLogin()
    {
        $this->httpClient->setMockResponses($this->getMockResponses());

        $this->assertTrue($this->userAccountService->login('GoodApiKey'));

        $this->assertEquals('GoodApiKey', TestShopConfiguration::getInstance()->getAuthorizationToken());
        $this->assertCount(5, $this->httpClient->getHistory());

        // check whether parcel info is set
        $parcelInfo = $this->shopConfig->getDefaultParcel();
        $this->assertNotNull($parcelInfo);

        // check whether warehouse info is set
        $warehouse = $this->shopConfig->getDefaultWarehouse();
        $this->assertNotNull($warehouse);
        $this->assertEquals('222459d5e4b0ed5488fe91544', $warehouse->id);

        /** @var \Logeecom\Infrastructure\ORM\Interfaces\QueueItemRepository $queueStorage */
        $queueStorage = RepositoryRegistry::getQueueItemRepository();

        /** @var \Logeecom\Infrastructure\TaskExecution\QueueItem[] $queueItems */
        $queueItems = $queueStorage->select();
        $this->assertCount(1, $queueItems);
        $this->assertEquals('UpdateShippingServicesTask', $queueItems[0]->getTaskType());

        /** @var MemoryRepository $scheduleRepository */
        $scheduleRepository = RepositoryRegistry::getRepository(Schedule::CLASS_NAME);
        /** @var Schedule[] $allSchedules */
        $allSchedules = $scheduleRepository->select();

        $this->assertCount(5, $allSchedules);

        $expectedSchedules = array(
            WeeklySchedule::getClassName() => array(
                UpdateShippingServicesTask::getClassName() => 1,
            ),
            HourlySchedule::getClassName() => array(
                // 2 hourly schedules, starting every 30 minutes
                UpdateShipmentDataTask::getClassName() => 2,
                TaskCleanupTask::getClassName() => 1,
            ),
            DailySchedule::getClassName() => array(
                UpdateShipmentDataTask::getClassName() => 1,
            ),
        );

        foreach ($allSchedules as $schedule) {
            $scheduleClass = get_class($schedule);
            $taskClass = explode('\\', get_class($schedule->getTask()));
            $taskClass = end($taskClass);
            $this->assertArrayHasKey($scheduleClass, $expectedSchedules);
            $this->assertArrayHasKey($taskClass, $expectedSchedules[$scheduleClass]);

            $expectedSchedules[$scheduleClass][$taskClass]--;
            if ($expectedSchedules[$scheduleClass][$taskClass] === 0) {
                unset($expectedSchedules[$scheduleClass][$taskClass]);
            }

            if (empty($expectedSchedules[$scheduleClass])) {
                unset($expectedSchedules[$scheduleClass]);
            }
        }

        $this->assertEmpty($expectedSchedules);
    }

    /**
     * Tests user login and user initialization
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testLoginNoParcel()
    {
        $this->httpClient->setMockResponses($this->getMockResponses(false));

        $this->assertTrue($this->userAccountService->login('GoodApiKey'));
        $this->assertCount(5, $this->httpClient->getHistory());

        // check whether parcel info is set
        $parcelInfo = $this->shopConfig->getDefaultParcel();
        $this->assertNull($parcelInfo, 'Parcel should not be set.');

        // check whether warehouse info is set
        $warehouse = $this->shopConfig->getDefaultWarehouse();
        $this->assertNotNull($warehouse, 'Warehouse data should be set.');
        $this->assertEquals('222459d5e4b0ed5488fe91544', $warehouse->id);
    }

    /**
     * Tests user login and user initialization
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testLoginNoWarehouse()
    {
        $this->httpClient->setMockResponses($this->getMockResponses(true, false));

        $this->assertTrue($this->userAccountService->login('GoodApiKey'));
        $this->assertCount(5, $this->httpClient->getHistory());

        // check whether parcel info is set
        $parcelInfo = $this->shopConfig->getDefaultParcel();
        $this->assertNotNull($parcelInfo, 'Parcel should be set.');

        // check whether warehouse info is set
        $warehouse = $this->shopConfig->getDefaultWarehouse();
        $this->assertNull($warehouse, 'Warehouse data should NOT be set.');
    }

    /**
     * Returns responses for testing user initialization.
     *
     * @param bool $parcel If parcel info should be set.
     * @param bool $warehouse If warehouse info should be set.
     *
     * @return HttpResponse[] Array of Http responses.
     */
    private function getMockResponses($parcel = true, $warehouse = true)
    {
        return array(
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/user.json')
            ),
            new HttpResponse(
                200, array(), $parcel ? file_get_contents(__DIR__ . '/../Common/ApiResponses/parcels.json') : ''
            ),
            new HttpResponse(
                200, array(), $warehouse ? file_get_contents(__DIR__ . '/../Common/ApiResponses/warehouses.json') : ''
            ),
            new HttpResponse(
                200, array(), ''
            ),
            new HttpResponse(
                200, array(), ''
            ),
        );
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
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
        $this->assertEquals(6, $parcelInfo->height);
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
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/parcels.json')
            ),
        );
    }

    /**
     * Tests setting of default warehouse.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
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
     * Tests setting a warehouse from unsupported country.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testSettingUnsupportedWarehouse()
    {
        $this->httpClient->setMockResponses($this->getUnsupportedWarehouseMockResponse());

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->userAccountService->setWarehouseInfo(true);

        $warehouse = $this->shopConfig->getDefaultWarehouse();

        $this->assertNull($warehouse);
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
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/warehouses.json')
            )
        );
    }

    /**
     * Returns response for unsupported warehouse.
     *
     * @return HttpResponse[] Array of Http responses.
     */
    private function getUnsupportedWarehouseMockResponse()
    {
        return array(
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/unsupportedWarehouse.json')
            )
        );
    }

    protected function tearDown()
    {
        UserAccountService::resetInstance();
        TestRepositoryRegistry::cleanUp();

        parent::tearDown();
    }
}
