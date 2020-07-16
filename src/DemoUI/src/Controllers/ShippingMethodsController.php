<?php

namespace Packlink\DemoUI\Controllers;

use Packlink\BusinessLogic\Controllers\ShippingMethodController;
use Packlink\DemoUI\Controllers\Models\Request;

/**
 * Class ShippingMethodsController
 *
 * @package Packlink\DemoUI\Controllers
 */
class ShippingMethodsController extends BaseHttpController
{
    /**
     * @var \Packlink\BusinessLogic\Controllers\ShippingMethodController
     */
    private $controller;

    /**
     * ShippingMethodsController constructor.
     */
    public function __construct()
    {
        $this->controller = new ShippingMethodController();
    }

    /**
     * Gets active services.
     */
    public function getActive()
    {
        $this->outputDtoEntities($this->controller->getActive());
    }

    /**
     * Gets inactive services.
     */
    public function getInactive()
    {
        $this->outputDtoEntities($this->controller->getInactive());
    }

    /**
     * Gets the status of the get services task auto configuration.
     */
    public function getTaskStatus()
    {
        $this->output(array('status' => 'completed'));
    }

    /**
     * Gets a single service.
     *
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     */
    public function getService(Request $request)
    {
        $method = $this->controller->getShippingMethod((int)$request->getQuery('id'));

        $this->output($method ? $method->toArray() : array());
    }

    /**
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     */
    public function deactivate(Request $request)
    {
        $payload = $request->getPayload();

        $this->output(array('status' => $this->controller->deactivate($payload['id'])));
    }

    /**
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     */
    public function save(Request $request)
    {

    }
}