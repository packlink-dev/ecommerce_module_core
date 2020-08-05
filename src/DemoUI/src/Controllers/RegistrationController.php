<?php

namespace Packlink\DemoUI\Controllers;

use Packlink\BusinessLogic\Controllers\RegistrationController as RegistrationControllerBase;
use Packlink\BusinessLogic\Tasks\UpdateShippingServicesTask;
use Packlink\DemoUI\Controllers\Models\Request;

/**
 * Class RegistrationController.
 *
 * @package Packlink\DemoUI\Controllers
 */
class RegistrationController extends BaseHttpController
{
    /**
     * @var bool
     */
    protected $requiresAuthentication = false;
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
     *
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     */
    public function get(Request $request)
    {
        $this->output($this->controller->getRegisterData($request->getQuery('country')));
    }

    /**
     * Handles POST request.
     *
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
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

        $success = $this->controller->register($payload);
        if ($success) {
            // this is only for the Demo app because there is no task runner
            $task = new UpdateShippingServicesTask();
            $task->execute();
        }

        $this->output(array('success' => $success));
    }
}
