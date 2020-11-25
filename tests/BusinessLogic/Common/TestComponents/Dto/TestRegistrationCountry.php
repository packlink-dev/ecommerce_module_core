<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto;

use Packlink\BusinessLogic\Country\RegistrationCountry;

/**
 * Class TestRegistrationCountry
 *
 * @package BusinessLogic\Common\TestComponents\Dto
 */
class TestRegistrationCountry extends RegistrationCountry
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    public function __construct()
    {
        $this->name = 'Cuba';
        $this->code = 'CU';
        $this->postalCode = '10400';
        $this->registrationLink = 'https://pro.packlink.com/register';
        $this->platformCountry = 'UN';
    }
}
