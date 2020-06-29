<?php

namespace Packlink\DemoUI\Controllers;

use Packlink\BusinessLogic\Controllers\RegistrationController as RegistrationControllerBase;
use Packlink\DemoUI\Controllers\Models\Request;

/**
 * Class RegistrationController
 * @package Packlink\DemoUI\Controllers
 */
class RegistrationController
{
    /**
     * @var RegistrationControllerBase
     */
    private $controller;

    public function __construct()
    {
        $this->controller = new RegistrationControllerBase();
    }

    public function get()
    {
        echo json_encode($this->controller->getRegisterData());
    }

    public function post(Request $request)
    {
        $payload = $request->getPayload();

        $payload['ecommerces'] = array('Test');

        echo json_encode(array('success' => $this->controller->register($payload)));
    }
}
