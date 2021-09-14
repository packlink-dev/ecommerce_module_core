<?php

namespace Packlink\BusinessLogic\Controllers\DTO;

use Packlink\BusinessLogic\DTO\FrontDto;

class RegistrationResponse extends FrontDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * @var string
     */
    public $context;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $phone;

    /**
     * @var string
     */
    public $source;

    /**
     * @var bool
     */
    public $termsAndConditionsUrl;

    /**
     * @var bool
     */
    public $privacyPolicyUrl;

    /**
     * Fields for this DTO. Needed for validation and transformation from/to array.
     *
     * @var array
     */
    protected static $fields = array(
        'context',
        'email',
        'phone',
        'source',
        'termsAndConditionsUrl',
        'privacyPolicyUrl',
    );
}
