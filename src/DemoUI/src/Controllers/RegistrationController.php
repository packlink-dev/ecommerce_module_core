<?php

namespace Packlink\DemoUI\Controllers;

use Packlink\BusinessLogic\Controllers\DTO\RegistrationRequest;
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
        $data = $this->controller->getRegisterData();

        echo json_encode($data->toArray());
    }

    public function post(Request $request)
    {
        $payload = $request->getPayload();

        $registrationRequest = new RegistrationRequest(
            $payload['email'],
            $payload['password'],
            $payload['estimated_delivery_volume'],
            $payload['phone'],
            $payload['platform_country'],
            $payload['source'],
            array('Test'),
            array(),
            $payload['terms_and_conditions'],
            $payload['marketing_emails']
        );

        echo json_encode(array('success' => $this->controller->register($registrationRequest)));
    }

}
