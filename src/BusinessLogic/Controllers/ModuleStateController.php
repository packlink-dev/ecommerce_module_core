<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\DTO\ModuleState;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Warehouse\Warehouse;
use Packlink\DemoUI\Services\BusinessLogic\ConfigurationService;

/**
 * Class ModuleStateController
 * @package Packlink\BusinessLogic\Controllers
 */
class ModuleStateController
{
    /**
     * Gets current state of the application.
     *
     * @return ModuleState
     */
    public function getCurrentState()
    {
        /** @var ConfigurationService $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);

        /** @var string $apiToken */
        $apiToken = $configService->getAuthorizationToken();

        /** @var ParcelInfo|null $defaultParcel */
        $defaultParcel = $configService->getDefaultParcel();

        /** @var Warehouse|null $defaultWarehouse */
        $defaultWarehouse = $configService->getDefaultWarehouse();

        $result = new ModuleState();

        if (empty($apiToken)) {
            $result->state = ModuleState::LOGIN_STATE;

        } else if ($defaultParcel === null || $defaultWarehouse === null) {
            $result->state = ModuleState::ONBOARDING_STATE;

        } else {
            $result->state = ModuleState::SERVICES_STATE;
        }

        return $result;
    }
}
