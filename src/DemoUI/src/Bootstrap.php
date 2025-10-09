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
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestRegistrationInfoService;
use Packlink\Brands\Packlink\PacklinkConfigurationService;
use Packlink\BusinessLogic\BootstrapComponent;
use Packlink\BusinessLogic\Brand\BrandConfigurationService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\FileResolver\FileResolverService;
use Packlink\BusinessLogic\CountryLabels\Interfaces\CountryService;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\OAuth\Models\OAuthInfo;
use Packlink\BusinessLogic\OAuth\Models\OAuthState;
use Packlink\BusinessLogic\OAuth\Proxy\OAuthProxy;
use Packlink\BusinessLogic\OAuth\Services\Interfaces\OAuthServiceInterface;
use Packlink\BusinessLogic\OAuth\Services\Interfaces\OAuthStateServiceInterface;
use Packlink\BusinessLogic\OAuth\Services\OAuthConfiguration;
use Packlink\BusinessLogic\OAuth\Services\OAuthService;
use Packlink\BusinessLogic\OAuth\Services\OAuthStateService;
use Packlink\BusinessLogic\Order\Interfaces\ShopOrderService as ShopOrderServiceInterface;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\Registration\RegistrationInfoService;
use Packlink\BusinessLogic\Scheduler\Models\Schedule;
use Packlink\BusinessLogic\ShipmentDraft\Models\OrderSendDraftTaskMap;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\User\UserAccountService;
use Packlink\DemoUI\Brands\Acme\AcmeConfigurationService;
use Packlink\DemoUI\Repository\SessionRepository;
use Packlink\DemoUI\Services\BusinessLogic\CarrierService;
use Packlink\DemoUI\Services\BusinessLogic\ConfigurationService;
use Packlink\DemoUI\Services\BusinessLogic\CustomsMappingService;
use Packlink\DemoUI\Services\BusinessLogic\OAuthConfigurationService;
use Packlink\DemoUI\Services\BusinessLogic\ShopOrderService;
use Packlink\BusinessLogic\SystemInformation\SystemInfoService as SystemInfoServiceInterface;
use Packlink\DemoUI\Services\BusinessLogic\SystemInfoService;
use Packlink\DemoUI\Services\Infrastructure\LoggerService;

/**
 * Class Bootstrap
 *
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
     * @var RegistrationInfoService
     */
    private $registrationInfoService;
    /**
     * @var SystemInfoService
     */
    private $systemInfoService;
    /**
     * @var CustomsMappingService
     */
    private $customsService;

    /**
     * @var OAuthProxy
     */
    private $oauthProxy;
    /**
     * @var Proxy
     */
    private $proxy;
    /**
     * @var OAuthConfigurationService
     */
    private $oAuthConfiguration;

    /**
     * Bootstrap constructor.
     */
    public function __construct()
    {
        $client = new CurlHttpClient();
        $configService = ConfigurationService::getInstance();
        $this->jsonSerializer = new JsonSerializer();
        $this->httpClientService = $client;
        $this->loggerService = new LoggerService();
        $this->configService = $configService;
        $this->shopOrderService = new ShopOrderService();
        $this->carrierService = new CarrierService();
        $this->userAccountService = UserAccountService::getInstance();
        $this->registrationInfoService = new TestRegistrationInfoService();
        $this->systemInfoService = new SystemInfoService();
        $this->customsService = new CustomsMappingService();
        $this->oauthProxy = new OAuthProxy(OAuthConfigurationService::getInstance(), $client);
        $this->proxy = new Proxy($configService, $client);
        $this->oAuthConfiguration = OAuthConfigurationService::getInstance();
    }

    /**
     * Initializes infrastructure components.
     */
    public static function init()
    {
        static::$instance = new static();

        parent::init();
    }

    /**
     * Initializes infrastructure services and utilities.
     */
    protected static function initServices()
    {
        parent::initServices();

        static::$instance->initInstanceServices();
        static::$instance->initBrandDependentServices();
        static::$instance->setMultistore(false);
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
        RepositoryRegistry::registerRepository(OAuthState::CLASS_NAME, SessionRepository::getClassName());
        RepositoryRegistry::registerRepository(OAuthInfo::CLASS_NAME, SessionRepository::getClassName());

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

        ServiceRegister::registerService(
            RegistrationInfoService::CLASS_NAME,
            function () use ($instance) {
                return $instance->registrationInfoService;
            }
        );

        ServiceRegister::registerService(
            SystemInfoServiceInterface::CLASS_NAME,
            function () use ($instance) {
                return $instance->systemInfoService;
            }
        );

        ServiceRegister::registerService(
            \Packlink\BusinessLogic\Customs\CustomsMappingService::CLASS_NAME,
            function () use ($instance) {
                return $instance->customsService;
            }
        );

        ServiceRegister::registerService(
            OAuthStateServiceInterface::CLASS_NAME,
            function () use ($instance) {
                $repository = RepositoryRegistry::getRepository(OAuthState::CLASS_NAME);

                return new OAuthStateService($repository);
            }
        );

        ServiceRegister::registerService(
            OAuthServiceInterface::CLASS_NAME,
            function () use ($instance) {
                $repository = RepositoryRegistry::getRepository(OAuthInfo::CLASS_NAME);
                /** @var OAuthStateServiceInterface $stateService */
                $stateService = ServiceRegister::getService(OAuthStateServiceInterface::CLASS_NAME);
                return new OAuthService(
                    $instance->oauthProxy,
                    $instance->proxy,
                    $repository,
                    $stateService,
                    $instance->oAuthConfiguration
                );
            }
        );
    }

    protected function initBrandDependentServices()
    {
        $brandPlatformCode = getenv('PL_PLATFORM');

        switch ($brandPlatformCode) {
            case 'PRO':
                ServiceRegister::registerService(
                    BrandConfigurationService::CLASS_NAME,
                    function () {
                        return new PacklinkConfigurationService();
                    }
                );

                ServiceRegister::registerService(
                    FileResolverService::CLASS_NAME,
                    function () {
                        return new FileResolverService(
                            array(
                                __DIR__ . '/../../BusinessLogic/Resources/countries',
                                __DIR__ . '/../../Brands/Packlink/Resources/countries',
                            )
                        );
                    }
                );
                break;
            case 'ACME':
                ServiceRegister::registerService(
                    BrandConfigurationService::CLASS_NAME,
                    function () {
                        return new AcmeConfigurationService();
                    }
                );

                ServiceRegister::registerService(
                    FileResolverService::CLASS_NAME,
                    function () {
                        return new FileResolverService(
                            array(
                                __DIR__ . '/../../BusinessLogic/Resources/countries',
                                __DIR__ . '/Brands/Acme/Resources/countries',
                            )
                        );
                    }
                );
        }

        ServiceRegister::registerService(
            CountryService::CLASS_NAME,
            function () {
                /** @var FileResolverService $fileResolverService */
                $fileResolverService = ServiceRegister::getService(FileResolverService::CLASS_NAME);

                return new \Packlink\BusinessLogic\CountryLabels\CountryService($fileResolverService);
            }
        );
    }

    /**
     * Sets multistore.
     *
     * @param bool $isMultistore
     */
    protected function setMultistore($isMultistore = false)
    {
        $this->systemInfoService->setMultistore($isMultistore);
    }
}