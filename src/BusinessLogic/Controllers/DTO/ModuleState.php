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
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Login state key.
     */
    const LOGIN_STATE = 'login';

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

    /**
     * Fields for this DTO. Needed for validation and transformation from/to array.
     *
     * @var array
     */
    protected static $fields = array(
        'state',
    );
}
