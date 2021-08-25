<?php

namespace Packlink\BusinessLogic\Controllers\DTO;

use Packlink\BusinessLogic\DTO\FrontDto;

class OnboardingState extends FrontDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Welcome state key.
     */
    const WELCOME_STATE = 'welcome';

    /**
     * Overview state key.
     */
    const OVERVIEW_STATE = 'overview';

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
