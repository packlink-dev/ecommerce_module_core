<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto;

use Packlink\BusinessLogic\Warehouse\Warehouse;

/**
 * Class TestWarehouse.
 *
 * @package BusinessLogic\Common\TestComponents\Dto
 */
class TestWarehouse extends Warehouse
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    public function __construct()
    {
        $this->alias = 'default';
        $this->name = 'default';
        $this->surname = 'test';
        $this->postalCode = '28001';
        $this->city = 'Madrid';
        $this->company = 'Test';
        $this->country = 'ES';
        $this->address = 'test';
        $this->phone = '123456789';
        $this->email = 'default@default.com';
        $this->default = true;
    }
}
