<?php

namespace Packlink\DemoUI\Controllers;

use Packlink\BusinessLogic\Controllers\RegistrationController as RegistrationControllerBase;
use Packlink\DemoUI\Controllers\Models\Request;

/**
 * Class RegistrationController.
 *
 * @package Packlink\DemoUI\Controllers
 */
class RegistrationController extends BaseHttpController
{
    /**
     * @var RegistrationControllerBase
     */
    private $controller;

    /**
     * RegistrationController constructor.
     */
    public function __construct()
    {
        $this->controller = new RegistrationControllerBase();
    }

    /**
     * Handles GET request.
     */
    public function get()
    {
        $this->output($this->controller->getRegisterData());
    }

    /**
     * Handles POST request.
     *
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     * @throws \Packlink\BusinessLogic\Registration\Exceptions\UnableToRegisterAccountException
     */
    public function post(Request $request)
    {
        $payload = $request->getPayload();

        $payload['ecommerces'] = array('Test');

        $this->output(array('success' => $this->controller->register($payload)));
    }
}
