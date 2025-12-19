<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto;

use Packlink\BusinessLogic\Country\Models\Country;

/**
 * Class TestCountry
 *
 * @package BusinessLogic\Common\TestComponents\Dto
 */
class TestCountry extends Country
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    public function __construct()
    {
        $this->name = 'Mauritius';
        $this->code = 'MU';
        $this->postalCode = '42602';
    }
}
