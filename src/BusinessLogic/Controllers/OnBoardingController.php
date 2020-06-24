<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\DTO\OnBoardingState;

class OnBoardingController
{
    /**
     * Gets current state of the on-boarding page.
     *
     * @return OnBoardingState
     */
    public function getCurrentState()
    {
        $result = new OnBoardingState();

        /** @var \Packlink\BusinessLogic\Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);

        $parcel = $configService->getDefaultParcel();
        $warehouse = $configService->getDefaultWarehouse();

        if ($parcel === null && $warehouse === null) {
            $result->state = OnBoardingState::WELCOME_STATE;

        } else {
            $result->state = OnBoardingState::OVERVIEW_STATE;
        }

        return $result;
    }
}
