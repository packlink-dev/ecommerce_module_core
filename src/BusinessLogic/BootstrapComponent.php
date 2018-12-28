<?php

namespace Packlink\BusinessLogic;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\TaskEvents\TickEvent;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Packlink\BusinessLogic\Controllers\DashboardController;
use Packlink\BusinessLogic\Controllers\ShippingMethodController;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\Scheduler\ScheduleTickHandler;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;
use Packlink\BusinessLogic\User\UserAccountService;
use Packlink\BusinessLogic\WebHook\Events\ShippingLabelEvent;
use Packlink\BusinessLogic\WebHook\Events\ShippingStatusEvent;
use Packlink\BusinessLogic\WebHook\Events\TrackingInfoEvent;
use Packlink\BusinessLogic\WebHook\WebHookEventHandler;

/**
 * Class BootstrapComponent.
 *
 * @package Packlink\BusinessLogic
 */
class BootstrapComponent extends \Logeecom\Infrastructure\BootstrapComponent
{
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

                return new Proxy($config->getAuthorizationToken(), $client);
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

        // subscribe web hook shipping label listener
        $eventBuss->when(
            ShippingLabelEvent::CLASS_NAME,
            function (ShippingLabelEvent $event) {
                WebHookEventHandler::getInstance()->handleShippingLabelEvent($event);
            }
        );

        // subscribe web hook shipping status listener
        $eventBuss->when(
            ShippingStatusEvent::CLASS_NAME,
            function (ShippingStatusEvent $event) {
                WebHookEventHandler::getInstance()->handleShippingStatusEvent($event);
            }
        );

        // subscribe web hook tracking info listener
        $eventBuss->when(
            TrackingInfoEvent::CLASS_NAME,
            function (TrackingInfoEvent $event) {
                WebHookEventHandler::getInstance()->handleTrackingInfoEvent($event);
            }
        );
    }
}
