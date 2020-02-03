<?php

namespace Packlink\BusinessLogic;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\TaskEvents\TickEvent;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Packlink\BusinessLogic\Controllers\DashboardController;
use Packlink\BusinessLogic\Controllers\DTO\DashboardStatus;
use Packlink\BusinessLogic\Controllers\ShippingMethodController;
use Packlink\BusinessLogic\Country\Country;
use Packlink\BusinessLogic\Country\CountryService;
use Packlink\BusinessLogic\DTO\FrontDtoFactory;
use Packlink\BusinessLogic\DTO\ValidationError;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Location\LocationService;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\BusinessLogic\Scheduler\ScheduleTickHandler;
use Packlink\BusinessLogic\ShipmentDraft\OrderSendDraftTaskMapService;
use Packlink\BusinessLogic\ShipmentDraft\ShipmentDraftService;
use Packlink\BusinessLogic\ShippingMethod\PackageTransformer;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;
use Packlink\BusinessLogic\User\UserAccountService;
use Packlink\BusinessLogic\Warehouse\Warehouse;
use Packlink\BusinessLogic\Warehouse\WarehouseService;

/**
 * Class BootstrapComponent.
 *
 * @package Packlink\BusinessLogic
 */
class BootstrapComponent extends \Logeecom\Infrastructure\BootstrapComponent
{
    /**
     * Initializes infrastructure components.
     */
    public static function init()
    {
        parent::init();

        static::initDtoRegistry();
    }

    /**
     * Initializes services and utilities.
     */
    protected static function initServices()
    {
        parent::initServices();

        ServiceRegister::registerService(
            Proxy::CLASS_NAME,
            function () {
                /** @var Configuration $config */
                $config = ServiceRegister::getService(Configuration::CLASS_NAME);
                /** @var HttpClient $client */
                $client = ServiceRegister::getService(HttpClient::CLASS_NAME);

                return new Proxy($config, $client);
            }
        );

        ServiceRegister::registerService(
            UserAccountService::CLASS_NAME,
            function () {
                return UserAccountService::getInstance();
            }
        );

        ServiceRegister::registerService(
            ShippingMethodService::CLASS_NAME,
            function () {
                return ShippingMethodService::getInstance();
            }
        );

        ServiceRegister::registerService(
            OrderService::CLASS_NAME,
            function () {
                return OrderService::getInstance();
            }
        );

        ServiceRegister::registerService(
            DashboardController::CLASS_NAME,
            function () {
                return new DashboardController();
            }
        );

        ServiceRegister::registerService(
            ShippingMethodController::CLASS_NAME,
            function () {
                return new ShippingMethodController();
            }
        );

        ServiceRegister::registerService(
            LocationService::CLASS_NAME,
            function () {
                return LocationService::getInstance();
            }
        );

        ServiceRegister::registerService(
            PackageTransformer::CLASS_NAME,
            function () {
                return PackageTransformer::getInstance();
            }
        );

        ServiceRegister::registerService(
            OrderShipmentDetailsService::CLASS_NAME,
            function () {
                return OrderShipmentDetailsService::getInstance();
            }
        );

        ServiceRegister::registerService(
            OrderSendDraftTaskMapService::CLASS_NAME,
            function () {
                return OrderSendDraftTaskMapService::getInstance();
            }
        );

        ServiceRegister::registerService(
            ShipmentDraftService::CLASS_NAME,
            function () {
                return ShipmentDraftService::getInstance();
            }
        );

        ServiceRegister::registerService(
            WarehouseService::CLASS_NAME,
            function () {
                return WarehouseService::getInstance();
            }
        );

        ServiceRegister::registerService(
            CountryService::CLASS_NAME,
            function () {
                return CountryService::getInstance();
            }
        );
    }

    /**
     * Initializes events.
     */
    protected static function initEvents()
    {
        parent::initEvents();

        /** @var EventBus $eventBuss */
        $eventBuss = ServiceRegister::getService(EventBus::CLASS_NAME);

        // subscribe tick event listener
        $eventBuss->when(
            TickEvent::CLASS_NAME,
            function () {
                $handler = new ScheduleTickHandler();
                $handler->handle();
            }
        );
    }

    /**
     * Initializes the registry of DTO classes.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    protected static function initDtoRegistry()
    {
        FrontDtoFactory::register(ValidationError::CLASS_KEY, ValidationError::CLASS_NAME);
        FrontDtoFactory::register(Warehouse::CLASS_KEY, Warehouse::CLASS_NAME);
        FrontDtoFactory::register(ParcelInfo::CLASS_KEY, ParcelInfo::CLASS_NAME);
        FrontDtoFactory::register(DashboardStatus::CLASS_KEY, DashboardStatus::CLASS_NAME);
        FrontDtoFactory::register(Country::CLASS_KEY, Country::CLASS_NAME);
    }
}
