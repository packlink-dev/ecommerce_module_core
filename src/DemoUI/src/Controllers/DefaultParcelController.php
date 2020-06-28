<?php

namespace Packlink\DemoUI\Controllers;

use Packlink\BusinessLogic\Controllers\DefaultParcelController as DefaultParcelControllerBase;
use Packlink\DemoUI\Controllers\Models\Request;

/**
 * Class DefaultParcelController
 *
 * @package Packlink\DemoUI\Controllers
 */
class DefaultParcelController
{
    /**
     * @var DefaultParcelControllerBase
     */
    private $controller;

    public function __construct()
    {
        $this->controller = new DefaultParcelControllerBase();
    }

    /**
     * Gets default parcel
     */
    public function getDefaultParcel()
    {
        $parcel = $this->controller->getDefaultParcel();

        echo json_encode($parcel ? $parcel->toArray() : array());
    }

    /**
     * Sets default parcel.
     *
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     *
     * @throws \Exception
     */
    public function setDefaultParcel(Request $request)
    {
        $data = $request->getPayload();

        $this->controller->setDefaultParcel($data);
    }

}