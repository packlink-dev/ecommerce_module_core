<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\Warehouse;

use Packlink\BusinessLogic\Warehouse\WarehouseService;

class MockWarehouseService extends WarehouseService
{
    protected static $instance;

    public $callHistory = array();
    public $getWarehouseResult = null;
    public $updateWarehouseDataResult = null;

    public function getWarehouse($createIfNotExist = true)
    {
        $this->callHistory[] = array('getWarehouse' => array($createIfNotExist));

        return $this->getWarehouseResult;
    }

    /**
     * Creates instance of this class.
     *
     * @return static
     *
     * @noinspection PhpDocSignatureInspection
     */
    public static function create()
    {
        return new self();
    }

    public function updateWarehouseData(array $payload)
    {
        $this->callHistory[] = array('updateWarehouseData' => array($payload));

        return $this->updateWarehouseDataResult;
    }
}