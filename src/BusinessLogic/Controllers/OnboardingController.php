<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\DTO\OnboardingState;

class OnboardingController
{
    /**
     * Gets current state of the on-boarding page.
     *
     * @return OnboardingState
     */
    public function getCurrentState()
    {
        $result = new OnboardingState();

        /** @var \Packlink\BusinessLogic\Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);

        $parcel = $configService->getDefaultParcel();
        $warehouse = $configService->getDefaultWarehouse();

        if ($parcel === null && $warehouse === null) {
            $result->state = OnboardingState::WELCOME_STATE;

        } else {
            $result->state = OnboardingState::OVERVIEW_STATE;
        }

        return $result;
    }
}
