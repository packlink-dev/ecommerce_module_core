<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\Analytics;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

/**
 * Class AnalyticsController.
 *
 * @package Packlink\BusinessLogic\Controllers
 */
class AnalyticsController
{
    /**
     * Sends analytics setup event if all conditions are met.
     */
    public static function sendSetupEvent()
    {
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);

        if (!$configService->isSetupFinished()
            && $configService->getDefaultParcel() !== null
            && $configService->getDefaultWarehouse() !== null
        ) {
            /** @var ShippingMethodService $shippingService */
            $shippingService = ServiceRegister::getService(ShippingMethodService::CLASS_NAME);

            if (count($shippingService->getActiveMethods()) === 1) {
                /** @var Proxy $proxy */
                $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
                $proxy->sendAnalytics(Analytics::EVENT_SETUP);
                $configService->setSetupFinished();
            }
        }
    }

    /**
     * Sends event notifying that other services in the integrated system are disabled.
     */
    public static function sendOtherServicesDisabledEvent()
    {
        /** @var Proxy $proxy */
        $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        $proxy->sendAnalytics(Analytics::EVENT_OTHER_SERVICES_DISABLED);
    }
}
