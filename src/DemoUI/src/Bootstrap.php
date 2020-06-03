<?php

namespace Packlink\DemoUI;

use Logeecom\Infrastructure\Configuration\ConfigEntity;
use Logeecom\Infrastructure\Http\CurlHttpClient;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Logeecom\Infrastructure\Logger\LogData;
use Logeecom\Infrastructure\ORM\Entity;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Serializer\Concrete\JsonSerializer;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Process;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Packlink\BusinessLogic\BootstrapComponent;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\Scheduler\Models\Schedule;
use Packlink\BusinessLogic\ShipmentDraft\Models\OrderSendDraftTaskMap;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\User\UserAccountService;
use Packlink\DemoUI\Repository\SessionRepository;
use Packlink\DemoUI\Services\BusinessLogic\CarrierService;
use Packlink\DemoUI\Services\BusinessLogic\ConfigurationService;
use Packlink\DemoUI\Services\BusinessLogic\ShopOrderService;
use Packlink\BusinessLogic\Order\Interfaces\ShopOrderService as ShopOrderServiceInterface;
use Packlink\DemoUI\Services\Infrastructure\LoggerService;

/**
 * Class Bootstrap
 * @package Packlink\DemoUI
 */
class Bootstrap extends BootstrapComponent
{
    /**
     * Class instance.
     *
     * @var Bootstrap
     */
    protected static $instance;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @var CurlHttpClient
     */
    private $httpClientService;

    /**
     * @var LoggerService
     */
    private $loggerService;

    /**
     * @var ConfigurationService
     */
    private $configService;

    /**
     * @var ShopOrderService
     */
    private $shopOrderService;

    /**
     * @var CarrierService
     */
    private $carrierService;

    /**
     * @var UserAccountService
     */
    private $userAccountService;

    /**
     * Bootstrap constructor.
     */
    public function __construct() {
        $this->jsonSerializer = new JsonSerializer();
        $this->httpClientService = new CurlHttpClient();
        $this->loggerService = new LoggerService();
        $this->configService = ConfigurationService::getInstance();
        $this->shopOrderService = new ShopOrderService();
        $this->carrierService = new CarrierService();
        $this->userAccountService = UserAccountService::getInstance();

        static::$instance = $this;
    }

    /**
     * Initializes infrastructure services and utilities.
     */
    protected static function initServices()
    {
        parent::initServices();

        static::$instance->initInstanceServices();
    }

    /**
     * Initializes repositories.
     *
     * @throws RepositoryClassException
     */
    protected static function initRepositories()
    {
        parent::initRepositories();

        RepositoryRegistry::registerRepository(Process::CLASS_NAME, SessionRepository::getClassName());
        RepositoryRegistry::registerRepository(ConfigEntity::CLASS_NAME, SessionRepository::getClassName());
        RepositoryRegistry::registerRepository(QueueItem::CLASS_NAME, MemoryQueueItemRepository::getClassName());
        RepositoryRegistry::registerRepository(Schedule::CLASS_NAME, SessionRepository::getClassName());
        RepositoryRegistry::registerRepository(OrderShipmentDetails::CLASS_NAME, SessionRepository::getClassName());
        RepositoryRegistry::registerRepository(ShippingMethod::CLASS_NAME, SessionRepository::getClassName());
        RepositoryRegistry::registerRepository(Entity::CLASS_NAME, SessionRepository::getClassName());
        RepositoryRegistry::registerRepository(LogData::CLASS_NAME, SessionRepository::getClassName());
        RepositoryRegistry::registerRepository(OrderSendDraftTaskMap::CLASS_NAME, SessionRepository::getClassName());
    }

    /**
     * Initializes instance services.
     */
    protected function initInstanceServices()
    {
        $instance = static::$instance;

        ServiceRegister::registerService(
            Serializer::CLASS_NAME,
            function () use ($instance) {
                return $instance->jsonSerializer;
            }
        );

        ServiceRegister::registerService(
            ShopLoggerAdapter::CLASS_NAME,
            function () use ($instance) {
                return $instance->loggerService;
            }
        );

        ServiceRegister::registerService(
            Configuration::CLASS_NAME,
            function () use ($instance) {
                return $instance->configService;
            }
        );

        ServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($instance) {
                return $instance->httpClientService;
            }
        );

        ServiceRegister::registerService(
            ShopOrderServiceInterface::CLASS_NAME,
            function () use ($instance) {
                return $instance->shopOrderService;
            }
        );

        ServiceRegister::registerService(
            ShopShippingMethodService::CLASS_NAME,
            function () use ($instance) {
                return $instance->carrierService;
            }
        );

        ServiceRegister::registerService(
            UserAccountService::CLASS_NAME,
            function () use ($instance) {
                return $instance->userAccountService;
            }
        );
    }
}