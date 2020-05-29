<?php

namespace Packlink\BusinessLogic\Controllers\DTO;

use Packlink\BusinessLogic\DTO\FrontDto;

/**
 * Class ModuleState.
 *
 * @package Packlink\BusinessLogic\Controllers\DTO
 */
class ModuleState extends FrontDto
{
    /**
     * Login state key.
     */
    const LOGIN_STATE = 'login/register';

    /**
     * On-boarding state key.
     */
    const ONBOARDING_STATE = 'onBoarding';

    /**
     * Service state key.
     */
    const SERVICES_STATE = 'services';

    /**
     * @var string Current state.
     */
    public $state;
}
