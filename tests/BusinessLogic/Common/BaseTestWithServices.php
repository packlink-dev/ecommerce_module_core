<?php

namespace Logeecom\Tests\BusinessLogic\Common;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\AsyncProcessUrlProviderInterface;
use Logeecom\Infrastructure\TaskExecution\HttpTaskExecutor;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskExecutorInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerConfigInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestWarehouse;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\BaseInfrastructureTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestAsyncProcessUrlProvider;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestQueueService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerConfig;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\Brands\Packlink\PacklinkConfigurationService;
use Packlink\BusinessLogic\Brand\BrandConfigurationService;
use Packlink\BusinessLogic\Country\CountryService;
use Packlink\BusinessLogic\Country\Models\Country;
use Packlink\BusinessLogic\Country\WarehouseCountryService;
use Packlink\BusinessLogic\CountryLabels\CountryService as CountryLabelService;
use Packlink\BusinessLogic\DTO\ValidationError;
use Packlink\BusinessLogic\FileResolver\FileResolverService;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Scheduler\Interfaces\SchedulerInterface;
use Packlink\BusinessLogic\Tasks\DefaultTaskMetadataProvider;
use Packlink\BusinessLogic\Tasks\Interfaces\TaskMetadataProviderInterface;
use Packlink\BusinessLogic\User\UserAccountService;
use Packlink\BusinessLogic\Warehouse\Warehouse;
use Packlink\BusinessLogic\Warehouse\WarehouseService;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Scheduler\TestScheduler;

/**
 * Class BaseTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Common
 */
abstract class BaseTestWithServices extends BaseInfrastructureTestWithServices
{
    /**
     * @var TestShopConfiguration
     */
    public $shopConfig;
    /**
     * @var \Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient
     */
    public $httpClient;
    /**
     * @var TestScheduler
     */
    public $scheduler;

    /**
     * @before
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoFactoryRegistrationException
     * @throws \Exception
     */
    protected function before()
    {
        parent::before();

        $me = $this;

        $this->shopConfig = new TestShopConfiguration();
        $this->scheduler = new TestScheduler();

        TestServiceRegister::registerService(
            Configuration::CLASS_NAME,
            function () use ($me) {
                return $me->shopConfig;
            }
        );

        TestServiceRegister::registerService(
            WarehouseService::CLASS_NAME,
            function () {
                $taskExecutor = ServiceRegister::getService(TaskExecutorInterface::CLASS_NAME);

                return new WarehouseService($taskExecutor);
            }
        );

        TestServiceRegister::registerService(
            UserAccountService::CLASS_NAME,
            function () {
                $taskExecutor = ServiceRegister::getService(TaskExecutorInterface::CLASS_NAME);
                $scheduler = ServiceRegister::getService(SchedulerInterface::class);

                return new UserAccountService($taskExecutor, $scheduler);
            }
        );

        TestServiceRegister::registerService(
            SchedulerInterface::class,
            function () use ($me) {
                return $me->scheduler;
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
                return new Proxy($me->shopConfig, $me->httpClient);
            }
        );

        TestServiceRegister::registerService(
            CountryService::CLASS_NAME,
            function () {
                return CountryService::getInstance();
            }
        );

        TestServiceRegister::registerService(
            WarehouseCountryService::CLASS_NAME,
            function () {
                return WarehouseCountryService::getInstance();
            }
        );

        $queueService = new TestQueueService();
        TestServiceRegister::registerService(
            QueueService::CLASS_NAME,
            function () use ($queueService) {
                return $queueService;
            }
        );

        $wakeupService = new TestTaskRunnerWakeupService();
        TestServiceRegister::registerService(
            TaskRunnerWakeup::CLASS_NAME,
            function () use ($wakeupService) {
                return $wakeupService;
            }
        );


        TestServiceRegister::registerService(
            AsyncProcessUrlProviderInterface::CLASS_NAME,
            function () {
                return new TestAsyncProcessUrlProvider();
            }
        );

        TestServiceRegister::registerService(
            TaskRunnerConfigInterface::CLASS_NAME,
            function () {
                $config = TestServiceRegister::getService(Configuration::CLASS_NAME);
                $urlProvider = TestServiceRegister::getService(AsyncProcessUrlProviderInterface::CLASS_NAME);


                return new TestTaskRunnerConfig($config, $urlProvider);
            }
        );

        TestServiceRegister::registerService(
            TaskMetadataProviderInterface::CLASS_NAME,
            function () use ($me) {
                $taskRunnerConfig = TestServiceRegister::getService(TaskRunnerConfigInterface::CLASS_NAME);
                return new DefaultTaskMetadataProvider($me->shopConfig, $taskRunnerConfig);
            }
        );

        TestServiceRegister::registerService(
            TaskExecutorInterface::CLASS_NAME,
            function () use ($queueService, $me) {
                /** @var TaskMetadataProviderInterface $metadataProvider */
                $metadataProvider = ServiceRegister::getService(TaskMetadataProviderInterface::CLASS_NAME);

                $taskRunnerConfig = TestServiceRegister::getService(TaskRunnerConfigInterface::CLASS_NAME);

                return new HttpTaskExecutor(
                    $queueService,
                    $metadataProvider,
                    $me->shopConfig,
                    EventBus::getInstance(),
                    ServiceRegister::getService(TimeProvider::CLASS_NAME),
                    ServiceRegister::getService(SchedulerInterface::class),
                    $taskRunnerConfig
                );
            }
        );

        TestServiceRegister::registerService(
            FileResolverService::CLASS_NAME,
            function () {
                return new FileResolverService(
                    array(
                        __DIR__ . '/../../../src/BusinessLogic/Resources/countries'
                    )
                );
            }
        );

        TestServiceRegister::registerService(
            \Packlink\BusinessLogic\CountryLabels\Interfaces\CountryService::CLASS_NAME,
            function () {
                $fileResolverService = ServiceRegister::getService(FileResolverService::CLASS_NAME);

                /** @noinspection PhpParamsInspection */
                return new CountryLabelService($fileResolverService);
            }
        );

        TestServiceRegister::registerService(
            BrandConfigurationService::CLASS_NAME,
            function () {
                return new PacklinkConfigurationService();
            }
        );

        TestRepositoryRegistry::registerRepository(QueueItem::CLASS_NAME, MemoryQueueItemRepository::THIS_CLASS_NAME);

        TestFrontDtoFactory::register(Warehouse::CLASS_KEY, TestWarehouse::CLASS_NAME);
        TestFrontDtoFactory::register(ParcelInfo::CLASS_KEY, ParcelInfo::CLASS_NAME);
        TestFrontDtoFactory::register(ValidationError::CLASS_KEY, ValidationError::CLASS_NAME);
        TestFrontDtoFactory::register(Country::CLASS_KEY, Country::CLASS_NAME);
    }

    /**
     * @after
     *
     * @return void
     */
    protected function after()
    {
        parent::after();

        TestFrontDtoFactory::reset();
    }
}
