<?php

namespace Packlink\BusinessLogic;

use Logeecom\Infrastructure\BootstrapComponent as InfrastructureBootstrapComponent;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\TaskEvents\TickEvent;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Scheduler\ScheduleTickHandler;
use Packlink\BusinessLogic\User\UserAccountService;

/**
 * Class BootstrapComponent
 * @package Packlink\BusinessLogic
 */
class BootstrapComponent extends InfrastructureBootstrapComponent
{
    /**
     * Initializes infrastructure services and utilities.
     *
     * @throws \Logeecom\Infrastructure\Exceptions\ServiceAlreadyRegisteredException
     */
    protected static function initServices()
    {
        parent::initServices();

        ServiceRegister::registerService(Proxy::CLASS_NAME, function () {
            /** @var Configuration $config */
            $config = ServiceRegister::getService(Configuration::CLASS_NAME);
            /** @var HttpClient $client */
            $client = ServiceRegister::getService(HttpClient::CLASS_NAME);

            return new Proxy($config->getAuthorizationToken(), $client);
        });

        ServiceRegister::registerService(UserAccountService::CLASS_NAME, function () {
            return UserAccountService::getInstance();
        });
    }

    /**
     * Initializes infrastructure events.
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
}
