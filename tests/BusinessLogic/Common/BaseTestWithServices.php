<?php

namespace Logeecom\Tests\BusinessLogic\Common;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestWarehouse;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\BaseInfrastructureTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestQueueService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\Brands\Packlink\PacklinkConfigurationService;
use Packlink\BusinessLogic\Brand\BrandConfigurationService;
use Packlink\BusinessLogic\Country\Country;
use Packlink\BusinessLogic\Country\CountryService;
use Packlink\BusinessLogic\Country\WarehouseCountryService;
use Packlink\BusinessLogic\DTO\ValidationError;
use Packlink\BusinessLogic\FileResolver\FileResolverService;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\CountryLabels\CountryService as CountryLabelService;
use Packlink\BusinessLogic\Warehouse\Warehouse;
use Packlink\BusinessLogic\Warehouse\WarehouseService;

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

        TestServiceRegister::registerService(
            Configuration::CLASS_NAME,
            function () use ($me) {
                return $me->shopConfig;
            }
        );

        TestServiceRegister::registerService(
            WarehouseService::CLASS_NAME,
            function () {
                return WarehouseService::getInstance();
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
